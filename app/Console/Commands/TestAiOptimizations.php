<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocalClaudeService;
use App\Services\ClaudeProcessManager;
use App\Services\ClaudeQueryQueue;
use Illuminate\Support\Facades\Cache;

class TestAiOptimizations extends Command
{
    protected $signature = 'ai:test-optimizations 
                          {--streaming : Test streaming functionality}
                          {--persistent : Test persistent process}
                          {--queue : Test query queue}
                          {--all : Run all tests}';
                          
    protected $description = 'Test all AI performance optimizations';

    private $results = [];

    public function handle()
    {
        $this->info('🚀 AI Optimization Test Suite');
        $this->info('===============================');
        
        $runAll = $this->option('all');
        
        // Test 1: Basic Performance
        $this->testBasicPerformance();
        
        // Test 2: Persistent Process
        if ($runAll || $this->option('persistent')) {
            $this->testPersistentProcess();
        }
        
        // Test 3: Streaming
        if ($runAll || $this->option('streaming')) {
            $this->testStreaming();
        }
        
        // Test 4: Query Queue
        if ($runAll || $this->option('queue')) {
            $this->testQueryQueue();
        }
        
        // Display results
        $this->displayResults();
    }
    
    private function testBasicPerformance()
    {
        $this->info("\n📊 Testing Basic Performance");
        $this->info("----------------------------");
        
        $service = new LocalClaudeService();
        
        // Test without persistent process
        $service->setPersistentMode(false);
        
        $startTime = microtime(true);
        $response = $service->sendMessage("What is 2 + 2?");
        $duration = round(microtime(true) - $startTime, 2);
        
        if ($response['success']) {
            $this->info("✅ Per-request mode: {$duration}s");
            $this->results[] = ['Per-request mode', "{$duration}s", '✅'];
        } else {
            $this->error("❌ Per-request mode failed");
            $this->results[] = ['Per-request mode', 'Failed', '❌'];
        }
        
        // Test with persistent process
        $service->setPersistentMode(true);
        
        // Pre-warm
        $this->info("Pre-warming Claude process...");
        $prewarmStart = microtime(true);
        $service->prewarm();
        $prewarmTime = round(microtime(true) - $prewarmStart, 2);
        $this->info("✅ Pre-warm completed: {$prewarmTime}s");
        
        // Test with warm process
        $startTime = microtime(true);
        $response = $service->sendMessage("What is 3 + 3?");
        $duration = round(microtime(true) - $startTime, 2);
        
        if ($response['success']) {
            $this->info("✅ Persistent mode (warm): {$duration}s");
            $this->results[] = ['Persistent mode (warm)', "{$duration}s", '✅'];
            
            // Calculate improvement
            $improvement = isset($this->results[0]) ? 
                round((floatval($this->results[0][1]) - $duration) / floatval($this->results[0][1]) * 100, 1) : 0;
            $this->info("📈 Performance improvement: {$improvement}%");
        } else {
            $this->error("❌ Persistent mode failed");
            $this->results[] = ['Persistent mode', 'Failed', '❌'];
        }
    }
    
    private function testPersistentProcess()
    {
        $this->info("\n🔄 Testing Persistent Process");
        $this->info("-----------------------------");
        
        $manager = ClaudeProcessManager::getInstance();
        $status = $manager->getStatus();
        
        $this->info("Session ID: " . $status['session_id']);
        $this->info("Process PID: " . ($status['pid'] ?? 'N/A'));
        $this->info("Is Ready: " . ($status['is_ready'] ? 'Yes' : 'No'));
        
        // Test context retention
        $this->info("\nTesting context retention...");
        
        // Send first message
        $response1 = $manager->sendMessage("My name is TestUser. Remember this.");
        if ($response1['success']) {
            $this->info("✅ First message sent");
        }
        
        // Send second message referencing first
        $response2 = $manager->sendMessage("What is my name?");
        if ($response2['success'] && stripos($response2['message'], 'TestUser') !== false) {
            $this->info("✅ Context retained across messages!");
            $this->results[] = ['Context retention', 'Success', '✅'];
        } else {
            $this->error("❌ Context not retained");
            $this->results[] = ['Context retention', 'Failed', '❌'];
        }
        
        // Check conversation history
        $history = $manager->getHistory();
        $this->info("Conversation history: " . count($history) . " messages");
    }
    
    private function testStreaming()
    {
        $this->info("\n🌊 Testing Response Streaming");
        $this->info("-----------------------------");
        
        $service = new LocalClaudeService();
        $sessionId = 'test_stream_' . uniqid();
        
        $chunks = [];
        $streamCallback = function($chunk) use (&$chunks) {
            $chunks[] = $chunk;
        };
        
        $startTime = microtime(true);
        $response = $service->sendMessage("Count from 1 to 5 slowly", [
            'enable_streaming' => true,
            'stream_to_session' => $sessionId,
            'stream_callback' => $streamCallback
        ]);
        $duration = round(microtime(true) - $startTime, 2);
        
        if ($response['success']) {
            $this->info("✅ Streaming completed in {$duration}s");
            $this->info("Received " . count($chunks) . " chunks");
            
            // Check cache for streamed content
            $streamKey = "claude_stream_{$sessionId}";
            $cachedContent = Cache::get($streamKey);
            if ($cachedContent) {
                $this->info("✅ Stream cached: " . strlen($cachedContent) . " bytes");
            }
            
            $this->results[] = ['Response streaming', "{$duration}s / " . count($chunks) . " chunks", '✅'];
        } else {
            $this->error("❌ Streaming failed");
            $this->results[] = ['Response streaming', 'Failed', '❌'];
        }
    }
    
    private function testQueryQueue()
    {
        $this->info("\n📋 Testing Query Queue");
        $this->info("----------------------");
        
        $queue = ClaudeQueryQueue::getInstance();
        
        // Add multiple queries
        $queryIds = [];
        $queries = [
            "What is 1 + 1?",
            "What is 2 + 2?",
            "What is 3 + 3?"
        ];
        
        $this->info("Adding " . count($queries) . " queries to queue...");
        
        foreach ($queries as $query) {
            $queryIds[] = $queue->addQuery($query);
        }
        
        // Wait for completion
        $this->info("Waiting for queue processing...");
        $allCompleted = true;
        
        foreach ($queryIds as $index => $queryId) {
            $result = $queue->waitForQuery($queryId, 30);
            
            if ($result && $result['status'] === 'completed') {
                $this->info("✅ Query " . ($index + 1) . " completed in " . 
                    round($result['processing_time'] ?? 0, 2) . "s");
            } else {
                $this->error("❌ Query " . ($index + 1) . " failed");
                $allCompleted = false;
            }
        }
        
        if ($allCompleted) {
            $this->results[] = ['Query queue', count($queries) . ' queries processed', '✅'];
        } else {
            $this->results[] = ['Query queue', 'Some queries failed', '⚠️'];
        }
        
        // Show queue stats
        $stats = $queue->getStats();
        $this->info("\nQueue Statistics:");
        $this->info("- Queue length: " . $stats['queue_length']);
        $this->info("- Is processing: " . ($stats['is_processing'] ? 'Yes' : 'No'));
    }
    
    private function displayResults()
    {
        $this->info("\n📊 Test Results Summary");
        $this->info("=======================");
        
        if (empty($this->results)) {
            $this->warn("No test results to display");
            return;
        }
        
        $this->table(
            ['Test', 'Result', 'Status'],
            $this->results
        );
        
        // Check context file
        $contextFile = base_path('zona_ai/context.md');
        if (file_exists($contextFile)) {
            $size = round(filesize($contextFile) / 1024, 2);
            $this->info("\n✅ Context file: {$size} KB");
        } else {
            $this->error("\n❌ Context file missing!");
        }
        
        // Performance recommendations
        $this->info("\n💡 Performance Recommendations:");
        $this->info("1. Use persistent mode for multiple queries");
        $this->info("2. Enable streaming for long responses");
        $this->info("3. Pre-warm Claude process on application startup");
        $this->info("4. Use query queue for batch processing");
        $this->info("5. Keep context.md file updated and concise");
    }
}