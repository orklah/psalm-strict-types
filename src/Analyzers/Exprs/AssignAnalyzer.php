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
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\VarLikeIdentifier;
use Psalm\Type\Atomic\TNamedObject;
use UnexpectedValueException;
use function is_string;

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
        if ($expr->var instanceof PropertyFetch) {
            if ($expr->var->var instanceof Variable && is_string($expr->var->var->name)) {
                if ($expr->var->var->name === 'this') {
                    $context = NodeNavigator::getContext($history);
                    $object_type = $context->vars_in_scope['$this'];
                    if ($object_type->isSingleAndMaybeNullable()) {
                        $object_type->removeType('null');
                        $object_types = $object_type->getAtomicTypes();
                        $atomic_object_type = array_pop($object_types);
                        if ($atomic_object_type instanceof TNamedObject) {
                            $object_name = $atomic_object_type->value;
                        } else {
                            throw NeedRefinementException::createWithNode('Found a non interpretable type for $this ' . $object_type->getKey() . ' for object in assign', $expr);
                        }
                    } else {
                        throw NeedRefinementException::createWithNode('Found a non interpretable type for this ' . $object_type->getKey() . ' for object in assign', $expr);
                    }
                } else {
                    $object_name = $expr->var->var->name;
                }
            } else {
                $object_type = $node_provider->getType($expr->var->var);
                if ($object_type === null) {
                    throw new ShouldNotHappenException('Unable to retrieve object type for assignment1');
                }
                if (!$object_type->isSingleStringLiteral()) {
                    throw NeedRefinementException::createWithNode('Found a ' . $object_type->getKey() . ' for an assign', $expr);
                }
                $object_name = $object_type->getSingleStringLiteral()->value;
            }

            $property_name = $expr->var->name;
        } else {
            if ($expr->var->class instanceof Name) {
                if ($expr->var->class->parts[0] === 'self') {
                    $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                    if ($class_stmt === null) {
                        throw new ShouldNotHappenException('Could not find Class Statement for self reference');
                    }

                    if ($class_stmt->name === null) {
                        throw new ShouldNotHappenException('Could not find Class Statement name for self reference');
                    } elseif ($class_stmt->name instanceof Identifier) {
                        $object_name = $class_stmt->name->name;
                    } else {
                        $object_name = $class_stmt->name;
                    }
                } else {
                    $object_name = implode('\\', $expr->var->class->parts);
                }
            } else {
                $object_type = $node_provider->getType($expr->var->class);
                if ($object_type === null) {
                    throw new ShouldNotHappenException('Unable to retrieve object type for assignment2');
                }
                if (!$object_type->isSingleStringLiteral()) {
                    throw NeedRefinementException::createWithNode('Found a ' . $object_type->getKey() . ' for an assign', $expr);
                }
                $object_name = $object_type->getSingleStringLiteral()->value;
            }

            if ($expr->var->name instanceof VarLikeIdentifier) {
                $property_name = $expr->var->name->name;
            } else {
                $property_type = $node_provider->getType($expr->var->class);
                if ($property_type === null) {
                    throw new ShouldNotHappenException('Unable to retrieve object type for assignment3');
                }
                if (!$property_type->isSingleStringLiteral()) {
                    var_dump('---' . get_class($object_type));

                    throw NeedRefinementException::createWithNode('Found a ' . $property_type->getKey() . ' for an assign', $expr);
                }
                $property_name = $property_type->getSingleStringLiteral()->value;
            }
        }

        $namespace_stmt = NodeNavigator::getLastNodeByType($history, Namespace_::class);
        $namespace_prefix = '';
        if ($namespace_stmt !== null) {
            $namespace_prefix = (string)$namespace_stmt->name;
        }

        $property_id = NodeNavigator::addNamespacePrefix($namespace_prefix, $object_name) . '::$' . $property_name;

        try {
            $property_type = StrictTypesHooks::$codebase->properties->getPropertyType(
                $property_id,
                true,
                StrictTypesHooks::$statement_source,
                StrictTypesHooks::$file_context
            );
        }
        catch(UnexpectedValueException $e) {
            throw new ShouldNotHappenException('Unable to retrieve Property for ' . $property_id);
        }

        if($property_type === null) {
            //property found but with no type, not interesting
            return;
        }

        $value_type = $node_provider->getType($expr->expr);
        if ($value_type === null) {
            throw new ShouldNotHappenException('Unable to retrieve Expression type for ' . $property_id);
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
