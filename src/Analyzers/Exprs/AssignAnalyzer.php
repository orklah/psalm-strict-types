<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NodeException;
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
use PhpParser\Node\Stmt\Namespace_;

class AssignAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     * @throws NodeException
     */
    public static function analyze(Assign $expr, array $history): void
    {
        //TODO: possible false positive: this doesn't handle the __set() magic method (check ExistingAtomicMethodCallAnalyzer?)
        if (
            !$expr->var instanceof StaticPropertyFetch &&
            !$expr->var instanceof PropertyFetch
        ) {
            // only properties can be typed
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        $namespace_stmt = NodeNavigator::getLastNodeByType($history, Namespace_::class);
        $namespace_prefix = '';
        if($namespace_stmt !== null){
            $namespace_prefix = (string)$namespace_stmt->name;
        }

        $property_id = $namespace_prefix . '\\' . $expr->var->name . '::$' . $expr->var->name;
        $property_type = StrictTypesHooks::$codebase->properties->getPropertyType(
            $property_id,
            true,
            StrictTypesHooks::$statement_source,
            StrictTypesHooks::$file_context
        );
        if($property_type === null){
            //unable find property type. Throw
            throw new ShouldNotHappenException('Unable to retrieve Property type');
        }

        $value_type = $node_provider->getType($expr->expr);
        if ($value_type === null) {
            throw new ShouldNotHappenException('Unable to retrieve Expression type');
        }

        if (!StrictUnionsChecker::strictUnionCheck($property_type, $value_type)) {
            throw NonStrictUsageException::createWithNode('Found assignation mismatching between property ' . $property_type->getKey() . ' and value ' . $value_type->getKey(), $expr);
        }

        if ($value_type->from_docblock === true) {
            //not trustworthy enough
            throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
        }

        //every potential mismatch would have been handled earlier
    }
}
