<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt;
use function count;

class ArrowFunctionAnalyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(ArrowFunction $expr, array $history): void
    {
        $has_params = false;
        //$has_at_least_one_typed_param = false;
        if (count($expr->params) !== 0) {
            $has_params = true;
            /** Checking this seems difficult. Aborting for now
             * $has_at_least_one_typed_param = true;
             * foreach($expr->params as $param) {
             *    var_dump($param->type);
             *     if ($param->type !== null && $param->type->from_docblock === false) {
             *         //TODO: check with actual types
             *         $has_at_least_one_typed_param = true;
             *     }
             * }
             */
        }

        $has_return = $expr->returnType !== null;
        /** Checking this seems difficult. Aborting for now
         * $has_typed_return = $expr->returnType->from_docblock === false;
         */
        if (!$has_params && !$has_return) {
            return;
        }

        throw NonStrictUsageException::createWithNode('Found ArrowFunction', $expr);
    }
}
