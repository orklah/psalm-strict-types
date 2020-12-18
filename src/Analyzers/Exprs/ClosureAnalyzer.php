<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use PhpParser\Node\Expr;
use function count;

class ClosureAnalyzer{

    /**
     * @param array<Expr> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(Expr $expr, array $history): void
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

        if (!$has_params) {
            return;
        }

        throw new NonStrictUsageException('Found Closure');
    }
}
