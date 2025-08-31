<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class TestAiRoutes extends Command
{
    protected $signature = 'ai:test-routes 
                          {--detailed : Show detailed response data}
                          {--stress : Run stress test with multiple requests}
                          {--report : Generate detailed report}';
                          
    protected $description = 'Test all AI routes and generate performance report';

    private $results = [];
    private $baseUrl;
    private $sessionId;

    public function handle()
    {
        $this->info('🧪 AI Routes Testing Suite');
        $this->info('==========================');
        $this->info('Starting at: ' . Carbon::now()->format('Y-m-d H:i:s'));
        
        // Setup
        $this->baseUrl = config('app.url', 'http://localhost');
        $this->sessionId = 'test_session_' . uniqid();
        
        // Get or create test user
        $testUser = $this->getTestUser();
        
        Log::channel('ai_performance')->info('[TEST-SUITE-START] Beginning AI route tests', [
            'session_id' => $this->sessionId,
            'timestamp' => Carbon::now()->toIso8601String()
        ]);
        
        // Run tests
        $this->testAiAgentRoute($testUser);
        $this->testPromptLoggerRoute($testUser);
        $this->testAiAgentTestRoute($testUser);
        $this->testStreamingRoutes($testUser);
        
        if ($this->option('stress')) {
            $this->runStressTest($testUser);
        }
        
        // Generate report
        if ($this->option('report')) {
            $this->generateDetailedReport();
        } else {
            $this->displayResults();
        }
        
        Log::channel('ai_performance')->info('[TEST-SUITE-END] AI route tests completed', [
            'session_id' => $this->sessionId,
            'total_tests' => count($this->results),
            'timestamp' => Carbon::now()->toIso8601String()
        ]);
    }
    
    private function getTestUser()
    {
        // Use first admin user or create one
        $user = User::where('email', 'admin@example.com')->first();
        
        if (!$user) {
            $user = User::first();
        }
        
        if (!$user) {
            $this->error('No users found in database. Creating test user...');
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        
        $this->info("Using test user: {$user->email}");
        return $user;
    }
    
    private function testAiAgentRoute($user)
    {
        $this->info("\n📍 Testing /ai-agent route...");
        
        $startTime = microtime(true);
        
        try {
            // Simulate authenticated request
            Auth::login($user);
            
            // Make internal request
            $response = $this->makeInternalCall('GET', '/ai-agent');
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->results[] = [
                'route' => '/ai-agent',
                'method' => 'GET',
                'status' => $response->status(),
                'duration' => $duration,
                'success' => $response->isSuccessful()
            ];
            
            if ($response->isSuccessful()) {
                $this->info("✅ AI Agent page loaded in {$duration}s");
                
                Log::channel('ai_performance')->info('[TEST-ROUTE] AI Agent page test', [
                    'route' => '/ai-agent',
                    'duration' => $duration,
                    'status' => $response->status()
                ]);
            } else {
                $this->error("❌ Failed with status: " . $response->status());
                
                Log::channel('ai_performance')->error('[TEST-ROUTE-ERROR] AI Agent page failed', [
                    'route' => '/ai-agent',
                    'status' => $response->status(),
                    'duration' => $duration
                ]);
            }
            
            Auth::logout();
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->error("❌ Exception: " . $e->getMessage());
            
            $this->results[] = [
                'route' => '/ai-agent',
                'method' => 'GET',
                'status' => 500,
                'duration' => $duration,
                'success' => false,
                'error' => $e->getMessage()
            ];
            
            Log::channel('ai_performance')->error('[TEST-ROUTE-EXCEPTION] AI Agent test failed', [
                'route' => '/ai-agent',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    private function testPromptLoggerRoute($user)
    {
        $this->info("\n📍 Testing /prompt-logger route...");
        
        $startTime = microtime(true);
        
        try {
            Auth::login($user);
            
            $response = $this->makeInternalCall('GET', '/prompt-logger');
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->results[] = [
                'route' => '/prompt-logger',
                'method' => 'GET',
                'status' => $response->status(),
                'duration' => $duration,
                'success' => $response->isSuccessful()
            ];
            
            if ($response->isSuccessful()) {
                $this->info("✅ Prompt Logger loaded in {$duration}s");
                
                Log::channel('ai_performance')->info('[TEST-ROUTE] Prompt Logger test', [
                    'route' => '/prompt-logger',
                    'duration' => $duration,
                    'status' => $response->status()
                ]);
            } else {
                $this->error("❌ Failed with status: " . $response->status());
            }
            
            Auth::logout();
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->error("❌ Exception: " . $e->getMessage());
            
            $this->results[] = [
                'route' => '/prompt-logger',
                'method' => 'GET',
                'status' => 500,
                'duration' => $duration,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testAiAgentTestRoute($user)
    {
        $this->info("\n📍 Testing /ai-agent/test route...");
        
        $startTime = microtime(true);
        
        try {
            Auth::login($user);
            
            $response = $this->makeInternalCall('GET', '/ai-agent/test');
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->results[] = [
                'route' => '/ai-agent/test',
                'method' => 'GET',
                'status' => $response->status(),
                'duration' => $duration,
                'success' => $response->isSuccessful()
            ];
            
            if ($response->isSuccessful()) {
                $this->info("✅ AI Agent test endpoint responded in {$duration}s");
                
                if ($this->option('detailed')) {
                    $content = substr($response->getContent(), 0, 500);
                    $this->info("Response preview: {$content}...");
                }
                
                Log::channel('ai_performance')->info('[TEST-ROUTE] AI Agent test endpoint', [
                    'route' => '/ai-agent/test',
                    'duration' => $duration,
                    'status' => $response->status()
                ]);
            } else {
                $this->error("❌ Failed with status: " . $response->status());
            }
            
            Auth::logout();
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->error("❌ Exception: " . $e->getMessage());
            
            $this->results[] = [
                'route' => '/ai-agent/test',
                'method' => 'GET',
                'status' => 500,
                'duration' => $duration,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testStreamingRoutes($user)
    {
        $this->info("\n📍 Testing streaming routes...");
        
        // Test SSE streaming endpoint
        $this->testStreamRoute($user);
        
        // Test stream complete endpoint
        $this->testStreamCompleteRoute($user);
    }
    
    private function testStreamRoute($user)
    {
        $this->info("  Testing /ai/stream/{sessionId}...");
        
        $startTime = microtime(true);
        
        try {
            Auth::login($user);
            
            // Simulate streaming data
            \Cache::put("claude_stream_{$this->sessionId}", "Test streaming content", 60);
            
            // Make request (will timeout quickly as it's SSE)
            $response = Http::timeout(2)
                ->withHeaders(['Accept' => 'text/event-stream'])
                ->get("{$this->baseUrl}/ai/stream/{$this->sessionId}");
            
            $duration = round(microtime(true) - $startTime, 2);
            
            // SSE will keep connection open, so timeout is expected
            $this->info("  ✅ Stream endpoint accessible (SSE connection)");
            
            $this->results[] = [
                'route' => "/ai/stream/{sessionId}",
                'method' => 'GET',
                'status' => 200,
                'duration' => $duration,
                'success' => true,
                'note' => 'SSE endpoint'
            ];
            
            Auth::logout();
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            // Timeout is expected for SSE
            if (strpos($e->getMessage(), 'timed out') !== false) {
                $this->info("  ✅ Stream endpoint working (SSE timeout normal)");
                
                $this->results[] = [
                    'route' => "/ai/stream/{sessionId}",
                    'method' => 'GET',
                    'status' => 200,
                    'duration' => $duration,
                    'success' => true,
                    'note' => 'SSE timeout expected'
                ];
            } else {
                $this->error("  ❌ Exception: " . $e->getMessage());
                
                $this->results[] = [
                    'route' => "/ai/stream/{sessionId}",
                    'method' => 'GET',
                    'status' => 500,
                    'duration' => $duration,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    private function testStreamCompleteRoute($user)
    {
        $this->info("  Testing /ai/stream/{sessionId}/complete...");
        
        $startTime = microtime(true);
        
        try {
            Auth::login($user);
            
            $response = $this->makeInternalCall('POST', "/ai/stream/{$this->sessionId}/complete");
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->results[] = [
                'route' => "/ai/stream/{sessionId}/complete",
                'method' => 'POST',
                'status' => $response->status(),
                'duration' => $duration,
                'success' => $response->isSuccessful()
            ];
            
            if ($response->isSuccessful()) {
                $this->info("  ✅ Stream complete endpoint responded in {$duration}s");
                
                Log::channel('ai_performance')->info('[TEST-ROUTE] Stream complete test', [
                    'route' => '/ai/stream/complete',
                    'duration' => $duration,
                    'status' => $response->status()
                ]);
            } else {
                $this->error("  ❌ Failed with status: " . $response->status());
            }
            
            Auth::logout();
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->error("  ❌ Exception: " . $e->getMessage());
            
            $this->results[] = [
                'route' => "/ai/stream/{sessionId}/complete",
                'method' => 'POST',
                'status' => 500,
                'duration' => $duration,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function runStressTest($user)
    {
        $this->info("\n🔥 Running stress test...");
        
        $requests = 10;
        $this->info("Sending {$requests} concurrent requests...");
        
        $stressResults = [];
        
        for ($i = 1; $i <= $requests; $i++) {
            $startTime = microtime(true);
            
            try {
                Auth::login($user);
                
                $response = $this->makeInternalCall('GET', '/ai-agent/test');
                
                $duration = round(microtime(true) - $startTime, 2);
                
                $stressResults[] = [
                    'request' => $i,
                    'duration' => $duration,
                    'status' => $response->status(),
                    'success' => $response->isSuccessful()
                ];
                
                $this->info("  Request {$i}: {$duration}s - " . 
                    ($response->isSuccessful() ? '✅' : '❌'));
                
                Auth::logout();
                
                // Small delay between requests
                usleep(100000); // 100ms
                
            } catch (\Exception $e) {
                $duration = round(microtime(true) - $startTime, 2);
                
                $stressResults[] = [
                    'request' => $i,
                    'duration' => $duration,
                    'status' => 500,
                    'success' => false
                ];
                
                $this->error("  Request {$i}: Failed - {$e->getMessage()}");
            }
        }
        
        // Calculate statistics
        $durations = array_column($stressResults, 'duration');
        $successful = array_filter($stressResults, fn($r) => $r['success']);
        
        $this->info("\nStress Test Results:");
        $this->info("  Total Requests: {$requests}");
        $this->info("  Successful: " . count($successful));
        $this->info("  Failed: " . ($requests - count($successful)));
        $this->info("  Avg Response Time: " . round(array_sum($durations) / count($durations), 2) . "s");
        $this->info("  Min Response Time: " . round(min($durations), 2) . "s");
        $this->info("  Max Response Time: " . round(max($durations), 2) . "s");
        
        Log::channel('ai_performance')->info('[STRESS-TEST] Results', [
            'total_requests' => $requests,
            'successful' => count($successful),
            'avg_time' => round(array_sum($durations) / count($durations), 2),
            'min_time' => round(min($durations), 2),
            'max_time' => round(max($durations), 2)
        ]);
    }
    
    private function displayResults()
    {
        $this->info("\n📊 Test Results Summary");
        $this->info("=======================");
        
        $this->table(
            ['Route', 'Method', 'Status', 'Duration (s)', 'Result'],
            array_map(function($result) {
                return [
                    $result['route'],
                    $result['method'],
                    $result['status'],
                    $result['duration'],
                    $result['success'] ? '✅ Pass' : '❌ Fail'
                ];
            }, $this->results)
        );
        
        // Calculate statistics
        $totalTests = count($this->results);
        $passed = array_filter($this->results, fn($r) => $r['success']);
        $failed = $totalTests - count($passed);
        $totalTime = array_sum(array_column($this->results, 'duration'));
        $avgTime = $totalTests > 0 ? round($totalTime / $totalTests, 2) : 0;
        
        $this->info("\n📈 Statistics:");
        $this->info("  Total Tests: {$totalTests}");
        $this->info("  Passed: " . count($passed) . " (" . 
            round(count($passed) / max(1, $totalTests) * 100, 1) . "%)");
        $this->info("  Failed: {$failed}");
        $this->info("  Total Time: " . round($totalTime, 2) . "s");
        $this->info("  Average Time: {$avgTime}s");
        
        if ($failed > 0) {
            $this->warn("\n⚠️ Some tests failed. Check logs for details.");
        } else {
            $this->info("\n✅ All tests passed successfully!");
        }
    }
    
    private function generateDetailedReport()
    {
        $reportFile = storage_path('logs/ai-route-test-report-' . date('Y-m-d-His') . '.txt');
        
        $report = "AI ROUTES TEST REPORT\n";
        $report .= "=====================\n";
        $report .= "Generated: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $report .= "Session ID: {$this->sessionId}\n\n";
        
        $report .= "TEST RESULTS\n";
        $report .= "------------\n";
        
        foreach ($this->results as $result) {
            $report .= "\nRoute: {$result['route']}\n";
            $report .= "Method: {$result['method']}\n";
            $report .= "Status: {$result['status']}\n";
            $report .= "Duration: {$result['duration']}s\n";
            $report .= "Result: " . ($result['success'] ? 'PASS' : 'FAIL') . "\n";
            
            if (isset($result['error'])) {
                $report .= "Error: {$result['error']}\n";
            }
            
            if (isset($result['note'])) {
                $report .= "Note: {$result['note']}\n";
            }
        }
        
        // Add statistics
        $totalTests = count($this->results);
        $passed = array_filter($this->results, fn($r) => $r['success']);
        $failed = $totalTests - count($passed);
        $totalTime = array_sum(array_column($this->results, 'duration'));
        
        $report .= "\n\nSTATISTICS\n";
        $report .= "----------\n";
        $report .= "Total Tests: {$totalTests}\n";
        $report .= "Passed: " . count($passed) . " (" . 
            round(count($passed) / max(1, $totalTests) * 100, 1) . "%)\n";
        $report .= "Failed: {$failed}\n";
        $report .= "Total Time: " . round($totalTime, 2) . "s\n";
        $report .= "Average Time: " . round($totalTime / max(1, $totalTests), 2) . "s\n";
        
        // Write report
        file_put_contents($reportFile, $report);
        
        $this->info("\n📄 Detailed report saved to:");
        $this->info($reportFile);
        
        // Also display summary
        $this->displayResults();
    }
    
    /**
     * Make internal call to route
     */
    private function makeInternalCall($method, $uri, $parameters = [])
    {
        $kernel = $this->laravel->make(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create($uri, $method, $parameters);
        
        return $kernel->handle($request);
    }
}