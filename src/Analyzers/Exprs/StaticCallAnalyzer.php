<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use function count;

class StaticCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     * @throws NeedRefinementException
     * @throws NonVerifiableStrictUsageException
     */
    public static function analyze(StaticCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        if ($expr->name instanceof Identifier) {
            $method_name = $expr->name->name;
        } elseif ($expr->name instanceof Expr) {
            $method_name_type = $node_provider->getType($expr->name);
            if ($method_name_type !== null && $method_name_type->isSingleStringLiteral()) {
                $method_name = $method_name_type->getSingleStringLiteral()->value;
            } elseif ($method_name_type === null) {
                throw NeedRefinementException::createWithNode('Found MethodCall with a method that is an expr with unknown type ', $expr);
            } else {
                throw NeedRefinementException::createWithNode('Found MethodCall with a method that is a ' . get_class($method_name_type), $expr);
            }
        } else {
            $method_name = $expr->name;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        if ($expr->class instanceof Name) {
            if ($expr->class->parts[0] === 'parent' || $expr->class->parts[0] === 'self') {
                //TODO: technically, parent should check the extends. This would imply getting MethodStorage earlier
                $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                $object_name = $class_stmt->name->name;
            } elseif ($expr->class->parts[0] === 'static') {
                //TODO: technically, we should check childrens but covariance/contravariance rules states all childrens will accept as least what the parent accepts so it's okay to check parent
                $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                $object_name = $class_stmt->name->name;
            } else {
                $object_name = $expr->class;
            }
        } else {
            $object_type = $node_provider->getType($expr->class);
            $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);
        }

        //Ok, we have a single object here. Time to fetch parameters from method
        $namespaced_class_name = strtolower(NodeNavigator::resolveName($history, $object_name));
        $method_name = strtolower($method_name);
        $method_storage = NodeNavigator::getMethodStorageFromName($namespaced_class_name, $method_name);
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $namespaced_class_name . '::' . $method_name);
        }

        $method_params = $method_storage->params;
        try {
            StrictUnionsChecker::checkValuesAgainstParams($expr->args, $method_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Method ' . $method_name . ': ' . $e->getMessage(), 0, $e);
        }

        //every potential mismatch would have been handled earlier
    }
}
