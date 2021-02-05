<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use Webmozart\Assert\Assert;
use function count;
use function is_string;

class NullsafeMethodCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     */
    public static function analyze(FileContext $file_context, NullsafeMethodCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($file_context, $history);

        if ($expr->name instanceof Identifier) {
            $method_name = $expr->name->name;
        } else {
            $method_name_type = $node_provider->getType($expr->name);
            $method_name = NodeNavigator::reduceUnionToString($method_name_type, $expr);
        }

        //object context, we fetch the node type provider or the context if the variable is $this
        if ($expr->var instanceof Variable && is_string($expr->var->name) && $expr->var->name === 'this') {
            $context = NodeNavigator::getContext($file_context, $history);
            Assert::notNull($context);
            $object_type = $context->vars_in_scope['$this'];
        } else {
            $object_type = $node_provider->getType($expr->var);
        }

        $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName($file_context, strtolower($object_name), strtolower($method_name));
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $object_name . '::' . $method_name);
        }

        $method_params = $method_storage->params;
        try {
            StrictUnionsChecker::checkValuesAgainstParams($file_context, $expr->args, $method_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Method ' . $method_name . ': ' . $e->getMessage(), 0, $e);
        }

        //every potential mismatch would have been handled earlier
    }
}
