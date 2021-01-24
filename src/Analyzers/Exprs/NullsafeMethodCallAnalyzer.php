<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function is_string;

class NullsafeMethodCallAnalyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     */
    public static function analyze(NullsafeMethodCall $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        if ($expr->name instanceof Identifier) {
            $method_name = $expr->name->name;
        } elseif ($expr->name instanceof Expr) {
            $method_name_type = $node_provider->getType($expr->name);
            if ($method_name_type !== null && $method_name_type->isSingleStringLiteral()) {
                $method_name = $method_name_type->getSingleStringLiteral()->value;
            } elseif ($method_name_type === null) {
                throw NeedRefinementException::createWithNode('Found MethodCall with a method that is an expr with unknown type ', $expr);
            } else {
                throw NeedRefinementException::createWithNode('Found MethodCall with a method that is a ' . get_class($method_name_type), $expr);
            }
        } else {
            $method_name = $expr->name;
        }

        //object context, we fetch the node type provider or the context if the variable is $this
        if (is_string($expr->var->name) && $expr->var->name === 'this') {
            $context = NodeNavigator::getContext($history);
            $object_type = $context->vars_in_scope['$this'];
        } else {
            $object_type = $node_provider->getType($expr->var);
        }

        if ($object_type === null) {
            //unable to identify object. Throw
            throw new ShouldNotHappenException('Unable to retrieve object type for "' . $expr->var->name . '"');
        }

        if (!$object_type->isSingle()) {
            //multiple object/types. Throw for now, but may be refined
            //TODO: try to refine (object with common parents, same parameters etc...)
            throw NeedRefinementException::createWithNode('Found Found Multiple objects possible for one call', $expr);
        }

        if (!$object_type->isObjectType()) {
            //How is that even possible? TODO: Find out if cases exists
            throw NeedRefinementException::createWithNode('Found a ' . $object_type->getKey() . ' for a method call', $expr);
        }

        //we may remove null safely, this is not what we're checking here
        $object_type->removeType('null');
        $object_types = $object_type->getAtomicTypes();
        $atomic_object_type = array_pop($object_types);
        if (!$atomic_object_type instanceof TNamedObject) {
            //TODO: check if we could refine it with TObject or TTemplateParam
            throw NeedRefinementException::createWithNode('Found MethodCall5', $expr);
        }

        $namespace_stmt = NodeNavigator::getLastNodeByType($history, Namespace_::class);
        $namespace_prefix = '';
        if ($namespace_stmt !== null) {
            $namespace_prefix = (string)$namespace_stmt->name;
        }

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName(strtolower(NodeNavigator::addNamespacePrefix($namespace_prefix, $atomic_object_type->value)), strtolower($method_name));
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $atomic_object_type->value . '::' . $method_name);
        }

        $method_params = $method_storage->params;
        try {
            StrictUnionsChecker::checkValuesAgainstParams($expr->args, $method_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Method ' . $method_name . ': ' . $e->getMessage(), 0, $e);
        }

        //every potential mismatch would have been handled earlier
    }
}
