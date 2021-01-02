<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt;
use function count;

class FuncCallAnalyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(FuncCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $namespace_stmt = NodeNavigator::getLastNodeByType($history, Stmt\Namespace_::class);
        $namespace_id = $namespace_stmt->name->parts[0] ?? '';
        $function_id = $expr->name->parts[0];

        //The function called was in the same file. This is lucky. Otherwise I don't know where I could fetch the function
        if (isset(StrictTypesHooks::$file_storage->functions[$namespace_id . '\\' . $function_id])) {
            $has_at_least_one_typed_param = false;
            foreach (StrictTypesHooks::$file_storage->functions[$namespace_id . '\\' . $function_id]->params as $param) {
                if ($param->signature_type !== null) {
                    //TODO: check with actual types
                    $has_at_least_one_typed_param = true;
                }
            }

            if (!$has_at_least_one_typed_param) {
                return;
            }
        } else {
            //TODO: find where the function could be stored and check with actual params
        }

        throw NeedRefinementException::createWithNode('Found FuncCall', $expr);
    }
}
