<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Type\Atomic\TLiteralString;
use function assert;

class AssignAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     * @throws NeedRefinementException
     * @throws NonVerifiableStrictUsageException
     */
    public static function analyze(Assign $expr, array $history): void
    {
        //TODO: possible false positive: this doesn't handle the __set() magic method
        if (
            !$expr->var instanceof StaticPropertyFetch &&
            !$expr->var instanceof PropertyFetch
        ) {
            // only properties can be typed
            return;
        }


        $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);
        assert($method_stmt !== null);
        $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
        assert($class_stmt !== null);
        assert($class_stmt->name !== null);
        $class_storage = StrictTypesHooks::$codebase->classlike_storage_provider->get($class_stmt->name->name);

        $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
        if ($node_provider === null) {
            //unable to fetch node provider. Throw
            throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
        }

        //retrieve property name
        $property_name_type = $node_provider->getType($expr->var);

        if ($property_name_type === null) {
            //unable to identify property. Throw
            throw new ShouldNotHappenException('Unable to retrieve property type');
        }

        if (!$property_name_type->isSingle()) {
            //multiple types. Throw for now, but may be refined
            //TODO: try to refine, not really a priority
            throw NeedRefinementException::createWithNode('Found Assign with PropertyFetch', $expr);
        }

        $property_name_atomic_types = $property_name_type->getAtomicTypes();
        $property_name_atomic_type = array_pop($property_name_atomic_types);
        if (!$property_name_atomic_type instanceof TLiteralString) {
            //TODO: try to refine, not really a priority
            throw NeedRefinementException::createWithNode('Found Assign with PropertyFetch', $expr);
        }

        $property_storage = $class_storage->properties[$property_name_atomic_type->value];
        if ($property_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Unable to retrieve property storage');
        }

        if ($property_storage->signature_type === null) {
            //not interested
            return;
        }

        $property_value = $node_provider->getType($expr->expr);
        if($property_value === null){
            //weird.
            throw new ShouldNotHappenException('Unable to retrieve property value');
        }

        if (!StrictUnionsChecker::strictUnionCheck($property_storage->signature_type, $property_value)) {
            throw NonStrictUsageException::createWithNode('Found property mismatching between ' . $property_storage->signature_type->getKey() . ' and ' . $property_value->getKey(), $expr);
        }

        if ($property_value->from_docblock === true) {
            //not trustworthy enough
            throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
        }

        //every potential mismatch would have been handled earlier
    }
}
