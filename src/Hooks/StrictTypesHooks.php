<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Hooks;

use Error;
use Exception;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Traversers\StmtsTraverser;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Declare_;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\Hook\AfterFileAnalysisInterface;
use Psalm\Plugin\Hook\AfterFunctionLikeAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeStorage;
use function assert;

class StrictTypesHooks implements AfterFileAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    /** @var array<string, array<lowercase-string, array<lowercase-string, Context>>>  */
    public static $context_map = [];
    /** @var FileAnalyzer */
    public static $statement_source;
    /** @var Context|null */
    public static $file_context;
    /** @var FileStorage */
    public static $file_storage;
    /** @var Codebase */
    public static $codebase;
    /** @var array<string, array<lowercase-string, array<lowercase-string, NodeTypeProvider>>> */
    public static $node_type_providers_map = [];

    /**
     * @param list<Stmt> $stmts
     */
    public static function afterAnalyzeFile(
        StatementsSource $statements_source,
        Context $file_context,
        FileStorage $file_storage,
        Codebase $codebase,
        array $stmts
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
            StmtsTraverser::traverseStatements($stmts, []);
        } catch (NonStrictUsageException $e) {
            // create an issue and show why each file can't be strict?
            //var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (NonVerifiableStrictUsageException $e) {
            // This is not safe enough to do automatically
            //var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (ShouldNotHappenException $e) {
            // This is probably a bug I left
            var_dump($e->getMessage() . ' in ' . $file_storage->file_path) ."\n";
            return;
        } catch (NeedRefinementException $e) {
            // This could be safe but it's not yet ready
            //var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (Exception $e) {
            // handle exceptions returned by Psalm. It should be handled sooner (probably in custom methods) but I'm not sure this is stable.
            // handling it here allow psalm to continue working in case of error on one file
            var_dump($e->getMessage()) ."\n";
            echo $e->getTraceAsString() ."\n";
            return;
        } catch (Error $e) {
            // I must have done something reeaaally bad. But we can't allow that to disrupt psalm's analysis
            var_dump($e->getMessage()) ."\n";
            echo $e->getTraceAsString() ."\n";
            return;
        }

        //var_dump($stmts);
        echo("eligible to strict types\n");
        return;
        //If there wasn't issue, put the strict type declaration
        $file_contents = file_get_contents($file_storage->file_path);
        $new_file_contents = str_replace('<?php', '<?php declare(strict_types=1);', $file_contents);
        file_put_contents($file_storage->file_path, $new_file_contents);
    }

    public static function afterStatementAnalysis(
        Node\FunctionLike $stmt,
        FunctionLikeStorage $classlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): ?bool
    {
        assert($statements_source instanceof FunctionLikeAnalyzer);

        // This will only serve to store NodeTypeProviders for later
        if (!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()])) {
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()] = [];
        }
        if (!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()])) {
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()] = [];
        }
        if(!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()])){
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()] = $statements_source->getNodeTypeProvider();
        }

        if (!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()])) {
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()] = [];
        }
        if (!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()])) {
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()] = [];
        }
        if(!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()])){
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()] = $statements_source->context;
        }

        return null;
    }
}
