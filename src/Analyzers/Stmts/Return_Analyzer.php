<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Stmts;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Issues\StrictTypesIssue;
use Orklah\StrictTypes\Utils\NodeNavigator;
use Orklah\StrictTypes\Utils\StrictUnionsChecker;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use Webmozart\Assert\Assert;

class Return_Analyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws ShouldNotHappenException
     */
    public static function analyze(FileContext $file_context, Return_ $stmt, array $history): void
    {
        if ($stmt->expr === null) {
            // this happens on void methods. This has no impact on strict types
            return;
        }

        $functionlike_stmt = NodeNavigator::getLastNodeByTypes($history, [Function_::class, ClassMethod::class]);
        Assert::notNull($functionlike_stmt);
        $functionlike_storage = null;
        if ($functionlike_stmt instanceof Function_) {
            $functionlike_storage = $file_context->getFileStorage()->functions[strtolower((string)$functionlike_stmt->name)] ?? null;
        } else {
            $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
            Assert::notNull($class_stmt);
            $functionlike_storage = NodeNavigator::getMethodStorageFromName($file_context, NodeNavigator::resolveName($file_context, $history, (string)$class_stmt->name), strtolower((string)$functionlike_stmt->name));
        }

        if ($functionlike_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Function Storage for ' . (string)$functionlike_stmt->name);
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($file_context, $history);

        $statement_return_type = $node_provider->getType($stmt->expr);

        $signature_return_type = $functionlike_storage->signature_return_type;

        if ($signature_return_type === null) {
            //This is not interesting, if there is no declared type, this can't be wrong with strict_types
            return;
        }

        if ($statement_return_type === null) {
            throw new ShouldNotHappenException('Could not find Statement Return Type');
        }

        $result = StrictUnionsChecker::strictUnionCheck($signature_return_type, $statement_return_type);
        if($result->is_correct){
            if ($statement_return_type->from_docblock === true) {
                //not trustworthy enough
                $message = 'Found correct type but from docblock';
                StrictTypesIssue::emitIssue($file_context, $stmt, $message, $result->is_correct, $statement_return_type->from_docblock, $result->is_partial, $result->is_mixed);
            }
        } else {
            $message = 'Found return statement mismatching between ' . $signature_return_type->getKey() . ' and ' . $statement_return_type->getKey();
            StrictTypesIssue::emitIssue($file_context, $stmt, $message, $result->is_correct, $statement_return_type->from_docblock, $result->is_partial, $result->is_mixed);
        }
    }
}
