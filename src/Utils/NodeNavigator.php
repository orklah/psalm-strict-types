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
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

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
     * $method_storage = $codebase->methods->getStorage($method_id);
     * TODO: investigate namespaces here
     */
    public static function getMethodStorageFromName(string $class_id, string $method_id): ?MethodStorage
    {
        $class_storage = StrictTypesHooks::$codebase->classlike_storage_provider->get($class_id);
        $method_storage = $class_storage->methods[$method_id] ?? null;
        if ($method_storage === null) {
            //We try on the parent
            foreach ($class_storage->parent_classes as $parent_class) {
                $method_storage = self::getMethodStorageFromName($parent_class, $method_id);
                if ($method_storage !== null) {
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
            $file_path = StrictTypesHooks::$file_storage->file_path;
            $class_name = strtolower($class_stmt->name->name);
            $method_name = strtolower($method_stmt->name->name);
            $node_provider = StrictTypesHooks::$node_type_providers_map[$file_path][$class_name][$method_name] ?? null;
            if ($node_provider === null) {
                //unable to fetch node provider. Throw
                throw new ShouldNotHappenException('Unable to retrieve Node Type Provider for ' . $class_name . '::' . $method_name);
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
        $file_path = StrictTypesHooks::$file_storage->file_path;
        $class_name = strtolower($class_stmt->name->name);
        $method_name = strtolower($method_stmt->name->name);
        $context = StrictTypesHooks::$context_map[$file_path][$class_name][$method_name] ?? null;
        if ($context === null) {
            //unable to context. Throw
            throw new ShouldNotHappenException('Unable to retrieve Context for ' . $class_name . '::' . $method_name);
        }

        return $context;
    }

    public static function addNamespacePrefix(string $namespace_prefix, string $class): string
    {
        if ($namespace_prefix === '') {
            return $class;
        }

        if (strpos($class, $namespace_prefix) === 0) {
            return $class;// classname already contains prefix
        } else {
            return $namespace_prefix . '\\' . $class;
        }
    }

    public static function transformParamTypeIntoCheckableType(?Union $union): ?Union
    {
        if ($union === null) {
            return null;
        }

        $atomic_types = $union->getAtomicTypes();
        $valid_union = true;
        foreach ($atomic_types as $atomic_type) {
            if($atomic_type instanceof TScalar){ continue; }
            if($atomic_type instanceof TString){ continue; }
            if($atomic_type instanceof TInt){ continue; }
            if($atomic_type instanceof TFloat){ continue; }
            if($atomic_type instanceof TBool){ continue; }

            if($atomic_type instanceof TResource){ continue; }

            if($atomic_type instanceof TNull){ continue; }

            if($atomic_type instanceof TArray){ continue; }
            if($atomic_type instanceof TList){ continue; }

            if($atomic_type instanceof TCallable){ continue; }

            if($atomic_type instanceof TMixed){ continue; }


            //TODO: TTemplateParam could be restricted to the upper type. In the meantime, not eligible
            if($atomic_type instanceof TTemplateParam){ $valid_union = false; break; }

            if (!$atomic_type->canBeFullyExpressedInPhp(StrictTypesHooks::$codebase->php_major_version, StrictTypesHooks::$codebase->php_minor_version)) {
                var_dump('==>type can be expressed and could be upgraded' . get_class($atomic_type));
                $valid_union = false;
                break;
            }
        }

        if ($valid_union) {
            return $union;
        }

        return null;
    }
}
