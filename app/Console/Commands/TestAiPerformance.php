<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocalClaudeService;
use App\Services\DirectClaudeService;
use Illuminate\Support\Facades\Cache;

class TestAiPerformance extends Command
{
    protected $signature = 'ai:test-performance {--clear-cache : Clear all caches before testing}';
    protected $description = 'Test AI service performance improvements';

    public function handle()
    {
        $this->info('🚀 AI Performance Test Suite');
        $this->info('============================');
        
        // Clear caches if requested
        if ($this->option('clear-cache')) {
            $this->info('Clearing caches...');
            Cache::flush();
            $localService = new LocalClaudeService();
            $localService->clearConversation();
            $this->info('✅ Caches cleared');
        }
        
        // Test 1: Simple query
        $this->testQuery('Simple Math', 'What is 2 + 2?');
        
        // Test 2: Database query
        $this->testQuery('Database Query', 'How many clients are in the system?');
        
        // Test 3: Complex query
        $this->testQuery('Complex Analysis', 'Show me the top 5 accounts by balance');
        
        // Summary
        $this->info("\n📊 Performance Summary");
        $this->info('======================');
        $this->table(
            ['Test', 'Time (seconds)', 'Status'],
            $this->results
        );
        
        $avgTime = collect($this->results)->avg('1');
        $this->info("Average response time: " . round($avgTime, 2) . " seconds");
        
        // Check context file
        $contextFile = base_path('zona_ai/context.md');
        if (file_exists($contextFile)) {
            $size = round(filesize($contextFile) / 1024, 2);
            $this->info("✅ Context file exists: {$size} KB");
        } else {
            $this->error("❌ Context file missing!");
        }
    }
    
    private $results = [];
    
    private function testQuery($name, $query)
    {
        $this->info("\n🔍 Testing: {$name}");
        $this->info("Query: {$query}");
        
        try {
            $service = new DirectClaudeService();
            
            $startTime = microtime(true);
            $response = $service->processMessage($query, [
                'user_name' => 'Test User',
                'session_id' => 'test_' . uniqid()
            ]);
            $endTime = microtime(true);
            
            $duration = round($endTime - $startTime, 2);
            
            if ($response['success']) {
                $this->info("✅ Success in {$duration} seconds");
                $this->info("Response preview: " . substr($response['message'], 0, 100) . "...");
                $this->results[] = [$name, $duration, '✅ Success'];
            } else {
                $this->error("❌ Failed: " . $response['message']);
                $this->results[] = [$name, $duration, '❌ Failed'];
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->results[] = [$name, 0, '❌ Error'];
        }
    }
}