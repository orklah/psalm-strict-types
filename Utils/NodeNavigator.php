<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use Orklah\StrictTypes\Hooks\StrictTypesAnalyzer;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use Psalm\Storage\MethodStorage;

class NodeNavigator
{
    /**
     * @template T of NodeAbstract
     * @param array<Stmt|Expr> $history
     * @param class-string<T>  $nodeType
     * @return T|null
     */
    public static function getLastNodeByType(array $history, string $nodeType)
    {
        while ($node = array_pop($history)) {
            if ($node instanceof $nodeType) {
                return $node;
            }
        }
        return null;
    }

    public static function getMethodStorageFromName(string $class_id, string $method_id): ?MethodStorage{
        $class_storage = StrictTypesAnalyzer::$codebase->classlike_storage_provider->get($class_id);
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
}
