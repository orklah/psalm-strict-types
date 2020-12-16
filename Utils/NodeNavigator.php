<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;

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
}
