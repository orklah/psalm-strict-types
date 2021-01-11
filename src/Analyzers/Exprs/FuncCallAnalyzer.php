<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use function array_slice;
use function count;

class FuncCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function analyze(FuncCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }
        $function_name = $expr->name;

        if ($function_name instanceof Name
            && isset($expr->args[0])
            && !$expr->args[0]->unpack
        ) {
            $original_function_id = implode('\\', $function_name->parts);

            if ($original_function_id === 'call_user_func') {
                $other_args = array_slice($expr->args, 1);

                $function_name = $expr->args[0]->value;

                $expr = new FuncCall(
                    $function_name,
                    $other_args,
                    $expr->getAttributes()
                );
            }

            if ($original_function_id === 'call_user_func_array' && isset($expr->args[1])) {
                $function_name = $expr->args[0]->value;

                $expr = new FuncCall(
                    $function_name,
                    [new Arg($expr->args[1]->value, false, true)],
                    $expr->getAttributes()
                );
            }
        }

        if ($function_name instanceof Expr) {
            throw NeedRefinementException::createWithNode('Found FuncCall with Expr as function name', $expr);
            //var_dump(StrictTypesHooks::$statement_source->getNodeTypeProvider()->getType($function_name));die();
        } else {
            $original_function_id = strtolower(implode('\\', $function_name->parts));

            if (!$function_name instanceof FullyQualified) {
                $function_id = StrictTypesHooks::$codebase->functions->getFullyQualifiedFunctionNameFromString(
                    $original_function_id,
                    StrictTypesHooks::$statement_source
                );
            } else {
                $function_id = $original_function_id;
            }
        }
        $function_id = strtolower($function_id);

        $function_from_stubs = true;
        $function_storage = StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id] ?? null;
        if ($function_storage === null) {
            $function_from_stubs = false;
            $function_storage = StrictTypesHooks::$function_storage_map[$function_id] ?? null;
        }

        if ($function_storage === null) {
            throw new ShouldNotHappenException('Could not retrieve function storage for ' . $function_id);
        }


        $function_params = $function_storage->params;

        $node_provider = StrictTypesHooks::$statement_source->getNodeTypeProvider();

        for ($i_param = 0, $i_paramMax = count($function_params); $i_param < $i_paramMax; $i_param++) {
            $param = $function_params[$i_param];

            if($function_from_stubs){
                // if the function is from the stubs, the location of the type is not relevant
                $return_type = $param->signature_type ?? $param->type;
            }
            else{
                $return_type = $param->signature_type;
            }

            if ($return_type !== null || $function_from_stubs) { //when the function is from stubs or callmap, the
                //TODO: beware of named params
                if (!isset($expr->args[$i_param])) {
                    // A param in signature is not specified in a call. Probably an optional param, if not, we don't care!
                    continue;
                }
                $arg = $expr->args[$i_param];
                $arg_type = $node_provider->getType($arg->value);
                if ($arg_type === null) {
                    //weird
                    throw new ShouldNotHappenException('Could not retrieve argument ' . ($i_param + 1) . ' for ' . $function_id);
                }

                if (!StrictUnionsChecker::strictUnionCheck($return_type, $arg_type)) {
                    throw NonStrictUsageException::createWithNode('Found argument ' . ($i_param + 1) . ' mismatching between ' . $return_type->getKey() . ' and ' . $arg_type->getKey(), $expr);
                }

                if ($arg_type->from_docblock === true) {
                    //not trustworthy enough
                    throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
                }
            }
        }
    }
}
