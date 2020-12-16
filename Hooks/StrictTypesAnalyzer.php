<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Hooks;

use Error;
use Exception;
use Orklah\StrictTypes\Analyzers\StmtsAnalyzer;
use PhpParser\Node\Stmt\Declare_;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Plugin\Hook\AfterFileAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use function assert;

class StrictTypesAnalyzer implements AfterFileAnalysisInterface
{
    /** @var FileAnalyzer */
    public static $statement_source;
    public static $file_context;
    /** @var FileStorage */
    public static $file_storage;
    /** @var Codebase */
    public static $codebase;

    public static function afterAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase,
        $stmts
    ): void
    {
        assert($statements_source instanceof FileAnalyzer);

        self::$statement_source = $statements_source;
        self::$file_context = $file_context;
        self::$file_storage = $file_storage;
        self::$codebase = $codebase;

        //$stmts = $codebase->getStatementsForFile($file_storage->file_path);

        $maybe_declare = $stmts[0] ?? null;
        if ($maybe_declare instanceof Declare_) {
            //assume this is strict_types. Will have to refine that later
            return;
        }

        try {
            StmtsAnalyzer::analyzeStatements($stmts, []);
        } catch (NonStrictUsageException $e) {
            // create an issue and show why each file can't be strict?
            var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (Exception $e) {
            // handle exceptions returned by Psalm. It should be handled sooner (probably in custom methods) but I'm not sure this is stable.
            // handling it here allow psalm to continue working in case of error on one file
            var_dump($e->getMessage());
            return;
        } catch (Error $e) {
            // I must have done something reeaaally bad. But we can't allow that to disrupt psalm's analysis
            var_dump($e->getMessage());
            return;
        }

        //var_dump($stmts);
        echo('eligible to strict types');
        return;
        //If there wasn't issue, put the strict type declaration
        $file_contents = file_get_contents($file_storage->file_path);
        $new_file_contents = str_replace('<?php', '<?php declare(strict_types=1);', $file_contents);
        file_put_contents($file_storage->file_path, $new_file_contents);
    }
}

class NonStrictUsageException extends Exception
{
}
