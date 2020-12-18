<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use PhpParser\Node\Expr;
use function count;

class StaticCallAnalyzer{

    /**
     * @param array<Expr> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(Expr $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        //identify object, identify method, identify params
        throw new NonStrictUsageException('Found StaticCall');
    }
}
