<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Exprs;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use function count;

class New_Analyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     */
    public static function analyze(FileContext $file_context, New_ $expr, array $history): void
    {
        if (count($expr->args) === 0) {
            //no params. Easy
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($file_context, $history);

        if($expr->class instanceof Name){
            if($expr->class->parts[0] === 'self') {
                $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                $object_name = $class_stmt->name->name;
            } elseif ($expr->class->parts[0] === 'static') {
                //TODO: technically, we should check childrens but covariance/contravariance rules states all childrens will accept as least what the parent accepts so it's okay to check parent
                $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
                $object_name = $class_stmt->name->name;
            } else {
                $object_name = $expr->class;
            }
        } elseif ($expr->class instanceof Class_) {
            $object_name = (string) $expr->class->name;
        } else{
            $object_type = $node_provider->getType($expr->class);
            $object_name = NodeNavigator::reduceUnionToString($object_type, $expr);
        }

        //Ok, we have a single object here. Time to fetch parameters from method
        $method_storage = NodeNavigator::getMethodStorageFromName($file_context, strtolower(NodeNavigator::resolveName($file_context, $history, $object_name)), '__construct');
        if ($method_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Method Storage for ' . $object_name . '::__construct');
        }


        $method_params = $method_storage->params;
        try {
            StrictUnionsChecker::checkValuesAgainstParams($file_context, $expr->args, $method_params, $node_provider, $expr);
        } catch (ShouldNotHappenException $e) {
            throw new ShouldNotHappenException('Method ' . $method_storage->cased_name . ': ' . $e->getMessage(), 0, $e);
        }
    }
}
