<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Core;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\NodeTypeProvider;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionStorage;

class FileContext
{
    /**
     * @var FileAnalyzer
     */
    private $statements_source;
    /**
     * @var Context
     */
    private $file_context;
    /**
     * @var FileStorage
     */
    private $file_storage;
    /**
     * @var Codebase
     */
    private $codebase;
    /**
     * @var array<lowercase-string, array<lowercase-string, NodeTypeProvider>>
     */
    private $current_node_type_providers;
    /**
     * @var array<lowercase-string, array<lowercase-string, Context>>
     */
    private $current_context;
    /**
     * @var array<lowercase-string, FunctionStorage>
     */
    private $function_storage_map;
    /**
     * @var bool
     */
    private $have_declare_statement;

    public function __construct(FileAnalyzer $statements_source, Context $file_context, FileStorage $file_storage, Codebase $codebase, array $current_node_type_providers, array $current_context, array $function_storage_map, bool $have_declare_statement)
    {
        $this->statements_source = $statements_source;
        $this->file_context = $file_context;
        $this->file_storage = $file_storage;
        $this->codebase = $codebase;
        $this->current_node_type_providers = $current_node_type_providers;
        $this->current_context = $current_context;
        $this->function_storage_map = $function_storage_map;
        $this->have_declare_statement = $have_declare_statement;
    }

    /**
     * @return FileAnalyzer
     */
    public function getStatementsSource(): FileAnalyzer
    {
        return $this->statements_source;
    }

    /**
     * @return Context
     */
    public function getFileContext(): Context
    {
        return $this->file_context;
    }

    /**
     * @return FileStorage
     */
    public function getFileStorage(): FileStorage
    {
        return $this->file_storage;
    }

    /**
     * @return Codebase
     */
    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    /**
     * @return array<lowercase-string, array<lowercase-string, NodeTypeProvider>>
     */
    public function getCurrentNodeTypeProviders(): array
    {
        return $this->current_node_type_providers;
    }

    /**
     * @return array<lowercase-string, array<lowercase-string, Context>>
     */
    public function getCurrentContext(): array
    {
        return $this->current_context;
    }

    /**
     * @return array<lowercase-string, FunctionStorage>
     */
    public function getFunctionStorageMap(): array
    {
        return $this->function_storage_map;
    }

    /**
     * @return bool
     */
    public function isHaveDeclareStatement(): bool
    {
        return $this->have_declare_statement;
    }
}
