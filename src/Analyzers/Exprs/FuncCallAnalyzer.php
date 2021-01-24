<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Type\Atomic\TLiteralString;
use Webmozart\Assert\Assert;
use function array_slice;
use function count;
use function gettype;

class FuncCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NeedRefinementException
     * @throws NonStrictUsageException
     * @throws NonVerifiableStrictUsageException
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

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        if ($function_name instanceof Expr) {
            $function_id_type = StrictTypesHooks::$statement_source->getNodeTypeProvider()->getType($function_name);
            if($function_id_type !== null && $function_id_type->isSingleStringLiteral()){
                $function_id_types = $function_id_type->getAtomicTypes();
                $atomic_function_id_type = array_pop($function_id_types);
                Assert::isInstanceOf($atomic_function_id_type, TLiteralString::class);
                $function_id = $atomic_function_id_type->value;
            }
            else{
                throw NeedRefinementException::createWithNode('Found FuncCall with ' . gettype($function_id_type) . ' as function name', $expr);
            }
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

        $native_function = false;
        $function_storage = null;
        $function_params = null;
        if (isset(StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id])) {
            $native_function = true;
            $function_storage = StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id];
            $function_params = $function_storage->params;
        }

        if ($function_storage === null && InternalCallMapHandler::inCallMap($function_id)) {
            $native_function = true;
            $callables = InternalCallMapHandler::getCallablesFromCallMap($function_id);

            if ($callables === null) {
                throw new ShouldNotHappenException('Could not retrieve callmap function ' . $function_id);
            }

            if (count($callables) !== 1) {
                throw NeedRefinementException::createWithNode('Multiple function storage for ' . $function_id . ' retrieved', $expr);
            }

            $function_params = $callables[0]->params;
        }

        if ($function_storage === null && isset(StrictTypesHooks::$function_storage_map[$function_id])) {
            $native_function = false;
            $function_storage = StrictTypesHooks::$function_storage_map[$function_id];
            $function_params = $function_storage->params;
        }

        if ($function_params === null) {
            throw new ShouldNotHappenException('Could not retrieve params for function ' . $function_id);
        }


        try {
            StrictUnionsChecker::checkValuesAgainstParams($expr->args, $function_params, $node_provider, $expr, $native_function);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Function ' . $function_id . ': ' . $e->getMessage(), 0, $e);
        }
    }
}
