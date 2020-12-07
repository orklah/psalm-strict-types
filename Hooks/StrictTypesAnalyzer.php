<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Hooks;

use Exception;
use Orklah\StrictTypes\Analyzers\StmtsAnalyzer;
use PhpParser\Node\Stmt\Declare_;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Plugin\Hook\AfterFileAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;

class StrictTypesAnalyzer implements AfterFileAnalysisInterface
{

    public static function afterAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase
    ): void
    {
        $stmts = $codebase->statements_provider->getStatementsForFile(
            $file_storage->file_path,
            $codebase->php_major_version . '.' . $codebase->php_minor_version
        );

        $maybe_declare = $stmts[0];
        if($maybe_declare instanceof Declare_){
            //assume this is strict_types. Will have to refine that later
            return;
        }

        try {
            StmtsAnalyzer::analyzeStatements($stmts);
        }
        catch(NonStrictUsageException $e){
            // create an issue and show why each file can't be strict?
            var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        }

        //If there wasn't issue, put the strict type declaration
        $file_contents = file_get_contents($file_storage->file_path);
        $new_file_contents = str_replace('<?php', '<?php declare(strict_types=1);', $file_contents);
        file_put_contents($file_storage->file_path, $new_file_contents);
    }
}

class NonStrictUsageException extends Exception{}
