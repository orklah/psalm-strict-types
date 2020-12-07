<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

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

class StmtsAnalyzer
{
    /**
     * @param Stmt[] $stmts
     */
    public static function analyzeStatements(array $stmts): void
    {
        foreach ($stmts as $stmt) {
            self::analyzeStatement($stmt);
        }
    }

    /**
     * This will only receive a statement and break it down into other statements or expressions
     */
    public static function analyzeStatement(Stmt $stmt): void
    {
        if ($stmt instanceof Break_) {
            //noop
            return;
        }

        if ($stmt instanceof Case_) {
            ExprsAnalyzer::analyzeExpr($stmt->cond);
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Catch_) {
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Class_) {
            //handle properties
            //handle methods
            //what is inside $stmt->stmts?
            // TODO: do that
            return;
        }

        if ($stmt instanceof ClassConst) {
            //noop
            return;
        }

        if ($stmt instanceof ClassMethod) {
            // TODO: check other properties
            self::analyzeStatements($stmt->stmts);
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
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof DeclareDeclare) {
            //not supported
            return;
        }

        if ($stmt instanceof Do_) {
            ExprsAnalyzer::analyzeExpr($stmt->cond);
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Echo_) {
            ExprsAnalyzer::analyzeExprs($stmt->exprs);
            return;
        }

        if ($stmt instanceof Else_) {
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof ElseIf_) {
            ExprsAnalyzer::analyzeExprs($stmt->cond);
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Expression) {
            ExprsAnalyzer::analyzeExpr($stmt->expr);
            return;
        }

        if ($stmt instanceof Finally_) {
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof For_) {
            ExprsAnalyzer::analyzeExprs($stmt->init);
            ExprsAnalyzer::analyzeExprs($stmt->cond);
            ExprsAnalyzer::analyzeExprs($stmt->loop);
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Foreach_) {
            self::analyzeStatements($stmt->stmts);
            return;
        }

        if ($stmt instanceof Function_) {
            self::analyzeStatements($stmt->stmts);
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
            ExprsAnalyzer::analyzeExprs($stmt->cond);
            self::analyzeStatements($stmt->stmts);
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
            // noop
            return;
        }

        if ($stmt instanceof Nop) {
            // noop
            return;
        }

        if ($stmt instanceof Property) {
            // TODO: check impact
            return;
        }

        if ($stmt instanceof PropertyProperty) {
            // TODO: what what?
            return;
        }

        if ($stmt instanceof Return_) {
            ExprsAnalyzer::analyzeExpr($stmt->expr);
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
            ExprsAnalyzer::analyzeExpr($stmt->cond);
            foreach($stmt->cases as $case){
                self::analyzeStatement($case);
            }
            return;
        }

        if ($stmt instanceof Throw_) {
            ExprsAnalyzer::analyzeExpr($stmt->expr);
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
            self::analyzeStatements($stmt->stmts);
            foreach($stmt->catches as $catch){
                self::analyzeStatement($catch);
            }
            if($stmt->finally !== null){
                self::analyzeStatement($stmt->finally);
            }
            return;
        }

        if ($stmt instanceof Unset_) {
            //not sur what could happen in here
            self::analyzeStatements($catch->stmts);
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
            ExprsAnalyzer::analyzeExpr($stmt->cond);
            self::analyzeStatements($stmt->stmts);
            return;
        }
    }
}
