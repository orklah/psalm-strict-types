<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Exceptions;


use Exception;
use PhpParser\Node;
use Throwable;

/**
 * @psalm-consistent-constructor
 */
class NodeException extends Exception
{
    /** @var Node */
    private $node;

    private function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return static
     */
    public static function createWithNode(string $message, Node $node): self
    {
        $exception = new static($message);
        $exception->node = $node;
        return $exception;
    }

    /**
     * @return Node
     */
    public function getNode(): Node
    {
        return $this->node;
    }
}
