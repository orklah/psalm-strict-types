<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Analyzers\Stmts\Return_Analyzer;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;

class StmtsAnalyzer
{
    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException|NonStrictUsageException
     */
    public static function customStmtHandling(Stmt $stmt, array $history): void
    {
        if ($stmt instanceof Return_) {
            Return_Analyzer::analyze($stmt, $history);
        }
    }
}
