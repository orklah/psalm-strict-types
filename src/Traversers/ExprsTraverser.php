<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Traversers;

use Orklah\StrictTypes\Analyzers\ExprsAnalyzer;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\BadTypeFromSignatureException;
use Orklah\StrictTypes\Exceptions\GoodTypeFromDocblockException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BitwiseNot;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\ShellExec;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Stmt;

class ExprsTraverser
{
    /**
     * @param array<Expr|null>          $exprs
     * @param list<Stmt|Expr> $history
     * @throws NeedRefinementException
     * @throws BadTypeFromSignatureException
     * @throws GoodTypeFromDocblockException
     * @throws ShouldNotHappenException
     */
    public static function traverseExprs(array $exprs, array $history): void
    {
        foreach ($exprs as $expr) {
            if($expr !== null) {
                self::traverseExpr($expr, $history);
            }
        }
    }

    /**
     * @param list<Stmt|Expr> $history
     * @throws NeedRefinementException
     * @throws BadTypeFromSignatureException
     * @throws GoodTypeFromDocblockException
     * @throws ShouldNotHappenException
     */
    public static function traverseExpr(Expr $expr, array $history): void
    {
        //var_dump('seen ' . get_class($expr));
        $history[] = $expr;
        ExprsAnalyzer::customExprHandling($expr, $history);

        if ($expr instanceof Array_) {
            self::traverseExprs($expr->items, $history);
            return;
        }

        if ($expr instanceof ArrayDimFetch) {
            //what's that?
            return;
        }

        if ($expr instanceof ArrayItem) {
            self::traverseExpr($expr->value, $history);
            if ($expr->key !== null) {
                self::traverseExpr($expr->key, $history);
            }
            return;
        }

        if ($expr instanceof ArrowFunction) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Assign) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignRef) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BitwiseNot) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BooleanNot) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof ClassConstFetch) {
            return;
        }

        if ($expr instanceof Clone_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Closure) {
            StmtsTraverser::traverseStatements($expr->stmts, $history);
            return;
        }

        if ($expr instanceof ClosureUse) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof ConstFetch) {
            return;
        }

        if ($expr instanceof Empty_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Error) {
            return;
        }

        if ($expr instanceof ErrorSuppress) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Eval_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Exit_) {
            if($expr->expr !== null) {
                self::traverseExpr($expr->expr, $history);
            }
            return;
        }

        if ($expr instanceof FuncCall) {
            foreach ($expr->args as $arg) {
                self::traverseExpr($arg->value, $history);
            }
            return;
        }

        if ($expr instanceof Include_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Instanceof_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Isset_) {
            self::traverseExprs($expr->vars, $history);
            return;
        }

        if ($expr instanceof List_) {
            return;
        }

        if ($expr instanceof Match_) {
            self::traverseExpr($expr->cond, $history);
            return;
        }

        if ($expr instanceof MethodCall) {
            self::traverseExpr($expr->var, $history);
            foreach ($expr->args as $arg) {
                self::traverseExpr($arg->value, $history);
            }
            return;
        }

        if ($expr instanceof New_) {
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            self::traverseExpr($expr->var, $history);
            foreach ($expr->args as $arg) {
                self::traverseExpr($arg->value, $history);
            }
            return;
        }

        if ($expr instanceof NullsafePropertyFetch) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PostDec) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PostInc) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PreDec) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PreInc) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof Print_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof PropertyFetch) {
            self::traverseExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof ShellExec) {
            return;
        }

        if ($expr instanceof StaticCall) {
            foreach ($expr->args as $arg) {
                self::traverseExpr($arg->value, $history);
            }
            return;
        }

        if ($expr instanceof StaticPropertyFetch) {
            return;
        }

        if ($expr instanceof Ternary) {
            self::traverseExpr($expr->cond, $history);
            if ($expr->if !== null) {
                self::traverseExpr($expr->if, $history);
            }
            self::traverseExpr($expr->else, $history);
            return;
        }

        if ($expr instanceof Throw_) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof UnaryMinus) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof UnaryPlus) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Variable) {
            return;
        }

        if ($expr instanceof Yield_) {
            return;
        }

        if ($expr instanceof YieldFrom) {
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseAnd) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseOr) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseXor) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Coalesce) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Concat) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Div) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Minus) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Mod) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Mul) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Plus) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Pow) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\ShiftLeft) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\ShiftRight) {
            self::traverseExpr($expr->var, $history);
            self::traverseExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseAnd) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseOr) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseXor) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanAnd) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanOr) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Coalesce) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Concat) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Div) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Equal) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Greater) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\GreaterOrEqual) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Identical) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalAnd) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalOr) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalXor) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Minus) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Mod) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Mul) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\NotEqual) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\NotIdentical) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Plus) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Pow) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftLeft) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftRight) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Smaller) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\SmallerOrEqual) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Spaceship) {
            self::traverseExpr($expr->left, $history);
            self::traverseExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof Cast\Array_) {
            return;
        }

        if ($expr instanceof Cast\Bool_) {
            return;
        }

        if ($expr instanceof Cast\Double) {
            return;
        }

        if ($expr instanceof Cast\Int_) {
            return;
        }

        if ($expr instanceof Cast\Object_) {
            return;
        }

        if ($expr instanceof Cast\String_) {
            return;
        }

        if ($expr instanceof Cast\Unset_) {
            return;
        }

        if ($expr instanceof Scalar\DNumber) {
            return;
        }

        if ($expr instanceof Scalar\Encapsed) {
            self::traverseExprs($expr->parts, $history);
            return;
        }

        if ($expr instanceof Scalar\EncapsedStringPart) {
            return;
        }

        if ($expr instanceof Scalar\LNumber) {
            return;
        }

        if ($expr instanceof Scalar\MagicConst) {
            return;
        }

        if ($expr instanceof Scalar\String_) {
            return;
        }

        if ($expr instanceof MagicConst\Class_) {
            return;
        }

        if ($expr instanceof MagicConst\Dir) {
            return;
        }

        if ($expr instanceof MagicConst\File) {
            return;
        }

        if ($expr instanceof MagicConst\Function_) {
            return;
        }

        if ($expr instanceof MagicConst\Line) {
            return;
        }

        if ($expr instanceof MagicConst\Method) {
            return;
        }

        if ($expr instanceof MagicConst\Namespace_) {
            return;
        }

        if ($expr instanceof MagicConst\Trait_) {
            return;
        }
    }
}
