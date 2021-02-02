<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use function count;

class ClosureAnalyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(Closure $expr, array $history): void
    {
        $has_params = false;
        if (count($expr->params) !== 0) {
            $has_params = true;
        }

        if (!$has_params) {
            return;
        }

        throw NeedRefinementException::createWithNode('Found Closure', $expr);
    }
}
