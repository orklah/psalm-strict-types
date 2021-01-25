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
use Orklah\StrictTypes\Issues\NonVerifiableStrictUsageIssue;
use Orklah\StrictTypes\Issues\StrictDeclarationToAddIssue;
use Orklah\StrictTypes\Traversers\StmtsTraverser;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
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
use Psalm\Storage\FunctionStorage;
use function assert;

class StrictTypesHooks implements AfterFileAnalysisInterface, AfterFunctionLikeAnalysisInterface
{
    /** @var array<string, array<lowercase-string, array<lowercase-string, Context>>> */
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
    /** @var array<lowercase-string, FunctionStorage> */
    public static $function_storage_map = [];

    /**
     * @throws NeedRefinementException
     * @throws NonStrictUsageException
     * @throws NonVerifiableStrictUsageException
     * @throws ShouldNotHappenException
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
            if ($have_declare_statement) {
                $issue = new NonStrictUsageOnStrictFileIssue($e->getMessage(),
                    new CodeLocation($statements_source, $e->getNode())
                );
            } else {
                $issue = new NonStrictUsageIssue($e->getMessage(),
                    new CodeLocation($statements_source, $e->getNode())
                );
            }

            IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            return;
        } catch (NonVerifiableStrictUsageException $e) {
            // This is not safe enough to do automatically
            $issue = new NonVerifiableStrictUsageIssue($e->getMessage(),
                new CodeLocation($statements_source, $e->getNode())
            );

            IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            return;
        } catch (ShouldNotHappenException $e) {
            // This is probably a bug I left
            var_dump(get_class($e), $e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (NeedRefinementException $e) {
            // This could be safe but it's not yet ready
            var_dump(get_class($e), $e->getMessage() . ' in ' . $file_storage->file_path);
            return;
        } catch (Exception $e) {
            // handle exceptions returned by Psalm. It should be handled sooner (probably in custom methods) but I'm not sure this is stable.
            // handling it here allow psalm to continue working in case of error on one file
            var_dump(get_class($e), $e->getMessage()) . "\n";
            //echo $e->getTraceAsString() ."\n";
            return;
        } catch (Error $e) {
            // I must have done something reeaaally bad. But we can't allow that to disrupt psalm's analysis
            var_dump(get_class($e), $e->getMessage()) . "\n";
            echo $e->getTraceAsString() . "\n";
            return;
        }

        if (!$have_declare_statement) {
            $issue = new StrictDeclarationToAddIssue('This file can have a strict declaration added',
                new CodeLocation($statements_source, new Declare_([], null, ['startLine' => 0]))
            );

            IssueBuffer::accepts($issue, $statements_source->getSuppressedIssues());
            return;
            //If there wasn't issue, put the strict type declaration
            $file_contents = file_get_contents($file_storage->file_path);

            $count = 0;
            $new_file_contents = preg_replace('#^<\?php#', '<?php declare(strict_types=1);', $file_contents, 1, $count);
            if($count === 1) {
                file_put_contents($file_storage->file_path, $new_file_contents);
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
        $file_path = $statements_source->getFileAnalyzer()->getFilePath();
        $class_name = strtolower($statements_source->getClassName()??'');
        $method_name = strtolower($statements_source->getMethodName()??'');

        if ($stmt instanceof ClassMethod) {
            //TODO: consider using namespace instead of file path. It would make more sense

            // This will only serve to store NodeTypeProviders for later
            if (!isset(self::$node_type_providers_map[$file_path])) {
                self::$node_type_providers_map = []; // Clear array when changing file
                self::$node_type_providers_map[$file_path] = [];
            }
            if (!isset(self::$node_type_providers_map[$file_path][$class_name])) {
                self::$node_type_providers_map[$file_path][$class_name] = [];
            }
            if (!isset(self::$node_type_providers_map[$file_path][$class_name][$method_name])) {
                self::$node_type_providers_map[$file_path][$class_name][$method_name] = $node_type_provider;
            }

            if (!isset(self::$context_map[$file_path])) {
                self::$context_map = []; // Clear array when changing file
                self::$context_map[$file_path] = [];
            }
            if (!isset(self::$context_map[$file_path][$class_name])) {
                self::$context_map[$file_path][$class_name] = [];
            }
            if (!isset(self::$context_map[$file_path][$class_name][$method_name])) {
                self::$context_map[$file_path][$class_name][$method_name] = $context;
            }
        } elseif ($stmt instanceof Function_) {
            assert($class_like_storage instanceof FunctionStorage);

            //TODO: throw an exception on conflicting names
            self::$function_storage_map[strtolower($stmt->name->name)] = $class_like_storage;
        }

        return null;
    }
}
