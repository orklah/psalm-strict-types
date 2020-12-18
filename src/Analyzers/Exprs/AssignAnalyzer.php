<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;

class AssignAnalyzer{

    /**
     * @param array<Expr> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(Expr $expr, array $history): void
    {
        //TODO: possible false positive: this doesn't handle the __set() magic method
        if (
            !$expr->var instanceof StaticPropertyFetch &&
            !$expr->var instanceof PropertyFetch
        ) {
            // only properties can be typed
            return;
        }

        //find the class and check if the property have a type, then compare with given type
        throw new NonStrictUsageException('Found Assign with StaticPropertyFetch');
    }
}
