<?php declare(strict_types=1);

namespace Orklah\StrictTypes\Hooks;

use Error;
use Exception;
use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\NeedRefinementException;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Issues\StrictDeclarationToAddIssue;
use Orklah\StrictTypes\Traversers\StmtsTraverser;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Node\Stmt\VirtualDeclare;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Storage\FunctionStorage;
use Webmozart\Assert\Assert;
use function assert;
use function get_class;

class StrictTypesHooks implements AfterFileAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    /** @var array<lowercase-string, array<lowercase-string, NodeTypeProvider>> */
    public static $node_type_providers_map = [];
    /** @var array<lowercase-string, array<lowercase-string, Context>> */
    public static $context_map = [];
    /** @var array<lowercase-string, FunctionStorage> */
    public static $function_storage_map = [];
    /** @var FileContext */
    public static $internal_file_context;

    /**
     * @throws NeedRefinementException
     * @throws ShouldNotHappenException
     */
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
    {
        $statements_source = $event->getStatementsSource();
        $file_context = $event->getFileContext();
        $file_storage = $event->getFileStorage();
        $codebase = $event->getCodebase();
        $stmts = $event->getStmts();

        Assert::isInstanceOf($statements_source, FileAnalyzer::class);

        $maybe_declare = $stmts[0] ?? null;
        $have_declare_statement = false;
        if ($maybe_declare instanceof Declare_) {
            //assume this is strict_types. Will have to refine that later
            $have_declare_statement = true;
        }

        self::$internal_file_context = new FileContext(
            $statements_source,
            $file_context,
            $file_storage,
            $codebase,
            self::$node_type_providers_map,
            self::$context_map,
            self::$function_storage_map,
            $have_declare_statement
        );
        //we need to erase the maps as soon as possible. We made a copy in file context and then erase the maps
        self::$node_type_providers_map = [];
        self::$context_map = [];

        try {
            StmtsTraverser::traverseStatements($stmts, []);
        } catch (ShouldNotHappenException $e) {
            // This is probably a bug I left
            if(ProjectAnalyzer::$instance->debug_lines) {
                ProjectAnalyzer::$instance->progress->debug(get_class($e) . ': ' . $e->getMessage() . ' in ' . $file_storage->file_path);
                //echo $e->getTraceAsString() ."\n";
            }
            return;
        } catch (NeedRefinementException $e) {
            // This could be safe but it's not yet ready
            if(ProjectAnalyzer::$instance->debug_lines) {
                ProjectAnalyzer::$instance->progress->debug(get_class($e) . ': ' . $e->getMessage() . ' in ' . $file_storage->file_path);
                //echo $e->getTraceAsString() ."\n";
            }
            if($codebase->config->throw_exception){
                throw $e;
            }
            return;
        } catch (Exception $e) {
            // handle exceptions returned by Psalm. It should be handled sooner (probably in custom methods) but I'm not sure this is stable.
            // handling it here allow psalm to continue working in case of error on one file
            if(ProjectAnalyzer::$instance->debug_lines) {
                ProjectAnalyzer::$instance->progress->debug(get_class($e) . ': ' . $e->getMessage() . ' in ' . $file_storage->file_path);
                //echo $e->getTraceAsString() ."\n";
            }
            if($codebase->config->throw_exception){
                throw $e;
            }
            return;
        } catch (Error $e) {
            // I must have done something reeaaally bad. But we can't allow that to disrupt psalm's analysis
            if(ProjectAnalyzer::$instance->debug_lines) {
                ProjectAnalyzer::$instance->progress->debug(get_class($e) . ': ' . $e->getMessage() . ' in ' . $file_storage->file_path);
                //echo $e->getTraceAsString() ."\n";
            }
            if($codebase->config->throw_exception){
                throw $e;
            }
            return;
        }

        if (!$have_declare_statement && self::$internal_file_context->declare_can_be_added) {
            if ($codebase->alter_code) {
                $file_contents = file_get_contents($file_storage->file_path);
                if($file_contents === false) {
                    return;
                }

                $count = 0;
                $new_file_contents = preg_replace('#^<\?php#', '<?php declare(strict_types=1);', $file_contents, 1, $count);
                if ($count === 1) {
                    file_put_contents($file_storage->file_path, $new_file_contents);
                }
            } else {
                $issue = new StrictDeclarationToAddIssue('This file can have a strict declaration added',
                    new CodeLocation($statements_source, new VirtualDeclare([], null, ['startLine' => 0]))
                );

                IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            }
        }
    }

    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $statements_source = $event->getStatementsSource();
        $node_type_provider = $event->getNodeTypeProvider();
        $context = $event->getContext();
        $stmt = $event->getStmt();
        $class_like_storage = $event->getClasslikeStorage();

        assert($statements_source instanceof FunctionLikeAnalyzer);
        $class_name = strtolower($statements_source->getClassName() ?? '');
        $method_name = strtolower($statements_source->getMethodName() ?? '');

        if ($stmt instanceof ClassMethod) {
            // This will only serve to store NodeTypeProviders for later
            if (!isset(self::$node_type_providers_map[$class_name])) {
                self::$node_type_providers_map[$class_name] = [];
            }
            if (!isset(self::$node_type_providers_map[$class_name][$method_name])) {
                self::$node_type_providers_map[$class_name][$method_name] = $node_type_provider;
            }

            if (!isset(self::$context_map[$class_name])) {
                self::$context_map[$class_name] = [];
            }
            if (!isset(self::$context_map[$class_name][$method_name])) {
                self::$context_map[$class_name][$method_name] = $context;
            }
        } elseif ($stmt instanceof Function_) {
            assert($class_like_storage instanceof FunctionStorage);

            //TODO: throw an exception on conflicting names
            self::$function_storage_map[strtolower($stmt->name->name)] = $class_like_storage;
        }

        return null;
    }
}
