<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

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
use PhpParser\Node\VarLikeIdentifier;
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
            if ($expr->var->var instanceof Variable && is_string($expr->var->var->name) && $expr->var->var->name === 'this') {
                $context = NodeNavigator::getContext($history);
                $object_type = $context->vars_in_scope['$this'];
            } else {
                $object_type = $node_provider->getType($expr->var->var);
            }
            $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);
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
                    }

                    $object_name = $class_stmt->name->name;
                } else {
                    $object_name = $expr->var->class;
                }
                $object_name = NodeNavigator::resolveName($history, $object_name);
            } else {
                $object_type = $node_provider->getType($expr->var->class);
                $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);
            }

            if ($expr->var->name instanceof VarLikeIdentifier) {
                $property_name = $expr->var->name->name;
            } else {
                $property_type = $node_provider->getType($expr->var->class);
                $property_name = NodeNavigator::reduceUnionToString($property_type, $expr);
            }
        }

        if ($object_name === 'stdClass') {
            //not interesting as properties can't be typed on stdClass
            return;
        }

        $property_id = $object_name . '::$' . (string) $property_name;

        try {
            $property_type = StrictTypesHooks::$codebase->properties->getPropertyType(
                $property_id,
                true,
                StrictTypesHooks::$statement_source,
                StrictTypesHooks::$file_context
            );
        } catch (UnexpectedValueException $e) {
            throw new ShouldNotHappenException('Unable to retrieve Property for ' . $property_id);
        }

        if ($property_type === null || $property_type->from_docblock) {
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
