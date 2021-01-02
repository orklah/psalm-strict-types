<?php


namespace Orklah\StrictTypes\Exceptions;


use Exception;
use PhpParser\Node;
use Throwable;

class NodeException extends Exception
{
    /** @var Node */
    private $node;

   private function __construct(string $message = '', int $code = 0, Throwable $previous = null)
   {
       parent::__construct($message, $code, $previous);
   }

    public static function createWithNode(string $message, Node $node): self{
        $exception = new self($message);
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
