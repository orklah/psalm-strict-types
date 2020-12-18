<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Stmts;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;

class Return_Analyzer{

    /**
     * @param array<Expr|Stmt> $history
     * @throws NonStrictUsageException
     * @throws ShouldNotHappenException
     */
    public static function analyze(Return_ $stmt, array $history): void
    {
        $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);

        if($method_stmt !== null){
            $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
            $class_storage = StrictTypesHooks::$codebase->classlike_storage_provider->get($class_stmt->name->name);
            $method_storage = $class_storage->methods[strtolower($method_stmt->name->name)] ?? null;
            if($method_storage === null){
                //weird.
                throw new ShouldNotHappenException('Found Return_');
            }
            $has_signature_return_type = $method_storage->signature_return_type !== null;
        }
        else{
            $function_stmt = NodeNavigator::getLastNodeByType($history, Function_::class);
            $function_storage = StrictTypesHooks::$file_storage->functions[strtolower((string)$function_stmt->name->name)] ?? null;
            if($function_storage === null){
                //weird.
                throw new ShouldNotHappenException('Found Return_');
            }
            $has_signature_return_type = $function_storage->signature_return_type !== null;
        }

        if (!$has_signature_return_type) {
            //This is not interesting, if there is no declared type, this can't be wrong with strict_types
            return;
        }

        //$type = StrictTypesHooks::$statement_source->getNodeTypeProvider()->getType($stmt->expr);
        //var_dump($type);

        //TODO: retrieve the type somehow and check compatibility
        //$inferred_return_type = StrictTypesHooks::$statement_source->getFunctionLikeAnalyzer(new MethodIdentifier('A', 'test'))->getNodeTypeProvider();
        //var_dump($inferred_return_type);
        //var_dump('-'.spl_object_id($stmt));
        //$inferred_return_type= $inferred_return_type->getType($stmt);
        //var_dump($inferred_return_type);
        throw new NonStrictUsageException('Found Return_');
    }
}
