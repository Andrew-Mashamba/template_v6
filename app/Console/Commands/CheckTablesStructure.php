<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\McpDatabaseService;

class CheckTablesStructure extends Command
{
    protected $signature = 'db:structure {table}';
    protected $description = 'Check the structure of a database table';

    private $mcpService;

    public function __construct()
    {
        parent::__construct();
        $this->mcpService = new McpDatabaseService();
    }

    public function handle()
    {
        $tableName = $this->argument('table');
        
        $this->info("Checking structure for table: {$tableName}");
        $this->line(str_repeat('=', 60));
        
        $result = $this->mcpService->describeTable($tableName);
        
        if ($result['success']) {
            $this->table(
                ['Column Name', 'Data Type', 'Nullable', 'Default', 'Max Length'],
                collect($result['columns'])->map(function ($column) {
                    return [
                        $column->column_name,
                        $column->data_type,
                        $column->is_nullable,
                        $column->column_default ?? 'NULL',
                        $column->character_maximum_length ?? 'N/A'
                    ];
                })->toArray()
            );
        } else {
            $this->error("Error: " . $result['error']);
        }

        return Command::SUCCESS;
    }
}