<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Exception;

class McpDatabaseService
{
    private $mcpServerProcess = null;
    private $mcpServerRunning = false;
    private $databaseConfig;
    private $mcpTools = [
        'read_query' => [
            'description' => 'Execute SELECT queries to read data',
            'parameters' => ['query' => 'SQL SELECT statement'],
            'permission' => 'GRANTED'
        ],
        'write_query' => [
            'description' => 'Execute INSERT, UPDATE, or DELETE queries',
            'parameters' => ['query' => 'SQL modification statement'],
            'permission' => 'GRANTED'
        ],
        'create_table' => [
            'description' => 'Create new tables in the database',
            'parameters' => ['query' => 'CREATE TABLE statement'],
            'permission' => 'GRANTED'
        ],
        'alter_table' => [
            'description' => 'Modify existing table schema',
            'parameters' => ['query' => 'ALTER TABLE statement'],
            'permission' => 'GRANTED'
        ],
        'drop_table' => [
            'description' => 'Remove a table from the database',
            'parameters' => ['table_name' => 'Name of table', 'confirm' => 'Safety flag (must be true)'],
            'permission' => 'GRANTED'
        ],
        'list_tables' => [
            'description' => 'Get a list of all tables',
            'parameters' => [],
            'permission' => 'GRANTED'
        ],
        'describe_table' => [
            'description' => 'View schema information for a table',
            'parameters' => ['table_name' => 'Name of table'],
            'permission' => 'GRANTED'
        ],
        'export_query' => [
            'description' => 'Export query results as CSV/JSON',
            'parameters' => ['query' => 'SQL SELECT statement', 'format' => 'csv or json'],
            'permission' => 'GRANTED'
        ],
        'append_insight' => [
            'description' => 'Add a business insight to memo',
            'parameters' => ['insight' => 'Text of insight'],
            'permission' => 'GRANTED'
        ],
        'list_insights' => [
            'description' => 'List all business insights',
            'parameters' => [],
            'permission' => 'GRANTED'
        ]
    ];
    
    private $businessInsights = [];

    public function __construct()
    {
        $this->databaseConfig = [
            'host' => config('database.connections.pgsql.host', 'localhost'),
            'database' => config('database.connections.pgsql.database', 'saccos_core_system'),
            'username' => config('database.connections.pgsql.username', 'postgres'),
            'password' => config('database.connections.pgsql.password', ''),
            'port' => config('database.connections.pgsql.port', 5432),
            'ssl' => config('database.connections.pgsql.sslmode', 'disable')
        ];
    }

    /**
     * Start the MCP Database Server
     */
    public function startMcpServer(): bool
    {
        try {
            if ($this->mcpServerRunning) {
                Log::info('MCP Database Server is already running');
                return true;
            }

            $command = $this->buildMcpCommand();
            
            Log::info('Starting MCP Database Server', [
                'command' => $command,
                'database' => $this->databaseConfig['database']
            ]);

            // Start the MCP server process
            $this->mcpServerProcess = proc_open($command, [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w']  // stderr
            ], $pipes);

            if (!is_resource($this->mcpServerProcess)) {
                throw new Exception('Failed to start MCP Database Server');
            }

            // Set non-blocking mode
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            // Wait a moment for the server to start
            sleep(2);

            // Check if process is still running
            $status = proc_get_status($this->mcpServerProcess);
            if ($status['running']) {
                $this->mcpServerRunning = true;
                Log::info('MCP Database Server started successfully');
                return true;
            } else {
                throw new Exception('MCP Database Server failed to start');
            }

        } catch (Exception $e) {
            Log::error('Failed to start MCP Database Server', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Stop the MCP Database Server
     */
    public function stopMcpServer(): bool
    {
        try {
            if (!$this->mcpServerRunning || !$this->mcpServerProcess) {
                return true;
            }

            Log::info('Stopping MCP Database Server');
            
            proc_terminate($this->mcpServerProcess);
            proc_close($this->mcpServerProcess);
            
            $this->mcpServerRunning = false;
            $this->mcpServerProcess = null;
            
            Log::info('MCP Database Server stopped successfully');
            return true;

        } catch (Exception $e) {
            Log::error('Failed to stop MCP Database Server', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Build the MCP command with database configuration
     */
    private function buildMcpCommand(): string
    {
        $args = [
            'npx',
            '-y',
            '@executeautomation/database-server',
            '--postgresql',
            '--host', $this->databaseConfig['host'],
            '--database', $this->databaseConfig['database'],
            '--user', $this->databaseConfig['username'],
            '--password', $this->databaseConfig['password'],
            '--port', $this->databaseConfig['port']
        ];

        if ($this->databaseConfig['ssl'] !== 'disable') {
            $args[] = '--ssl';
            $args[] = 'true';
        }

        return implode(' ', array_map('escapeshellarg', $args));
    }

    /**
     * Get Claude Code CLI configuration for MCP
     */
    public function getClaudeConfig(): array
    {
        return [
            'mcpServers' => [
                'saccos-database' => [
                    'command' => 'npx',
                    'args' => [
                        '-y',
                        '@executeautomation/database-server',
                        '--postgresql',
                        '--host', $this->databaseConfig['host'],
                        '--database', $this->databaseConfig['database'],
                        '--user', $this->databaseConfig['username'],
                        '--password', $this->databaseConfig['password'],
                        '--port', $this->databaseConfig['port']
                    ]
                ]
            ]
        ];
    }

    /**
     * Get available MCP tools
     */
    public function getAvailableTools(): array
    {
        return $this->mcpTools;
    }

    /**
     * Execute MCP tool based on tool name
     */
    public function executeMcpTool(string $tool, array $parameters = []): array
    {
        // LOG POINT: MCP Tool Execution
        Log::channel('daily')->info('🔧 [MCP-TOOL] Executing MCP Tool', [
            'tool' => $tool,
            'parameters' => $parameters,
            'location' => 'McpDatabaseService::executeMcpTool'
        ]);
        
        if (!isset($this->mcpTools[$tool])) {
            return [
                'success' => false,
                'error' => "Unknown MCP tool: {$tool}"
            ];
        }
        
        try {
            switch ($tool) {
                case 'read_query':
                    return $this->executeReadQuery($parameters['query'] ?? '');
                    
                case 'write_query':
                    return $this->executeWriteQuery($parameters['query'] ?? '');
                    
                case 'create_table':
                    return $this->executeCreateTable($parameters['query'] ?? '');
                    
                case 'alter_table':
                    return $this->executeAlterTable($parameters['query'] ?? '');
                    
                case 'drop_table':
                    return $this->executeDropTable($parameters['table_name'] ?? '', $parameters['confirm'] ?? false);
                    
                case 'list_tables':
                    return $this->listTables();
                    
                case 'describe_table':
                    return $this->describeTable($parameters['table_name'] ?? '');
                    
                case 'export_query':
                    return $this->executeExportQuery($parameters['query'] ?? '', $parameters['format'] ?? 'json');
                    
                case 'append_insight':
                    return $this->appendInsight($parameters['insight'] ?? '');
                    
                case 'list_insights':
                    return $this->listInsights();
                    
                default:
                    return [
                        'success' => false,
                        'error' => "Tool not implemented: {$tool}"
                    ];
            }
        } catch (Exception $e) {
            Log::channel('daily')->error('❌ [MCP-TOOL] Tool execution failed', [
                'tool' => $tool,
                'error' => $e->getMessage(),
                'location' => 'McpDatabaseService::executeMcpTool::error'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tool' => $tool
            ];
        }
    }
    
    /**
     * Execute SELECT query (read_query tool)
     */
    private function executeReadQuery(string $query): array
    {
        if (empty($query)) {
            throw new Exception('Query cannot be empty');
        }
        
        // Ensure it's a SELECT query
        if (!preg_match('/^\s*SELECT/i', $query)) {
            throw new Exception('read_query tool only accepts SELECT statements');
        }
        
        $results = DB::select($query);
        
        return [
            'success' => true,
            'tool' => 'read_query',
            'data' => $results,
            'count' => count($results),
            'query' => $query
        ];
    }
    
    /**
     * Execute INSERT, UPDATE, DELETE query (write_query tool)
     */
    private function executeWriteQuery(string $query): array
    {
        if (empty($query)) {
            throw new Exception('Query cannot be empty');
        }
        
        // Check for valid write operations
        if (!preg_match('/^\s*(INSERT|UPDATE|DELETE)/i', $query)) {
            throw new Exception('write_query tool only accepts INSERT, UPDATE, or DELETE statements');
        }
        
        $affected = DB::statement($query);
        
        return [
            'success' => true,
            'tool' => 'write_query',
            'affected_rows' => $affected,
            'query' => $query
        ];
    }
    
    /**
     * Create a new table (create_table tool)
     */
    private function executeCreateTable(string $query): array
    {
        if (empty($query)) {
            throw new Exception('CREATE TABLE statement cannot be empty');
        }
        
        if (!preg_match('/^\s*CREATE\s+TABLE/i', $query)) {
            throw new Exception('create_table tool requires a CREATE TABLE statement');
        }
        
        DB::statement($query);
        
        // Extract table name
        preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([^\s(]+)/i', $query, $matches);
        $tableName = $matches[1] ?? 'unknown';
        
        return [
            'success' => true,
            'tool' => 'create_table',
            'table_name' => $tableName,
            'message' => "Table {$tableName} created successfully"
        ];
    }
    
    /**
     * Alter table schema (alter_table tool)
     */
    private function executeAlterTable(string $query): array
    {
        if (empty($query)) {
            throw new Exception('ALTER TABLE statement cannot be empty');
        }
        
        if (!preg_match('/^\s*ALTER\s+TABLE/i', $query)) {
            throw new Exception('alter_table tool requires an ALTER TABLE statement');
        }
        
        DB::statement($query);
        
        // Extract table name
        preg_match('/ALTER\s+TABLE\s+([^\s]+)/i', $query, $matches);
        $tableName = $matches[1] ?? 'unknown';
        
        return [
            'success' => true,
            'tool' => 'alter_table',
            'table_name' => $tableName,
            'message' => "Table {$tableName} altered successfully"
        ];
    }
    
    /**
     * Drop a table (drop_table tool)
     */
    private function executeDropTable(string $tableName, bool $confirm): array
    {
        if (empty($tableName)) {
            throw new Exception('Table name cannot be empty');
        }
        
        if (!$confirm) {
            throw new Exception('Safety confirmation required. Set confirm=true to drop table');
        }
        
        // Safety check - don't drop critical system tables
        $protectedTables = ['users', 'migrations', 'password_resets', 'failed_jobs'];
        if (in_array(strtolower($tableName), $protectedTables)) {
            throw new Exception("Cannot drop protected system table: {$tableName}");
        }
        
        DB::statement("DROP TABLE IF EXISTS {$tableName}");
        
        return [
            'success' => true,
            'tool' => 'drop_table',
            'table_name' => $tableName,
            'message' => "Table {$tableName} dropped successfully"
        ];
    }
    
    /**
     * Export query results (export_query tool)
     */
    private function executeExportQuery(string $query, string $format): array
    {
        if (empty($query)) {
            throw new Exception('Query cannot be empty');
        }
        
        if (!in_array($format, ['csv', 'json'])) {
            throw new Exception('Format must be either "csv" or "json"');
        }
        
        $results = DB::select($query);
        
        if ($format === 'csv') {
            // Convert to CSV
            $csv = $this->convertToCsv($results);
            return [
                'success' => true,
                'tool' => 'export_query',
                'format' => 'csv',
                'data' => $csv,
                'count' => count($results)
            ];
        } else {
            // Return as JSON
            return [
                'success' => true,
                'tool' => 'export_query',
                'format' => 'json',
                'data' => json_encode($results, JSON_PRETTY_PRINT),
                'count' => count($results)
            ];
        }
    }
    
    /**
     * Convert results to CSV format
     */
    private function convertToCsv(array $results): string
    {
        if (empty($results)) {
            return '';
        }
        
        $csv = '';
        
        // Headers
        $headers = array_keys((array)$results[0]);
        $csv .= implode(',', $headers) . "\n";
        
        // Data rows
        foreach ($results as $row) {
            $values = array_map(function($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, (array)$row);
            $csv .= implode(',', $values) . "\n";
        }
        
        return $csv;
    }
    
    /**
     * Append business insight (append_insight tool)
     */
    private function appendInsight(string $insight): array
    {
        if (empty($insight)) {
            throw new Exception('Insight cannot be empty');
        }
        
        $insightData = [
            'id' => uniqid('insight_'),
            'insight' => $insight,
            'timestamp' => now()->toIso8601String(),
            'user_id' => auth()->id() ?? null
        ];
        
        // Store in cache for persistence
        $insights = Cache::get('business_insights', []);
        $insights[] = $insightData;
        Cache::put('business_insights', $insights, 86400); // Store for 24 hours
        
        return [
            'success' => true,
            'tool' => 'append_insight',
            'insight_id' => $insightData['id'],
            'message' => 'Business insight added successfully'
        ];
    }
    
    /**
     * List business insights (list_insights tool)
     */
    private function listInsights(): array
    {
        $insights = Cache::get('business_insights', []);
        
        return [
            'success' => true,
            'tool' => 'list_insights',
            'count' => count($insights),
            'insights' => $insights
        ];
    }
    
    /**
     * Execute a database query using MCP tools (backward compatibility)
     */
    public function executeQuery(string $query, string $type = 'read'): array
    {
        // LOG POINT 20: MCP Database Query
        Log::channel('daily')->info('🔸 [PROMPT-CHAIN] MCP Database Query', [
            'query_type' => $type,
            'query_preview' => substr($query, 0, 100),
            'step' => 20,
            'location' => 'McpDatabaseService::executeQuery'
        ]);
        
        try {
            // Validate query type
            if (!in_array($type, ['read', 'write'])) {
                throw new Exception('Invalid query type. Must be "read" or "write"');
            }

            // For now, we'll execute directly using Laravel's DB facade
            // In a full implementation, this would communicate with the MCP server
            if ($type === 'read') {
                $results = DB::select($query);
                
                // LOG POINT 21: MCP Database Result
                Log::channel('daily')->info('🔹 [PROMPT-CHAIN] MCP Database Result', [
                    'result_count' => count($results),
                    'query_type' => 'read',
                    'step' => 21,
                    'location' => 'McpDatabaseService::executeQuery::result'
                ]);
                
                return [
                    'success' => true,
                    'data' => $results,
                    'count' => count($results),
                    'type' => 'read'
                ];
            } else {
                $affected = DB::statement($query);
                
                // LOG POINT 21: MCP Database Result
                Log::channel('daily')->info('🔹 [PROMPT-CHAIN] MCP Database Result', [
                    'affected_rows' => $affected,
                    'query_type' => 'write',
                    'step' => 21,
                    'location' => 'McpDatabaseService::executeQuery::result'
                ]);
                
                return [
                    'success' => true,
                    'affected_rows' => $affected,
                    'type' => 'write'
                ];
            }

        } catch (Exception $e) {
            // LOG POINT 21-ERROR: MCP Database Error
            Log::channel('daily')->error('❌ [PROMPT-CHAIN] MCP Database Error', [
                'query' => $query,
                'type' => $type,
                'error' => $e->getMessage(),
                'step' => 21,
                'location' => 'McpDatabaseService::executeQuery::error'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query,
                'type' => $type
            ];
        }
    }

    /**
     * Get database schema information
     */
    public function getDatabaseSchema(): array
    {
        try {
            $tables = DB::select("
                SELECT 
                    table_name,
                    table_type
                FROM information_schema.tables 
                WHERE table_schema = 'public'
                ORDER BY table_name
            ");

            $schema = [];
            foreach ($tables as $table) {
                $columns = DB::select("
                    SELECT 
                        column_name,
                        data_type,
                        is_nullable,
                        column_default
                    FROM information_schema.columns 
                    WHERE table_name = ? AND table_schema = 'public'
                    ORDER BY ordinal_position
                ", [$table->table_name]);

                $schema[$table->table_name] = [
                    'type' => $table->table_type,
                    'columns' => $columns
                ];
            }

            return [
                'success' => true,
                'schema' => $schema
            ];

        } catch (Exception $e) {
            Log::error('Failed to get database schema', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all tables
     */
    public function listTables(): array
    {
        try {
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public'
                ORDER BY table_name
            ");

            return [
                'success' => true,
                'tables' => array_column($tables, 'table_name')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Describe a specific table
     */
    public function describeTable(string $tableName): array
    {
        try {
            $columns = DB::select("
                SELECT 
                    column_name,
                    data_type,
                    is_nullable,
                    column_default,
                    character_maximum_length
                FROM information_schema.columns 
                WHERE table_name = ? AND table_schema = 'public'
                ORDER BY ordinal_position
            ", [$tableName]);

            if (empty($columns)) {
                return [
                    'success' => false,
                    'error' => "Table '{$tableName}' not found"
                ];
            }

            return [
                'success' => true,
                'table' => $tableName,
                'columns' => $columns
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if MCP server is running
     */
    public function isRunning(): bool
    {
        return $this->mcpServerRunning;
    }

    /**
     * Get MCP server status
     */
    public function getStatus(): array
    {
        return [
            'running' => $this->mcpServerRunning,
            'database' => $this->databaseConfig['database'],
            'host' => $this->databaseConfig['host'],
            'port' => $this->databaseConfig['port'],
            'available_tools' => array_keys($this->mcpTools)
        ];
    }

    /**
     * Generate Claude Code CLI configuration instructions
     */
    public function getClaudeConfigInstructions(): string
    {
        $config = $this->getClaudeConfig();
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        
        $instructions = "## MCP Database Server Configuration for Claude Code CLI\n\n";
        $instructions .= "To enable database querying in Claude Code CLI, add this configuration to your Claude Desktop config:\n\n";
        $instructions .= "**Config File Location:**\n";
        $instructions .= "- macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`\n";
        $instructions .= "- Windows: `%APPDATA%\\Claude\\claude_desktop_config.json`\n";
        $instructions .= "- Linux: `~/.config/Claude/claude_desktop_config.json`\n\n";
        $instructions .= "**Configuration:**\n```json\n{$configJson}\n```\n\n";
        $instructions .= "**Available Tools:**\n";
        foreach ($this->mcpTools as $tool => $description) {
            $instructions .= "- `{$tool}`: {$description}\n";
        }
        
        return $instructions;
    }
}
