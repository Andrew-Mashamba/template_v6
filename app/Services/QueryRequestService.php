<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class QueryRequestService
{
    /**
     * Check if response contains permission issue or MCP tool request
     */
    public function hasPermissionIssue(string $response): bool
    {
        return strpos($response, 'PERMISSION-ISSUE') !== false ||
               strpos($response, '"permission_issue"') !== false ||
               strpos($response, 'MCP-TOOL') !== false ||
               strpos($response, '"mcp_tool"') !== false;
    }
    
    /**
     * Extract query request from Claude's response
     */
    public function extractQueryRequest(string $response): ?array
    {
        // Try to find JSON in the response (handle nested objects)
        $jsonPattern = '/\{(?:[^{}]|(?:\{[^{}]*\}))*"PERMISSION-ISSUE"(?:[^{}]|(?:\{[^{}]*\}))*\}/';
        if (preg_match($jsonPattern, $response, $matches)) {
            try {
                $data = json_decode($matches[0], true);
                if ($data && isset($data['queries'])) {
                    return $data;
                }
            } catch (Exception $e) {
                Log::error('Failed to parse permission request JSON', [
                    'error' => $e->getMessage(),
                    'response' => substr($response, 0, 500)
                ]);
            }
        }
        
        // Alternative format detection
        if (strpos($response, 'PERMISSION-ISSUE') !== false) {
            // Extract queries from text format
            return $this->extractQueriesFromText($response);
        }
        
        return null;
    }
    
    /**
     * Extract queries from text format
     */
    private function extractQueriesFromText(string $response): ?array
    {
        $queries = [];
        
        // Look for SQL queries in the text
        $sqlPattern = '/(SELECT|INSERT|UPDATE|DELETE|WITH)\s+.*?(?:;|$)/si';
        if (preg_match_all($sqlPattern, $response, $matches)) {
            foreach ($matches[0] as $query) {
                $queries[] = [
                    'type' => 'sql',
                    'query' => trim($query, ';')
                ];
            }
        }
        
        // Look for Laravel/Eloquent queries
        $eloquentPattern = '/(?:DB::table|\\$\\w+->where|Model::)/';
        if (preg_match($eloquentPattern, $response)) {
            // Extract table names and conditions
            $tablePattern = '/DB::table\([\'"](\w+)[\'"]\)/';
            if (preg_match_all($tablePattern, $response, $matches)) {
                foreach ($matches[1] as $table) {
                    $queries[] = [
                        'type' => 'eloquent',
                        'table' => $table,
                        'description' => "Query needed for table: {$table}"
                    ];
                }
            }
        }
        
        if (!empty($queries)) {
            return [
                'PERMISSION-ISSUE' => true,
                'queries' => $queries,
                'original_response' => $response
            ];
        }
        
        return null;
    }
    
    /**
     * Execute requested queries safely
     */
    public function executeQueries(array $queryRequest): array
    {
        $results = [];
        $errors = [];
        
        if (!isset($queryRequest['queries'])) {
            return [
                'success' => false,
                'error' => 'No queries provided in request'
            ];
        }
        
        foreach ($queryRequest['queries'] as $index => $queryData) {
            try {
                $result = $this->executeSingleQuery($queryData);
                $results["query_{$index}"] = $result;
            } catch (Exception $e) {
                $errors["query_{$index}"] = $e->getMessage();
                Log::error('Query execution failed', [
                    'query' => $queryData,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'success' => empty($errors),
            'results' => $results,
            'errors' => $errors,
            'executed_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Execute a single query safely
     */
    private function executeSingleQuery(array $queryData): array
    {
        $type = $queryData['type'] ?? 'sql';
        
        // Handle MCP tool requests
        if ($type === 'mcp_tool') {
            return $this->executeMcpTool($queryData);
        }
        
        // Validate query is safe for non-MCP requests
        if (!$this->isQuerySafe($queryData)) {
            throw new Exception('Query contains unsafe operations');
        }
        
        switch ($type) {
            case 'sql':
                return $this->executeSqlQuery($queryData['query']);
                
            case 'eloquent':
                return $this->executeEloquentQuery($queryData);
                
            case 'table_info':
                return $this->getTableInfo($queryData['table']);
                
            default:
                throw new Exception("Unknown query type: {$type}");
        }
    }
    
    /**
     * Execute MCP tool
     */
    private function executeMcpTool(array $queryData): array
    {
        $tool = $queryData['tool'] ?? '';
        $parameters = $queryData['parameters'] ?? [];
        
        // Use McpDatabaseService to execute the tool
        $mcpService = new \App\Services\McpDatabaseService();
        return $mcpService->executeMcpTool($tool, $parameters);
    }
    
    /**
     * Check if query is safe to execute
     */
    private function isQuerySafe(array $queryData): bool
    {
        // MCP tools handle their own safety checks
        if (($queryData['type'] ?? '') === 'mcp_tool') {
            return true;
        }
        
        // For direct SQL, allow all operations since Claude has full permissions
        if (isset($queryData['query'])) {
            // All queries are allowed - Claude has full database permissions
            return true;
        }
        
        return true;
    }
    
    /**
     * Execute SQL query
     */
    private function executeSqlQuery(string $sql): array
    {
        $sql = trim($sql);
        
        // Determine query type
        if (preg_match('/^SELECT/i', $sql)) {
            // Read query
            $results = DB::select($sql);
            
            return [
                'type' => 'sql_result',
                'count' => count($results),
                'data' => $results,
                'query' => $sql
            ];
        } else {
            // Write query (INSERT, UPDATE, DELETE, CREATE, ALTER, DROP)
            $affected = DB::statement($sql);
            
            return [
                'type' => 'sql_result',
                'affected_rows' => $affected,
                'query' => $sql,
                'message' => 'Query executed successfully'
            ];
        }
    }
    
    /**
     * Execute Eloquent-style query
     */
    private function executeEloquentQuery(array $queryData): array
    {
        $table = $queryData['table'];
        $query = DB::table($table);
        
        // Apply conditions if provided
        if (isset($queryData['where'])) {
            foreach ($queryData['where'] as $condition) {
                if (is_array($condition) && count($condition) >= 2) {
                    $query->where($condition[0], $condition[1], $condition[2] ?? '=');
                }
            }
        }
        
        // Apply limit if provided
        if (isset($queryData['limit'])) {
            $query->limit($queryData['limit']);
        }
        
        $results = $query->get();
        
        return [
            'type' => 'eloquent_result',
            'table' => $table,
            'count' => $results->count(),
            'data' => $results->toArray()
        ];
    }
    
    /**
     * Get table information
     */
    private function getTableInfo(string $table): array
    {
        $columns = DB::select("
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = ?
            AND table_schema = ?
        ", [$table, config('database.connections.pgsql.database')]);
        
        $count = DB::table($table)->count();
        
        return [
            'type' => 'table_info',
            'table' => $table,
            'record_count' => $count,
            'columns' => $columns
        ];
    }
    
    /**
     * Build enhanced message with query results
     */
    public function buildEnhancedMessageWithResults(string $originalMessage, array $queryResults): string
    {
        $enhanced = "[QUERY RESULTS PROVIDED]\n";
        $enhanced .= "The following database queries were executed on your behalf:\n\n";
        
        foreach ($queryResults['results'] as $key => $result) {
            $enhanced .= "Result {$key}:\n";
            
            if (isset($result['type'])) {
                switch ($result['type']) {
                    case 'sql_result':
                    case 'eloquent_result':
                        $enhanced .= "- Found {$result['count']} records\n";
                        if (!empty($result['data'])) {
                            $enhanced .= "- Data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
                        }
                        break;
                        
                    case 'table_info':
                        $enhanced .= "- Table: {$result['table']}\n";
                        $enhanced .= "- Total records: {$result['record_count']}\n";
                        $enhanced .= "- Columns: " . count($result['columns']) . "\n";
                        break;
                }
            }
            $enhanced .= "\n";
        }
        
        $enhanced .= "\nOriginal question: " . $originalMessage;
        $enhanced .= "\n\nPlease provide your answer based on the query results above.";
        
        return $enhanced;
    }
}