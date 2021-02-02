<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Traversers;

use Orklah\StrictTypes\Analyzers\StmtsAnalyzer;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\BadTypeFromSignatureException;
use Orklah\StrictTypes\Exceptions\GoodTypeFromDocblockException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\HaltCompiler;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Label;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\StaticVar;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Node\Stmt\While_;

class StmtsTraverser
{
    /**
     * @param array<Stmt|null> $stmts
     * @param list<Stmt|Expr>  $history
     * @throws BadTypeFromSignatureException
     * @throws ShouldNotHappenException
     * @throws NeedRefinementException
     * @throws GoodTypeFromDocblockException
     */
    public static function traverseStatements(array $stmts, array $history): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt !== null) {
                self::analyzeStatement($stmt, $history);
            }
        }
    }

    /**
     * This will only receive a statement and break it down into other statements or expressions
     * @param list<Stmt|Expr> $history
     * @throws BadTypeFromSignatureException
     * @throws ShouldNotHappenException
     * @throws NeedRefinementException
     * @throws GoodTypeFromDocblockException
     */
    public static function analyzeStatement(Stmt $stmt, array $history): void
    {
        //var_dump('seen '.get_class($stmt));
        $history[] = $stmt;
        StmtsAnalyzer::customStmtHandling($stmt, $history);

        if ($stmt instanceof Break_) {
            //noop
            return;
        }

        if ($stmt instanceof Case_) {
            if ($stmt->cond !== null) {
                ExprsTraverser::traverseExpr($stmt->cond, $history);
            }
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Catch_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Class_) {
            self::traverseStatements($stmt->getMethods(), $history);
            return;
        }

        if ($stmt instanceof ClassConst) {
            //noop
            return;
        }

        if ($stmt instanceof ClassMethod) {
            if ($stmt->stmts !== null) {
                self::traverseStatements($stmt->stmts, $history);
            }
            return;
        }

        if ($stmt instanceof Const_) {
            //noop
            return;
        }

        if ($stmt instanceof Continue_) {
            //noop
            return;
        }

        if ($stmt instanceof Declare_) {
            // oh god, declare can be a block...
            if ($stmt->stmts !== null) {
                self::traverseStatements($stmt->stmts, $history);
            }
            return;
        }

        if ($stmt instanceof DeclareDeclare) {
            //not supported
            return;
        }

        if ($stmt instanceof Do_) {
            ExprsTraverser::traverseExpr($stmt->cond, $history);
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Echo_) {
            ExprsTraverser::traverseExprs($stmt->exprs, $history);
            return;
        }

        if ($stmt instanceof Else_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof ElseIf_) {
            ExprsTraverser::traverseExpr($stmt->cond, $history);
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Expression) {
            ExprsTraverser::traverseExpr($stmt->expr, $history);
            return;
        }

        if ($stmt instanceof Finally_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof For_) {
            ExprsTraverser::traverseExprs($stmt->init, $history);
            ExprsTraverser::traverseExprs($stmt->cond, $history);
            ExprsTraverser::traverseExprs($stmt->loop, $history);
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Foreach_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Function_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Global_) {
            //noop
            return;
        }

        if ($stmt instanceof Goto_) {
            //noop
            return;
        }

        if ($stmt instanceof GroupUse) {
            // TODO: what's that?
            return;
        }

        if ($stmt instanceof HaltCompiler) {
            //noop
            return;
        }

        if ($stmt instanceof If_) {
            ExprsTraverser::traverseExpr($stmt->cond, $history);
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof InlineHTML) {
            // not supported
            return;
        }

        if ($stmt instanceof Interface_) {
            //noop
            return;
        }

        if ($stmt instanceof Label) {
            // what's that?
            return;
        }

        if ($stmt instanceof Namespace_) {
            self::traverseStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Nop) {
            //noop
            return;
        }

        if ($stmt instanceof Property) {
            //noop
            return;
        }

        if ($stmt instanceof PropertyProperty) {
            // TODO: what what?
            return;
        }

        if ($stmt instanceof Return_) {
            if ($stmt->expr !== null) {
                ExprsTraverser::traverseExpr($stmt->expr, $history);
            }
            return;
        }

        if ($stmt instanceof Static_) {
            //noop
            return;
        }

        if ($stmt instanceof StaticVar) {
            // TODO: what's that?
            return;
        }

        if ($stmt instanceof Switch_) {
            ExprsTraverser::traverseExpr($stmt->cond, $history);
            foreach ($stmt->cases as $case) {
                self::analyzeStatement($case, $history);
            }
            return;
        }

        if ($stmt instanceof Throw_) {
            ExprsTraverser::traverseExpr($stmt->expr, $history);
            return;
        }

        if ($stmt instanceof Trait_) {
            //noop
            return;
        }

        if ($stmt instanceof TraitUse) {
            //noop
            return;
        }

        if ($stmt instanceof TryCatch) {
            self::traverseStatements($stmt->stmts, $history);
            self::traverseStatements($stmt->catches, $history);
            if ($stmt->finally !== null) {
                self::analyzeStatement($stmt->finally, $history);
            }
            return;
        }

        if ($stmt instanceof Unset_) {
            //not sur what could happen in here
            ExprsTraverser::traverseExprs($stmt->vars, $history);
            return;
        }

        if ($stmt instanceof Use_) {
            //noop
            return;
        }

        if ($stmt instanceof UseUse) {
            //noop
            return;
        }

        if ($stmt instanceof While_) {
            ExprsTraverser::traverseExpr($stmt->cond, $history);
            self::traverseStatements($stmt->stmts, $history);
            return;
        }
    }
}
