<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Analyzers\Exprs\ArrowFunctionAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\AssignAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\ClosureAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\FuncCallAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\MethodCallAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\New_Analyzer;
use Orklah\StrictTypes\Analyzers\Exprs\NullsafeMethodCallAnalyzer;
use Orklah\StrictTypes\Analyzers\Exprs\StaticCallAnalyzer;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt;

class ExprsAnalyzer
{
    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws NeedRefinementException
     * @throws NonVerifiableStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function customExprHandling(Expr $expr, array $history): void
    {
        //custom plugin code here
        if ($expr instanceof ArrowFunction) {
            ArrowFunctionAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof Closure) {
            ClosureAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof FuncCall) {
            FuncCallAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof MethodCall) {
            MethodCallAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            NullsafeMethodCallAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof StaticCall) {
            StaticCallAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof Assign) {
            AssignAnalyzer::analyze($expr, $history);
            return;
        }

        if ($expr instanceof New_) {
            New_Analyzer::analyze($expr, $history);
            return;
        }
    }
}
