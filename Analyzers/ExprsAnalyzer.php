<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Hooks\NonStrictUsageException;
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

class ExprsAnalyzer
{
    /**
     * @param Expr[] $exprs
     */
    public static function analyzeExprs(array $exprs): void
    {
        foreach ($exprs as $expr) {
            self::analyzeExpr($expr);
        }
    }

    public static function analyzeExpr(Expr $expr): void
    {
        //var_dump('saw a '.get_class($expr));
        if ($expr instanceof Array_) {
            //noop
            return;
        }

        if ($expr instanceof ArrayDimFetch) {
            //what's that?
            return;
        }

        if ($expr instanceof ArrayItem) {
            //what's that?
            return;
        }

        if ($expr instanceof ArrowFunction) {
            // TODO: probably something to do here
            return;
        }

        if ($expr instanceof Assign) {
            return;
        }

        if ($expr instanceof AssignRef) {
            return;
        }

        if ($expr instanceof BitwiseNot) {
            return;
        }

        if ($expr instanceof BooleanNot) {
            return;
        }

        if ($expr instanceof ClassConstFetch) {
            return;
        }

        if ($expr instanceof Clone_) {
            return;
        }

        if ($expr instanceof Closure) {
            return;
        }

        if ($expr instanceof ClosureUse) {
            return;
        }

        if ($expr instanceof ConstFetch) {
            return;
        }

        if ($expr instanceof Empty_) {
            return;
        }

        if ($expr instanceof Error) {
            return;
        }

        if ($expr instanceof ErrorSuppress) {
            return;
        }

        if ($expr instanceof Eval_) {
            return;
        }

        if ($expr instanceof Exit_) {
            return;
        }

        if ($expr instanceof FuncCall) {
            throw new NonStrictUsageException('Found FuncCall');
        }

        if ($expr instanceof Include_) {
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof Instanceof_) {
            return;
        }

        if ($expr instanceof Isset_) {
            return;
        }

        if ($expr instanceof List_) {
            return;
        }

        if ($expr instanceof Match_) {
            return;
        }

        if ($expr instanceof MethodCall) {
            throw new NonStrictUsageException('Found MethodCall');
        }

        if ($expr instanceof New_) {
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            throw new NonStrictUsageException('Found NullsafeMethodCall');
            return;
        }

        if ($expr instanceof NullsafePropertyFetch) {
            return;
        }

        if ($expr instanceof PostDec) {
            return;
        }

        if ($expr instanceof PostInc) {
            return;
        }

        if ($expr instanceof PreDec) {
            return;
        }

        if ($expr instanceof PreInc) {
            return;
        }

        if ($expr instanceof Print_) {
            return;
        }

        if ($expr instanceof PropertyFetch) {
            return;
        }

        if ($expr instanceof ShellExec) {
            return;
        }

        if ($expr instanceof StaticCall) {
            throw new NonStrictUsageException('Found StaticCall');
            return;
        }

        if ($expr instanceof StaticPropertyFetch) {
            return;
        }

        if ($expr instanceof Ternary) {
            ExprsAnalyzer::analyzeExpr($expr->cond);
            if($expr->if !== null) {
                ExprsAnalyzer::analyzeExpr($expr->if);
            }
            ExprsAnalyzer::analyzeExpr($expr->else);
            return;
        }

        if ($expr instanceof Throw_) {
            return;
        }

        if ($expr instanceof UnaryMinus) {
            return;
        }

        if ($expr instanceof UnaryPlus) {
            return;
        }

        if ($expr instanceof Variable) {
            return;
        }

        if ($expr instanceof Yield_) {
            return;
        }

        if ($expr instanceof YieldFrom) {
            return;
        }

        if ($expr instanceof AssignOp\BitwiseAnd) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseOr) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseXor) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Coalesce) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Concat) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Div) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Minus) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Mod) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Mul) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Plus) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\Pow) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\ShiftLeft) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof AssignOp\ShiftRight) {
            self::analyzeExpr($expr->var);
            self::analyzeExpr($expr->expr);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseAnd) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseOr) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseXor) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanAnd) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanOr) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Coalesce) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Concat) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Div) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Equal) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Greater) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\GreaterOrEqual) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Identical) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalAnd) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalOr) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalXor) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Minus) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Mod) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Mul) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\NotEqual) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\NotIdentical) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Plus) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Pow) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftLeft) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftRight) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Smaller) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\SmallerOrEqual) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
            return;
        }

        if ($expr instanceof BinaryOp\Spaceship) {
            self::analyzeExpr($expr->left);
            self::analyzeExpr($expr->right);
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
