<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Hooks;

use Error;
use Exception;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\NonStrictUsageException;
use Orklah\StrictTypes\Exceptions\NonVerifiableStrictUsageException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Issues\NonStrictUsageIssue;
use Orklah\StrictTypes\Issues\NonStrictUsageOnStrictFileIssue;
use Orklah\StrictTypes\Issues\NonVerifiableStrictUsage;
use Orklah\StrictTypes\Traversers\StmtsTraverser;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Declare_;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\IssueBuffer;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Storage\FileStorage;
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
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
    {
        $statements_source = $event->getStatementsSource();
        $file_context = $event->getFileContext();
        $file_storage = $event->getFileStorage();
        $codebase = $event->getCodebase();
        $stmts = $event->getStmts();

        assert($statements_source instanceof FileAnalyzer);

        self::$statement_source = $statements_source;
        self::$file_context = $file_context;
        self::$file_storage = $file_storage;
        self::$codebase = $codebase;

        $have_declare_statement = false;
        $maybe_declare = $stmts[0] ?? null;
        if ($maybe_declare instanceof Declare_) {
            //assume this is strict_types. Will have to refine that later
            $have_declare_statement = true;
        }

        try {
            StmtsTraverser::traverseStatements($stmts, []);
        } catch (NonStrictUsageException $e) {
            if($have_declare_statement){
                $issue = new NonStrictUsageOnStrictFileIssue($e->getMessage(),
                    new CodeLocation($statements_source, $e->getNode())
                );
            }
            else{
                $issue = new NonStrictUsageIssue($e->getMessage(),
                    new CodeLocation($statements_source, $e->getNode())
                );
            }

            IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            return;
        } catch (NonVerifiableStrictUsageException $e) {
            // This is not safe enough to do automatically
            $issue = new NonVerifiableStrictUsage($e->getMessage(),
                new CodeLocation($statements_source, $e->getNode())
            );

            IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            return;
        } catch (ShouldNotHappenException $e) {
            // This is probably a bug I left
            var_dump($e->getMessage() . ' in ' . $file_storage->file_path) ."\n";
            return;
        } catch (NeedRefinementException $e) {
            // This could be safe but it's not yet ready
            var_dump($e->getMessage() . ' in ' . $file_storage->file_path);
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
        //echo("eligible to strict types\n");
        return;
        //If there wasn't issue, put the strict type declaration
        $file_contents = file_get_contents($file_storage->file_path);
        $new_file_contents = str_replace('<?php', '<?php declare(strict_types=1);', $file_contents);
        file_put_contents($file_storage->file_path, $new_file_contents);
    }

    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $statements_source = $event->getStatementsSource();
        $node_type_provider = $event->getNodeTypeProvider();
        $context = $event->getContext();

        assert($statements_source instanceof FunctionLikeAnalyzer);


        // This will only serve to store NodeTypeProviders for later
        if (!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()])) {
            self::$node_type_providers_map = []; // Clear array when changing file
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()] = [];
        }
        if (!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()])) {
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()] = [];
        }
        if(!isset(self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()])){
            self::$node_type_providers_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()] = $node_type_provider;
        }

        if (!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()])) {
            self::$context_map = []; // Clear array when changing file
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()] = [];
        }
        if (!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()])) {
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()] = [];
        }
        if(!isset(self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()])){
            self::$context_map[$statements_source->getFileAnalyzer()->getFilePath()][$statements_source->getClassName()][$statements_source->getMethodName()] = $context;
        }

        return null;
    }
}
