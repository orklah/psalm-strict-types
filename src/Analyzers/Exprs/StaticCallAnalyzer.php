<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Type\Atomic\TNamedObject;
use function count;

class StaticCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     * @throws NeedRefinementException
     * @throws NonVerifiableStrictUsageException
     */
    public static function analyze(StaticCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        if (!$expr->name instanceof Identifier) {
            //can't handle this for now TODO: refine this
            throw NeedRefinementException::createWithNode('Unable to analyze a non-literal method', $expr);
        }

        $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);
        $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
        if ($class_stmt !== null && $method_stmt !== null) {
            //object context, we fetch the node type provider
            $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
            if ($node_provider === null) {
                //unable to fetch node provider. Throw
                throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
            }
            $object_type = $node_provider->getType($expr->class);
        } else {
            //outside of object context, standard node type provider should be enough
            $node_provider = StrictTypesHooks::$statement_source->getNodeTypeProvider();

            $object_type = $node_provider->getType($expr->class);
        }

        if ($object_type === null) {
            //unable to identify object. Throw
            throw new ShouldNotHappenException('Unable to retrieve object type');
        }

        if (!$object_type->isSingle()) {
            //multiple object/types. Throw for now, but may be refined
            //TODO: try to refine (object with common parents, same parameters etc...)
            throw NeedRefinementException::createWithNode('Found MethodCall3', $expr);
        }

        if (!$object_type->isObjectType()) {
            //How is that even possible? TODO: Find out if cases exists
            throw NeedRefinementException::createWithNode('Found MethodCall4', $expr);
        }

        //we may remove null safely, this is not what we're checking here
        $object_type->removeType('null');
        $object_types = $object_type->getAtomicTypes();
        $atomic_object_type = array_pop($object_types);
        if (!$atomic_object_type instanceof TNamedObject) {
            //TODO: check if we could refine it with TObject or TTemplateParam
            throw NeedRefinementException::createWithNode('Found MethodCall5', $expr);
        }

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName(strtolower($atomic_object_type->value), strtolower($expr->name->name));
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $atomic_object_type->value . '::' . $expr->name->name);
        }

        $method_params = $method_storage->params;
        for ($i_param = 0, $i_paramMax = count($method_params); $i_param < $i_paramMax; $i_param++) {
            $param = $method_params[$i_param];
            if ($param->signature_type !== null) {
                //TODO: beware of named params
                if (!isset($expr->args[$i_param])) {
                    // A param in signature is not specified in a call. Probably an optional param, if not, we don't care!
                    continue;
                }
                $arg = $expr->args[$i_param];
                $arg_type = $node_provider->getType($arg->value);
                if ($arg_type === null) {
                    //weird
                    throw new ShouldNotHappenException('Could not retrieve argument ' . ($i_param + 1) . ' for ' . $method_storage->cased_name);
                }

                if (!StrictUnionsChecker::strictUnionCheck($param->signature_type, $arg_type)) {
                    throw NonStrictUsageException::createWithNode('Found argument ' . ($i_param + 1) . ' mismatching between ' . $param->signature_type->getKey() . ' and ' . $arg_type->getKey(), $expr);
                }

                if ($arg_type->from_docblock === true) {
                    //not trustworthy enough
                    throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $expr);
                }
            }
        }

        //every potential mismatch would have been handled earlier
    }
}
