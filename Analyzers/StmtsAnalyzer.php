<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Hooks\NonStrictUsageException;
use Orklah\StrictTypes\Hooks\StrictTypesAnalyzer;
use Orklah\StrictTypes\Utils\NodeNavigator;
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
use Psalm\Internal\MethodIdentifier;

class StmtsAnalyzer
{
    /**
     * @param Stmt[]          $stmts
     * @param list<Stmt|Expr> $history
     */
    public static function analyzeStatements(array $stmts, array $history): void
    {
        foreach ($stmts as $stmt) {
            if($stmt !== null) {
                self::analyzeStatement($stmt, $history);
            }
        }
    }

    /**
     * This will only receive a statement and break it down into other statements or expressions
     * @param list<Stmt|Expr> $history
     */
    public static function analyzeStatement(Stmt $stmt, array $history): void
    {
        //var_dump('seen '.get_class($stmt));
        $history[] = $stmt;
        self::customStmtHandling($stmt, $history);

        if ($stmt instanceof Break_) {
            //noop
            return;
        }

        if ($stmt instanceof Case_) {
            if ($stmt->cond !== null) {
                ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            }
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Catch_) {
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Class_) {
            self::analyzeStatements($stmt->getMethods(), $history);
            return;
        }

        if ($stmt instanceof ClassConst) {
            //noop
            return;
        }

        if ($stmt instanceof ClassMethod) {
            if ($stmt->stmts !== null) {
                self::analyzeStatements($stmt->stmts, $history);
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
                self::analyzeStatements($stmt->stmts, $history);
            }
            return;
        }

        if ($stmt instanceof DeclareDeclare) {
            //not supported
            return;
        }

        if ($stmt instanceof Do_) {
            ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Echo_) {
            ExprsAnalyzer::analyzeExprs($stmt->exprs, $history);
            return;
        }

        if ($stmt instanceof Else_) {
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof ElseIf_) {
            ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Expression) {
            ExprsAnalyzer::analyzeExpr($stmt->expr, $history);
            return;
        }

        if ($stmt instanceof Finally_) {
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof For_) {
            ExprsAnalyzer::analyzeExprs($stmt->init, $history);
            ExprsAnalyzer::analyzeExprs($stmt->cond, $history);
            ExprsAnalyzer::analyzeExprs($stmt->loop, $history);
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Foreach_) {
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }

        if ($stmt instanceof Function_) {
            self::analyzeStatements($stmt->stmts, $history);
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
            ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            self::analyzeStatements($stmt->stmts, $history);
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
            self::analyzeStatements($stmt->stmts, $history);
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
                ExprsAnalyzer::analyzeExpr($stmt->expr, $history);
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
            ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            foreach ($stmt->cases as $case) {
                self::analyzeStatement($case, $history);
            }
            return;
        }

        if ($stmt instanceof Throw_) {
            ExprsAnalyzer::analyzeExpr($stmt->expr, $history);
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
            self::analyzeStatements($stmt->stmts, $history);
            self::analyzeStatements($stmt->catches, $history);
            if ($stmt->finally !== null) {
                self::analyzeStatement($stmt->finally, $history);
            }
            return;
        }

        if ($stmt instanceof Unset_) {
            //not sur what could happen in here
            ExprsAnalyzer::analyzeExprs($stmt->vars, $history);
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
            ExprsAnalyzer::analyzeExpr($stmt->cond, $history);
            self::analyzeStatements($stmt->stmts, $history);
            return;
        }
    }

    /**
     * @param array<Expr|Stmt> $history
     */
    private static function customStmtHandling(Stmt $stmt, array $history): void
    {
        if ($stmt instanceof Return_) {
            $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);

            if($method_stmt !== null){
                $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                $method_storage = StrictTypesAnalyzer::$codebase->classlike_storage_provider->get($class_stmt->name->name)->methods[$method_stmt->name->name];
                $has_signature_return_type = $method_storage->signature_return_type !== null;
            }
            else{
                $function_stmt = NodeNavigator::getLastNodeByType($history, Function_::class);
                //TODO: handle function case
                $declared_return_type = 'unknown';
                $has_signature_return_type = true;
            }

            if (!$has_signature_return_type) {
                //This is not interesting, if there is no declared type, this can't be wrong with strict_types
                return;
            }

            //TODO: retrieve the type somehow and check compatibility
            //$inferred_return_type = StrictTypesAnalyzer::$statement_source->getFunctionLikeAnalyzer(new MethodIdentifier('A', 'test'))->getNodeTypeProvider();
            //var_dump($inferred_return_type);
            //var_dump('-'.spl_object_id($stmt));
            //$inferred_return_type= $inferred_return_type->getType($stmt);
            //var_dump($inferred_return_type);
            throw new NonStrictUsageException('Found Return_');
        }
    }
}
