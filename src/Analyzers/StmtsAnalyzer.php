<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Analyzers\Stmts\Return_Analyzer;
use Orklah\StrictTypes\Analyzers\Stmts\Use_Analyzer;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Use_;

class StmtsAnalyzer
{
    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     */
    public static function customStmtHandling(Stmt $stmt, array $history): void
    {
        $file_context = StrictTypesHooks::$internal_file_context;
        //custom plugin code here
        if ($stmt instanceof Return_) {
            Return_Analyzer::analyze($file_context, $stmt, $history);
            return;
        }

        if ($stmt instanceof Use_) {
            Use_Analyzer::analyze($file_context, $stmt, $history);
            return;
        }
    }
}
