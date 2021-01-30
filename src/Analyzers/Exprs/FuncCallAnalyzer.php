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
            if ($function_id_type !== null && $function_id_type->isSingleStringLiteral()) {
                $function_id_types = $function_id_type->getAtomicTypes();
                $atomic_function_id_type = array_pop($function_id_types);
                Assert::isInstanceOf($atomic_function_id_type, TLiteralString::class);
                $function_id = $atomic_function_id_type->value;
            } else {
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

        if (isset(StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id]) || InternalCallMapHandler::inCallMap($function_id)) {
            $function_params = self::buildParamsFromStubsAndCallMap($function_id);
        } elseif (isset(StrictTypesHooks::$function_storage_map[$function_id])) {
            $function_params = StrictTypesHooks::$function_storage_map[$function_id]->params;
        } else {
            throw new ShouldNotHappenException('Could not retrieve params for function ' . $function_id);
        }

        try {
            StrictUnionsChecker::checkValuesAgainstParams($expr->args, $function_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Function ' . $function_id . ': ' . $e->getMessage(), 0, $e);
        }
    }

    private static function buildParamsFromStubsAndCallMap(string $function_id): array
    {
        $function_params_from_stubs = [];
        $callmap_callables = [];
        $final_params_array = [];
        $max_params = 0;
        if (isset(StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id])) {
            $function_storage = StrictTypesHooks::$codebase->functions->getAllStubbedFunctions()[$function_id];
            $function_params_from_stubs = $function_storage->params;
            $max_params = count($function_params_from_stubs);
        }

        if (InternalCallMapHandler::inCallMap($function_id)) {
            $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap($function_id);

            if ($callmap_callables === null) {
                throw new ShouldNotHappenException('Could not retrieve callmap function ' . $function_id);
            }

            foreach ($callmap_callables as $callable) {
                $tmp_max_params = count($callable->params);
                if ($tmp_max_params > $max_params) {
                    $max_params = $tmp_max_params;
                }
            }
        }

        for ($i = 0; $i < $max_params; $i++) {
            /*
             * we'll sort by order of preference:
             * expressible type from signature in stubs
             * expressible type in all callmaps (signature then doc)
             * expressible type in doc in stubs
             *
             * There may be an additional solution. For example, if the returned type is class-string everywhere, it's not expressible but it can be downgraded is string that is expressible
             */
            $stub_signature_type = null;
            if (isset($function_params_from_stubs[$i])) {
                $stub_signature_type = $function_params_from_stubs[$i]->signature_type;
            }
            $stub_doc_type = null;
            if (isset($function_params_from_stubs[$i])) {
                $stub_doc_type = $function_params_from_stubs[$i]->type;
            }
            $callmap_signature_type = null;
            $callmap_doc_type = null;

            $tmp_type_signature = null;
            $tmp_type_doc = null;
            $consistent_type_signature = true;
            $consistent_type_doc = true;
            $functionlike_parameter = null;
            foreach ($callmap_callables as $callmap_callable) {
                $functionlike_parameter = $callmap_callable->params[$i] ?? $function_storage->params[$i];
                if (isset($callmap_callable->params[$i])) {
                    if ($tmp_type_signature === null) {
                        $tmp_type_signature = $callmap_callable->params[$i]->signature_type;
                    } elseif (!$tmp_type_signature->equals($callmap_callable->params[$i]->signature_type)) {
                        $consistent_type_signature = false;
                    }

                    if ($tmp_type_doc === null) {
                        $tmp_type_doc = $callmap_callable->params[$i]->type;
                    } elseif (!$tmp_type_doc->equals($callmap_callable->params[$i]->type)) {
                        $consistent_type_doc = false;
                    }
                }
            }

            $stub_signature_checkable_type = NodeNavigator::transformParamTypeIntoCheckableType($stub_signature_type);
            $callmap_signature_checkable_type = $consistent_type_signature ? NodeNavigator::transformParamTypeIntoCheckableType($tmp_type_signature) : null;
            $callmap_phpdoc_checkable_type = $consistent_type_doc ? NodeNavigator::transformParamTypeIntoCheckableType($tmp_type_doc) : null;
            $stub_phpdoc_checkable_type = NodeNavigator::transformParamTypeIntoCheckableType($stub_doc_type);

            $type = $stub_signature_checkable_type ?? $callmap_signature_checkable_type ?? $callmap_phpdoc_checkable_type ?? $stub_phpdoc_checkable_type;
            if ($type === null) {
                throw new ShouldNotHappenException('No checkable type found for ' . $function_id);
            }

            $functionlike_parameter->signature_type = $type;
            $final_params_array[$i] = $functionlike_parameter;
        }

        return $final_params_array;
    }
}
