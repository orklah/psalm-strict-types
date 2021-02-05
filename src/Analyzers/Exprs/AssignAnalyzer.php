<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\NodeException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Issues\StrictTypesIssue;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VarLikeIdentifier;
use UnexpectedValueException;
use Webmozart\Assert\Assert;
use function is_string;

class AssignAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     * @throws NodeException
     */
    public static function analyze(FileContext $file_context, Assign $expr, array $history): void
    {
        //TODO: possible false positive: this doesn't handle the __set() magic method (check ExistingAtomicMethodCallAnalyzer?)
        if (
            !$expr->var instanceof StaticPropertyFetch &&
            !$expr->var instanceof PropertyFetch
        ) {
            // only properties can be typed
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($file_context, $history);
        if ($expr->var instanceof PropertyFetch) {
            if ($expr->var->var instanceof Variable && is_string($expr->var->var->name) && $expr->var->var->name === 'this') {
                $context = NodeNavigator::getContext($file_context, $history);
                Assert::notNull($context);
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
                $object_name = NodeNavigator::resolveName($file_context, $history, $object_name);
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

        $property_id = $object_name . '::$' . (string)$property_name;

        try {
            $property_type = $file_context->getCodebase()->properties->getPropertyType(
                $property_id,
                true,
                $file_context->getStatementsSource(),
                $file_context->getFileContext()
            );
        } catch (UnexpectedValueException $e) {
            throw new ShouldNotHappenException('Unable to retrieve Property for ' . $property_id);
        }

        if ($property_type === null) {
            //property found but with no type, not interesting
            return;
        }

        $value_type = $node_provider->getType($expr->expr);
        if ($value_type === null) {
            throw new ShouldNotHappenException('Unable to retrieve Expression type for ' . $property_id);
        }
        if($property_type->from_docblock){
            //not interesting. If the property is loosely typed we can assign anything to it
            return;
        }

        $result = StrictUnionsChecker::strictUnionCheck($property_type, $value_type);
        if($result->is_correct){
            if ($value_type->from_docblock === true) {
                //not trustworthy enough
                $message = 'Found correct type but from docblock';
                StrictTypesIssue::emitIssue($file_context, $expr, $message, $result->is_correct, $value_type->from_docblock, $result->is_partial, $result->is_mixed);
            }
        } else {
            $message = 'Found assignation mismatching between property ' . $property_type->getKey() . ' and value ' . $value_type->getKey();
            StrictTypesIssue::emitIssue($file_context, $expr, $message, $result->is_correct, $value_type->from_docblock, $result->is_partial, $result->is_mixed);
        }
    }
}
