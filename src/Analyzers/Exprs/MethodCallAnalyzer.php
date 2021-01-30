<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function is_string;

class MethodCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NeedRefinementException
     * @throws NonStrictUsageException
     * @throws NonVerifiableStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function analyze(MethodCall $expr, array $history): void
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

        //object context, we fetch the node type provider or the context if the variable is $this
        if (is_string($expr->var->name) && $expr->var->name === 'this') {
            $context = NodeNavigator::getContext($history);
            $object_type = $context->vars_in_scope['$this'];
        } else {
            $object_type = $node_provider->getType($expr->var);
        }

        $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName(strtolower($object_name), strtolower($method_name));
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $object_name . '::' . $method_name);
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
