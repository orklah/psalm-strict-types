<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt;
use function count;

class ArrowFunctionAnalyzer{

    /**
     * @param array<Expr|Stmt> $history
     */
    public static function analyze(FileContext $file_context, ArrowFunction $expr, array $history): void
    {
        $has_params = false;
        if (count($expr->params) !== 0) {
            $has_params = true;
        }

        $has_return = $expr->returnType !== null;

        if (!$has_params && !$has_return) {
            return;
        }

        throw NeedRefinementException::createWithNode('Found ArrowFunction', $expr);
    }
}
