<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Analyzers\Stmts;


use Orklah\StrictTypes\Hooks\StrictTypesHooks;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Use_;

class Use_Analyzer
{

    /** @var array<string, array<lowercase-string, list<string>>> */
    public static $use_map = [];

    /**
     * This analyzer is a little special. It won't find error but exists solely to make a map of known symbols and their namespaces
     * @param array<Expr|Stmt> $history
     */
    public static function analyze(Use_ $stmt, array $history): void
    {
        $file_path = StrictTypesHooks::$statement_source->getFileAnalyzer()->getFilePath();
        if (!isset(self::$use_map[$file_path])) {
            self::$use_map[$file_path] = [];
        }
        foreach ($stmt->uses as $useUse) {
            $parts = $useUse->name->parts;
            $last_part = strtolower(array_pop($parts));
            self::$use_map[$file_path][$last_part] = $parts;
        }
    }
}
