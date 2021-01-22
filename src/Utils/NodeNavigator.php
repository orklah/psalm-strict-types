<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use Psalm\Context;
use Psalm\NodeTypeProvider;
use Psalm\Storage\MethodStorage;

class NodeNavigator
{
    /**
     * @template T of NodeAbstract
     * @param array<Stmt|Expr> $history
     * @param class-string<T>  $nodeType
     * @return T|null
     */
    public static function getLastNodeByType(array $history, string $nodeType): ?NodeAbstract
    {
        while ($node = array_pop($history)) {
            if ($node instanceof $nodeType) {
                return $node;
            }
        }
        return null;
    }

    /**
     * TODO, investigate
     *  $method_id = new MethodIdentifier(...explode('::', $declaring_method_id));
    $method_storage = $codebase->methods->getStorage($method_id);
     */
    public static function getMethodStorageFromName(string $class_id, string $method_id): ?MethodStorage{
        $class_storage = StrictTypesHooks::$codebase->classlike_storage_provider->get($class_id);
        $method_storage = $class_storage->methods[$method_id] ?? null;
        if($method_storage === null){
            //We try on the parent
            foreach($class_storage->parent_classes as $parent_class){
                $method_storage = self::getMethodStorageFromName($parent_class, $method_id);
                if($method_storage !== null){
                    break;
                }
            }
        }
        return $method_storage;
    }

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     */
    public static function getNodeProviderFromContext(array $history): NodeTypeProvider
    {
        $method_stmt = self::getLastNodeByType($history, ClassMethod::class);
        $class_stmt = self::getLastNodeByType($history, Class_::class);
        if ($class_stmt !== null && $method_stmt !== null) {
            //object context, we fetch the node type provider
            $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
            if ($node_provider === null) {
                //unable to fetch node provider. Throw
                throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
            }
        } else {
            //outside of object context, standard node type provider should be enough
            $node_provider = StrictTypesHooks::$statement_source->getNodeTypeProvider();
        }

        return $node_provider;
    }

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     */
    public static function getContext(array $history): Context
    {
        $method_stmt = self::getLastNodeByType($history, ClassMethod::class);
        $class_stmt = self::getLastNodeByType($history, Class_::class);
        $context = StrictTypesHooks::$context_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
        if ($context === null) {
            //unable to context. Throw
            throw new ShouldNotHappenException('Unable to retrieve Context');
        }

        return $context;
    }
}
