<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers;

use Orklah\StrictTypes\Hooks\NonStrictUsageException;
use Orklah\StrictTypes\Hooks\StrictTypesAnalyzer;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
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
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Stmt;
use Psalm\Type\Atomic\TNamedObject;
use function count;

class ExprsAnalyzer
{
    /**
     * @param Expr[]          $exprs
     * @param list<Stmt|Expr> $history
     */
    public static function analyzeExprs(array $exprs, array $history): void
    {
        foreach ($exprs as $expr) {
            if($expr !== null) {
                self::analyzeExpr($expr, $history);
            }
        }
    }

    /**
     * @param list<Stmt|Expr> $history
     */
    public static function analyzeExpr(Expr $expr, array $history): void
    {
        //var_dump('seen ' . get_class($expr));
        $history[] = $expr;
        self::customExprHandling($expr, $history);

        if ($expr instanceof Array_) {
            if ($expr->items !== null) {
                self::analyzeExprs($expr->items, $history);
            }
            return;
        }

        if ($expr instanceof ArrayDimFetch) {
            //what's that?
            return;
        }

        if ($expr instanceof ArrayItem) {
            self::analyzeExpr($expr->value, $history);
            if ($expr->key !== null) {
                self::analyzeExpr($expr->key, $history);
            }
            return;
        }

        if ($expr instanceof ArrowFunction) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Assign) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignRef) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BitwiseNot) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BooleanNot) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof ClassConstFetch) {
            return;
        }

        if ($expr instanceof Clone_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Closure) {
            StmtsAnalyzer::analyzeStatements($expr->stmts, $history);
            return;
        }

        if ($expr instanceof ClosureUse) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof ConstFetch) {
            return;
        }

        if ($expr instanceof Empty_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Error) {
            return;
        }

        if ($expr instanceof ErrorSuppress) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Eval_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Exit_) {
            if($expr->expr !== null) {
                self::analyzeExpr($expr->expr, $history);
            }
            return;
        }

        if ($expr instanceof FuncCall) {
            return;
        }

        if ($expr instanceof Include_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Instanceof_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Isset_) {
            self::analyzeExprs($expr->vars, $history);
            return;
        }

        if ($expr instanceof List_) {
            return;
        }

        if ($expr instanceof Match_) {
            self::analyzeExpr($expr->cond, $history);
            return;
        }

        if ($expr instanceof MethodCall) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof New_) {
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof NullsafePropertyFetch) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PostDec) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PostInc) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PreDec) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof PreInc) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof Print_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof PropertyFetch) {
            self::analyzeExpr($expr->var, $history);
            return;
        }

        if ($expr instanceof ShellExec) {
            return;
        }

        if ($expr instanceof StaticCall) {
            return;
        }

        if ($expr instanceof StaticPropertyFetch) {
            return;
        }

        if ($expr instanceof Ternary) {
            ExprsAnalyzer::analyzeExpr($expr->cond, $history);
            if ($expr->if !== null) {
                ExprsAnalyzer::analyzeExpr($expr->if, $history);
            }
            ExprsAnalyzer::analyzeExpr($expr->else, $history);
            return;
        }

        if ($expr instanceof Throw_) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof UnaryMinus) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof UnaryPlus) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof Variable) {
            return;
        }

        if ($expr instanceof Yield_) {
            return;
        }

        if ($expr instanceof YieldFrom) {
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseAnd) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseOr) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\BitwiseXor) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Coalesce) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Concat) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Div) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Minus) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Mod) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Mul) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Plus) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\Pow) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\ShiftLeft) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof AssignOp\ShiftRight) {
            self::analyzeExpr($expr->var, $history);
            self::analyzeExpr($expr->expr, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseAnd) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseOr) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BitwiseXor) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanAnd) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\BooleanOr) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Coalesce) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Concat) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Div) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Equal) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Greater) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\GreaterOrEqual) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Identical) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalAnd) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalOr) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\LogicalXor) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Minus) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Mod) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Mul) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\NotEqual) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\NotIdentical) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Plus) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Pow) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftLeft) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\ShiftRight) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Smaller) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\SmallerOrEqual) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
            return;
        }

        if ($expr instanceof BinaryOp\Spaceship) {
            self::analyzeExpr($expr->left, $history);
            self::analyzeExpr($expr->right, $history);
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
            self::analyzeExprs($expr->parts, $history);
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

    /**
     * @param array<Expr|Stmt> $history
     */
    private static function customExprHandling(Expr $expr, array $history): void
    {
        //custom plugin code here
        if ($expr instanceof ArrowFunction) {
            $has_params = false;
            //$has_at_least_one_typed_param = false;
            if (count($expr->params) !== 0) {
                $has_params = true;
                /** Checking this seems difficult. Aborting for now
                 * $has_at_least_one_typed_param = true;
                 * foreach($expr->params as $param) {
                 *    var_dump($param->type);
                 *     if ($param->type !== null && $param->type->from_docblock === false) {
                 *         //TODO: check with actual types
                 *         $has_at_least_one_typed_param = true;
                 *     }
                 * }
                 */
            }

            $has_return = $expr->returnType !== null;
            /** Checking this seems difficult. Aborting for now
             * $has_typed_return = $expr->returnType->from_docblock === false;
             */
            if (!$has_params && !$has_return) {
                return;
            }

            throw new NonStrictUsageException('Found ArrowFunction');
        }

        if ($expr instanceof Closure) {
            $has_params = false;
            //$has_at_least_one_typed_param = false;
            if (count($expr->params) !== 0) {
                $has_params = true;
                /** Checking this seems difficult. Aborting for now
                 * $has_at_least_one_typed_param = true;
                 * foreach($expr->params as $param) {
                 *    var_dump($param->type);
                 *     if ($param->type !== null && $param->type->from_docblock === false) {
                 *         //TODO: check with actual types
                 *         $has_at_least_one_typed_param = true;
                 *     }
                 * }
                 */
            }

            if (!$has_params) {
                return;
            }

            throw new NonStrictUsageException('Found Closure');
        }

        if ($expr instanceof FuncCall) {
            if (count($expr->args) === 0) {
                //no params. Easy
                return;
            }

            $namespace_stmt = NodeNavigator::getLastNodeByType($history, Stmt\Namespace_::class);
            $namespace_id = $namespace_stmt->name->parts[0] ?? '';
            $function_id = $expr->name->parts[0];

            //The function called was in the same file. This is lucky. Otherwise I don't know where I could fetch the function
            if (isset(StrictTypesAnalyzer::$file_storage->functions[$namespace_id . '\\' . $function_id])) {
                $has_at_least_one_typed_param = false;
                foreach (StrictTypesAnalyzer::$file_storage->functions[$namespace_id . '\\' . $function_id]->params as $param) {
                    if ($param->signature_type !== null) {
                        //TODO: check with actual types
                        $has_at_least_one_typed_param = true;
                    }
                }

                if (!$has_at_least_one_typed_param) {
                    return;
                }
            } else {
                //TODO: find where the function could be stored and check with actual params
            }

            throw new NonStrictUsageException('Found FuncCall');
        }

        if ($expr instanceof MethodCall) {
            if (count($expr->args) === 0) {
                //no params. Easy
                return;
            }

            if(!$expr->name instanceof Identifier){
                //can't handle this for now TODO: refine this
                throw new NonStrictUsageException('Found MethodCall1');
            }

            $object = StrictTypesAnalyzer::$statement_source->getNodeTypeProvider()->getType($expr->var);

            if($object === null){
                //unable to identify object. Throw
                throw new NonStrictUsageException('Found MethodCall2');
            }

            if(!$object->isSingle()){
                //multiple object/types. Throw for now, but may be refined
                //TODO: try to refine (object with common parents, same parameters etc...)
                throw new NonStrictUsageException('Found MethodCall3');
            }

            if(!$object->isObjectType()){
                //How is that even possible? TODO: Find out if cases exists
                throw new NonStrictUsageException('Found MethodCall4');
            }

            //we may remove null safely, this is not what we're checking here
            $object->removeType('null');
            $object_types = $object->getAtomicTypes();
            $object_type = array_pop($object_types);
            if(!$object_type instanceof TNamedObject){
                //TODO: check if we could refine it with TObject or TTemplateParam
                throw new NonStrictUsageException('Found MethodCall5');
            }

            //Ok, we have a single object here. Time to fetch parameters from method
            $class_storage = StrictTypesAnalyzer::$codebase->classlike_storage_provider->get($object_type->value);
            $method_storage = $class_storage->methods[strtolower($expr->name->name)];
            if($method_storage === null){
                //weird.
                throw new NonStrictUsageException('Found MethodCall6');
            }

            $method_params = $method_storage->params;
            for($i_param = 0, $i_paramMax = count($method_params); $i_param < $i_paramMax; $i_param++){
                $param = $method_params[$i_param];
                if ($param->signature_type !== null) {

                    //TODO: beware of named params
                    $arg = $expr->args[$i_param];
                    $arg_type = StrictTypesAnalyzer::$statement_source->getNodeTypeProvider()->getType($arg->value);
                    if($arg_type === null){
                        //weird
                        throw new NonStrictUsageException('Found MethodCall7');
                    }

                    if(!StrictUnionsChecker::strictUnionCheck($param->signature_type, $arg_type)){
                        throw new NonStrictUsageException('Found MethodCall10 with argument '.($i_param+1).' mismatching');
                    }

                    if($arg_type->from_docblock === true){
                        //not trustworthy enough
                        throw new NonStrictUsageException('Found MethodCall8');
                    }
                }
            }

            //every potential mismatch would have been handled earlier
            return;
        }

        if ($expr instanceof NullsafeMethodCall) {
            if (count($expr->args) === 0) {
                //no params. Easy
                return;
            }

            //identify object, identify method, identify params
            throw new NonStrictUsageException('Found NullsafeMethodCall');
        }

        if ($expr instanceof StaticCall) {
            if (count($expr->args) === 0) {
                //no params. Easy
                return;
            }

            //identify object, identify method, identify params
            throw new NonStrictUsageException('Found StaticCall');
        }

        if ($expr instanceof Assign) {
            //TODO: possible false positive: this doesn't handle the __set() magic method
            if (
                !$expr->var instanceof StaticPropertyFetch &&
                !$expr->var instanceof PropertyFetch
            ) {
                // only properties can be typed
                return;
            }

            //find the class and check if the property have a type, then compare with given type
            throw new NonStrictUsageException('Found Assign with StaticPropertyFetch');
        }

        if ($expr instanceof New_) {
            if (count($expr->args) === 0) {
                //no params. Easy
                return;
            }

            //identify object, identify constructor, identify params
            throw new NonStrictUsageException('Found New_');
        }
    }
}
