<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Stmts;

use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
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
     * @throws NonVerifiableStrictUsageException
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
                throw new ShouldNotHappenException('Could not find Method Storage for '.$method_stmt->name->name);
            }
            $signature_return_type = $method_storage->signature_return_type;

            $node_provider = StrictTypesHooks::$node_type_providers_map[StrictTypesHooks::$file_storage->file_path][$class_stmt->name->name][$method_stmt->name->name] ?? null;
            if ($node_provider === null) {
                //unable to fetch node provider. Throw
                throw new ShouldNotHappenException('Unable to retrieve Node Type Provider');
            }
            $statement_return_type = $node_provider->getType($stmt->expr);
        }
        else{
            $function_stmt = NodeNavigator::getLastNodeByType($history, Function_::class);
            $function_storage = StrictTypesHooks::$file_storage->functions[strtolower((string)$function_stmt->name->name)] ?? null;
            if($function_storage === null){
                //weird.
                throw new ShouldNotHappenException('Could not find Function Storage for '.$function_stmt->name->name);
            }
            $signature_return_type = $function_storage->signature_return_type;
            //TODO: retrieve return type for function
            $statement_return_type = null;
        }

        if ($signature_return_type === null) {
            //This is not interesting, if there is no declared type, this can't be wrong with strict_types
            return;
        }

        if ($statement_return_type === null) {
            throw new ShouldNotHappenException('Could not find Statement Return Type');
        }

        if (!StrictUnionsChecker::strictUnionCheck($signature_return_type, $statement_return_type)) {
            throw new NonStrictUsageException('Found return statement mismatching between '.$signature_return_type->getKey().' and '.$statement_return_type->getKey());
        }

        if ($statement_return_type->from_docblock === true) {
            //not trustworthy enough
            throw new NonVerifiableStrictUsageException('Found correct type but from docblock');
        }

        //every potential mismatch would have been handled earlier
    }
}
