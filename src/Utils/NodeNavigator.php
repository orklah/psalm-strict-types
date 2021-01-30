<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Orklah\StrictTypes\Analyzers\Stmts\Use_Analyzer;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
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

    /**
     * We receive the node history and the class string. We'll go fetch the Use_ map and reconstruct the FQCN here
     * @param array<Expr|Stmt> $history
     * @param string|Name      $class
     */
    public static function resolveName(array $history, $class): string
    {
        if ($class instanceof Name) {
            $resolved_name = $class->getAttribute('resolvedName');
            if($resolved_name !== null){
                return $resolved_name;
            }
            else{
                $class = implode('\\', $class->parts);
            }
        }

        if (strpos($class, '\\') === 0) {
            //the path is absolute, we return the given path
            return $class;
        }

        $file_path = StrictTypesHooks::$statement_source->getFileAnalyzer()->getFilePath();
        $uses = Use_Analyzer::$use_map[$file_path] ?? [];

        $exploded_class = explode('\\', $class);
        $first_part = strtolower($exploded_class[0]);
        $final_class = $class;
        $found_use = false;
        while (isset($uses[$first_part])) {
            $use = $uses[$first_part];
            $found_use = true;
            $final_class = implode('\\', $use) . '\\' . $final_class;
            $first_part = strtolower(array_shift($use));
        }

        if ($found_use) {
            return $final_class;
        }

        //we retrieve the current namespace. It will prefix the class unless it begins with a known Use
        $namespace_stmt = self::getLastNodeByType($history, Namespace_::class);
        $namespace_prefix = '';
        if ($namespace_stmt !== null) {
            $namespace_prefix = (string)$namespace_stmt->name;
        }

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
            if ($atomic_type instanceof TScalar) {
                continue;
            }
            if ($atomic_type instanceof TString) {
                continue;
            }
            if ($atomic_type instanceof TInt) {
                continue;
            }
            if ($atomic_type instanceof TFloat) {
                continue;
            }
            if ($atomic_type instanceof TBool) {
                continue;
            }

            if ($atomic_type instanceof TResource) {
                continue;
            }

            if ($atomic_type instanceof TNull) {
                continue;
            }

            if ($atomic_type instanceof TArray) {
                continue;
            }
            if ($atomic_type instanceof TList) {
                continue;
            }

            if ($atomic_type instanceof TCallable) {
                continue;
            }

            if ($atomic_type instanceof TMixed) {
                continue;
            }


            //TODO: TTemplateParam could be restricted to the upper type. In the meantime, not eligible
            if ($atomic_type instanceof TTemplateParam) {
                $valid_union = false;
                break;
            }

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
