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
        if($stmt->expr === null){
            // this happens on void methods. This has no impact on strict types
            return;
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

        $method_stmt = NodeNavigator::getLastNodeByType($history, ClassMethod::class);
        if($method_stmt !== null){
            $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
            $functionlike_storage = NodeNavigator::getMethodStorageFromName(strtolower($class_stmt->name->name), strtolower($method_stmt->name->name));
            $functionlike_name = $method_stmt->name->name;
        }
        else{
            $function_stmt = NodeNavigator::getLastNodeByType($history, Function_::class);
            $functionlike_storage = StrictTypesHooks::$file_storage->functions[strtolower((string)$function_stmt->name->name)] ?? null;
            $functionlike_name = $function_stmt->name->name;
        }

        if($functionlike_storage === null){
            //weird.
            throw new ShouldNotHappenException('Could not find Function Storage for '.$functionlike_name);
        }

        $statement_return_type = $node_provider->getType($stmt->expr);

        $signature_return_type = $functionlike_storage->signature_return_type;

        if ($signature_return_type === null) {
            //This is not interesting, if there is no declared type, this can't be wrong with strict_types
            return;
        }

        if ($statement_return_type === null) {
            throw new ShouldNotHappenException('Could not find Statement Return Type');
        }

        if (!StrictUnionsChecker::strictUnionCheck($signature_return_type, $statement_return_type)) {
            throw NonStrictUsageException::createWithNode('Found return statement mismatching between '.$signature_return_type->getKey().' and '.$statement_return_type->getKey(), $stmt);
        }

        if ($statement_return_type->from_docblock === true) {
            //not trustworthy enough
            throw NonVerifiableStrictUsageException::createWithNode('Found correct type but from docblock', $stmt);
        }

        //every potential mismatch would have been handled earlier
    }
}
