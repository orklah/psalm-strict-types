<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NodeException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt;

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

        if($expr->var instanceof PropertyFetch) {
            $property_id = $expr->var->name . '::$' . $expr->var->name;
            $class_property_type = StrictTypesHooks::$codebase->properties->getPropertyType(
                $property_id,
                true,
                StrictTypesHooks::$statement_source,
                StrictTypesHooks::$file_context
            );
            if($class_property_type === null){
                //unable find property type. Throw
                throw new ShouldNotHappenException('Unable to retrieve Property type');
            }

            if($class_property_type->from_docblock === true){
                //not interested
                return;
            }
            throw NeedRefinementException::createWithNode('Found Assign with PropertyFetch', $expr);
/*
            $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);
            assert($method_stmt !== null);
            $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
            assert($class_stmt !== null);
            assert($class_stmt->name !== null);

            $class_storage = StrictTypesHooks::$codebase->classlike_storage_provider->get($class_stmt->name->name);
            if ($class_stmt !== null && $method_stmt !== null) {
                //object context, we fetch the node type provider or the context if the variable is $this
                if (is_string($expr->var->name) && $expr->var->name === 'this') {
                    $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
                    if ($node_provider === null) {
                        //unable to fetch node provider. Throw
                        throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
                    }
                    $context = StrictTypesHooks::$context_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
                    if ($context === null) {
                        //unable to context. Throw
                        throw new ShouldNotHappenException('Unable to retrieve Context');
                    }
                    $object_type = $context->vars_in_scope['$this'];
                } else {
                    $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
                    if ($node_provider === null) {
                        //unable to fetch node provider. Throw
                        throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
                    }
                    $object_type = $node_provider->getType($expr->var);
                }
            }

            //retrieve property name
            $property_name_type = $node_provider->getType($expr->var);

            if ($property_name_type === null) {
                //unable to identify property. Throw
                throw new ShouldNotHappenException('Unable to retrieve property type for "' . $expr->var->name . '"');
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
            if ($property_value === null) {
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
*/
        }
        else{
            //$expr->var instanceof StaticPropertyFetch
            throw NeedRefinementException::createWithNode('Found StaticPropertyFetch', $expr);
        }

        //every potential mismatch would have been handled earlier
    }
}
