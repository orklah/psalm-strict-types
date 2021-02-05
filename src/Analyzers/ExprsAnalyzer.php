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
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
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
     * @throws NeedRefinementException
     * @throws ShouldNotHappenException
     */
    public static function customExprHandling(Expr $expr, array $history): void
    {
        $file_context = StrictTypesHooks::$internal_file_context;
        //custom plugin code here
        if ($expr instanceof ArrowFunction) {
            ArrowFunctionAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof Closure) {
            ClosureAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof FuncCall) {
            FuncCallAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof MethodCall) {
            MethodCallAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            NullsafeMethodCallAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof StaticCall) {
            StaticCallAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof Assign) {
            AssignAnalyzer::analyze($file_context, $expr, $history);
            return;
        }

        if ($expr instanceof New_) {
            New_Analyzer::analyze($file_context, $expr, $history);
            return;
        }
    }
}
