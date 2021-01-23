<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use function count;
use function get_class;

class New_Analyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function analyze(New_ $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        if($expr->class instanceof Name){
            $object = implode('\\', $expr->class->parts);
        }
        else{
            $class_type = $node_provider->getType($expr->class);
            if ($class_type !== null && $class_type->isSingleStringLiteral()) {
                $object = $class_type->getSingleStringLiteral()->value;
            }
            elseif ($class_type === null) {
                throw NeedRefinementException::createWithNode('Found New_ with a class that is an expr with unknown type ', $expr);
            }
            else {
                throw NeedRefinementException::createWithNode('Found New_ with a class that is a '. get_class($class_type), $expr);
            }
        }

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName(strtolower($object), '__construct');
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $object . '::__construct');
        }


        $method_params = $method_storage->params;
        try {
            StrictUnionsChecker::checkValuesAgainstParams($expr->args, $method_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Method ' . $method_storage->cased_name . ': ' . $e->getMessage(), 0, $e);
        }
    }
}
