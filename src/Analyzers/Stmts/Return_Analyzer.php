<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Stmts;

use Orklah\StrictTypes\Exceptions\BadTypeFromSignatureException;
use Orklah\StrictTypes\Exceptions\GoodTypeFromDocblockException;
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
use Webmozart\Assert\Assert;

class Return_Analyzer
{

    /**
     * @param array<Expr|Stmt> $history
     * @throws BadTypeFromSignatureException
     * @throws ShouldNotHappenException
     * @throws GoodTypeFromDocblockException
     */
    public static function analyze(Return_ $stmt, array $history): void
    {
        if ($stmt->expr === null) {
            // this happens on void methods. This has no impact on strict types
            return;
        }

        $functionlike_stmt = NodeNavigator::getLastNodeByTypes($history, [Function_::class, ClassMethod::class]);
        Assert::notNull($functionlike_stmt);
        $functionlike_storage = null;
        if ($functionlike_stmt instanceof Function_) {
            $functionlike_storage = StrictTypesHooks::$file_storage->functions[strtolower((string)$functionlike_stmt->name)] ?? null;
        } else {
            $class_stmt = NodeNavigator::getLastNodeByType($history, Class_::class);
            Assert::notNull($class_stmt);
            $functionlike_storage = NodeNavigator::getMethodStorageFromName(NodeNavigator::resolveName($history, (string)$class_stmt->name), strtolower((string)$functionlike_stmt->name));
        }

        if ($functionlike_storage === null) {
            //weird.
            throw new ShouldNotHappenException('Could not find Function Storage for ' . (string)$functionlike_stmt->name);
        }

        $node_provider = NodeNavigator::getNodeProviderFromContext($history);

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
            throw BadTypeFromSignatureException::createWithNode('Found return statement mismatching between ' . $signature_return_type->getKey() . ' and ' . $statement_return_type->getKey(), $stmt);
        }

        if ($statement_return_type->from_docblock === true) {
            //not trustworthy enough
            throw GoodTypeFromDocblockException::createWithNode('Found correct type but from docblock', $stmt);
        }

        //every potential mismatch would have been handled earlier
    }
}
