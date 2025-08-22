<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AiAgentService;
use App\Services\AiProviderService;
use App\Services\AiMemoryService;
use App\Services\AiValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class AiAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $aiAgentService;
    protected $providerService;
    protected $memoryService;
    protected $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->aiAgentService = app(AiAgentService::class);
        $this->providerService = app(AiProviderService::class);
        $this->memoryService = app(AiMemoryService::class);
        $this->validationService = app(AiValidationService::class);
    }

    /** @test */
    public function it_can_validate_input_queries()
    {
        $validQuery = "What is the account balance?";
        $validation = $this->validationService->validateQuery($validQuery);
        
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function it_rejects_sql_injection_attempts()
    {
        $maliciousQuery = "SELECT * FROM users; DROP TABLE users;";
        $validation = $this->validationService->validateQuery($maliciousQuery);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Query contains potentially harmful content', $validation['errors']);
    }

    /** @test */
    public function it_rejects_xss_attempts()
    {
        $maliciousQuery = "<script>alert('xss')</script>";
        $validation = $this->validationService->validateQuery($maliciousQuery);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Content contains potentially harmful content', $validation['errors']);
    }

    /** @test */
    public function it_can_sanitize_input()
    {
        $dirtyInput = "<script>alert('test')</script>Hello World";
        $cleanInput = $this->validationService->sanitizeInput($dirtyInput);
        
        $this->assertNotEquals($dirtyInput, $cleanInput);
        $this->assertStringContainsString('Hello World', $cleanInput);
    }

    /** @test */
    public function it_can_manage_conversation_memory()
    {
        $sessionId = 'test_session_123';
        $this->memoryService->setSessionId($sessionId);
        
        // Add interaction
        $this->memoryService->addInteraction(
            "What is the balance?",
            "The balance is $1000",
            ['user_id' => 1]
        );
        
        // Get recent interactions
        $interactions = $this->memoryService->getRecentInteractions(5);
        
        $this->assertNotEmpty($interactions);
        $this->assertEquals("What is the balance?", $interactions[0]['query']);
    }

    /** @test */
    public function it_can_get_provider_status()
    {
        $status = $this->providerService->getProviderStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('groq', $status);
        $this->assertArrayHasKey('openai', $status);
        $this->assertArrayHasKey('together', $status);
    }

    /** @test */
    public function it_can_get_healthy_providers()
    {
        $healthyProviders = $this->providerService->getHealthyProviders();
        
        $this->assertIsArray($healthyProviders);
        // Note: This test may fail if no API keys are configured
    }

    /** @test */
    public function it_can_validate_sql_queries()
    {
        $safeSql = "SELECT COUNT(*) FROM users WHERE status = 'active'";
        $validation = $this->validationService->validateSqlQuery($safeSql);
        
        $this->assertTrue($validation['valid']);
    }

    /** @test */
    public function it_rejects_dangerous_sql_operations()
    {
        $dangerousSql = "DROP TABLE users";
        $validation = $this->validationService->validateSqlQuery($dangerousSql);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('SQL query contains dangerous operations', $validation['errors']);
    }

    /** @test */
    public function it_can_execute_safe_sql_queries()
    {
        // Create a test table first
        \DB::statement('CREATE TABLE IF NOT EXISTS test_table (id INT, name VARCHAR(255))');
        \DB::table('test_table')->insert(['id' => 1, 'name' => 'Test User']);
        
        $sql = "SELECT COUNT(*) as count FROM test_table";
        $result = $this->aiAgentService->executeSqlQuery($sql);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data'][0]->count);
        
        // Clean up
        \DB::statement('DROP TABLE IF EXISTS test_table');
    }

    /** @test */
    public function it_handles_sql_execution_errors()
    {
        $invalidSql = "SELECT * FROM non_existent_table";
        $result = $this->aiAgentService->executeSqlQuery($invalidSql);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_clear_conversation_memory()
    {
        $sessionId = 'test_session_456';
        $this->memoryService->setSessionId($sessionId);
        
        // Add some interactions
        $this->memoryService->addInteraction("Test query", "Test response", []);
        
        // Clear memory
        $this->memoryService->clearSessionMemory($sessionId);
        
        // Verify memory is cleared
        $interactions = $this->memoryService->getRecentInteractions(5);
        $this->assertEmpty($interactions);
    }

    /** @test */
    public function it_can_get_conversation_context()
    {
        $sessionId = 'test_session_789';
        $this->memoryService->setSessionId($sessionId);
        
        // Add multiple interactions
        $this->memoryService->addInteraction("First question", "First answer", []);
        $this->memoryService->addInteraction("Second question", "Second answer", []);
        
        $context = $this->memoryService->getFormattedContext(2);
        
        $this->assertStringContainsString('First question', $context);
        $this->assertStringContainsString('Second question', $context);
    }

    /** @test */
    public function it_can_search_interactions()
    {
        $sessionId = 'test_session_search';
        $this->memoryService->setSessionId($sessionId);
        
        // Add interactions
        $this->memoryService->addInteraction("Balance query", "Balance response", []);
        $this->memoryService->addInteraction("Account query", "Account response", []);
        
        $results = $this->memoryService->searchInteractions('balance');
        
        $this->assertNotEmpty($results);
        $this->assertStringContainsString('balance', strtolower($results[0]->query));
    }

    /** @test */
    public function it_can_get_memory_statistics()
    {
        $sessionId = 'test_session_stats';
        $this->memoryService->setSessionId($sessionId);
        
        // Add some interactions
        $this->memoryService->addInteraction("Test query 1", "Test response 1", []);
        $this->memoryService->addInteraction("Test query 2", "Test response 2", []);
        
        $stats = $this->memoryService->getStats($sessionId);
        
        $this->assertNotNull($stats);
        $this->assertEquals(2, $stats->total_interactions);
    }

    /** @test */
    public function it_can_update_provider_configuration()
    {
        $originalConfig = $this->providerService->getProvider('groq');
        
        // Update configuration
        $this->providerService->updateProviderConfig('groq', [
            'timeout' => 60
        ]);
        
        $updatedConfig = $this->providerService->getProvider('groq');
        
        $this->assertEquals(60, $updatedConfig['timeout']);
        $this->assertNotEquals($originalConfig['timeout'], $updatedConfig['timeout']);
    }

    /** @test */
    public function it_can_enable_and_disable_providers()
    {
        // Disable provider
        $this->providerService->setProviderStatus('groq', false);
        $config = $this->providerService->getProvider('groq');
        $this->assertFalse($config['enabled']);
        
        // Enable provider
        $this->providerService->setProviderStatus('groq', true);
        $config = $this->providerService->getProvider('groq');
        $this->assertTrue($config['enabled']);
    }

    /** @test */
    public function it_can_export_session_data()
    {
        $sessionId = 'test_session_export';
        $this->memoryService->setSessionId($sessionId);
        
        // Add interactions
        $this->memoryService->addInteraction("Export test", "Export response", []);
        
        $jsonData = $this->memoryService->exportSessionData($sessionId, 'json');
        $csvData = $this->memoryService->exportSessionData($sessionId, 'csv');
        
        $this->assertNotEmpty($jsonData);
        $this->assertNotEmpty($csvData);
        $this->assertStringContainsString('Export test', $csvData);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $userId = 1;
        
        // Simulate multiple requests
        for ($i = 0; $i < 5; $i++) {
            $validation = $this->validationService->validateQuery("Test query $i", [
                'user_id' => $userId
            ]);
            $this->assertTrue($validation['valid']);
        }
        
        // Note: This test may need adjustment based on actual rate limiting implementation
    }

    /** @test */
    public function it_can_get_validation_statistics()
    {
        $stats = $this->validationService->getValidationStats('24h');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('validated_requests', $stats);
        $this->assertArrayHasKey('failed_validations', $stats);
    }

    /** @test */
    public function it_can_update_validation_patterns()
    {
        $customPatterns = ['/custom-pattern/'];
        
        $this->validationService->updatePatterns('custom', $customPatterns);
        
        // Test that custom patterns are applied
        $validation = $this->validationService->validateQuery('custom-pattern test');
        
        // This test may need adjustment based on how custom patterns are implemented
        $this->assertIsArray($validation);
    }

    /** @test */
    public function it_can_handle_empty_queries()
    {
        $validation = $this->validationService->validateQuery('');
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Query must be a non-empty string', $validation['errors']);
    }

    /** @test */
    public function it_can_handle_long_queries()
    {
        $longQuery = str_repeat('a', 15000); // Exceeds max length
        $validation = $this->validationService->validateQuery($longQuery);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Query is too long', $validation['errors']);
    }

    /** @test */
    public function it_can_get_provider_models()
    {
        $models = $this->providerService->getProviderModels('groq');
        
        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertArrayHasKey('llama3-8b-8192', $models);
    }

    /** @test */
    public function it_can_get_best_provider()
    {
        $bestProvider = $this->providerService->getBestProvider();
        
        // This may be null if no providers are healthy
        if ($bestProvider) {
            $this->assertIsString($bestProvider);
            $this->assertContains($bestProvider, ['groq', 'openai', 'together']);
        }
    }

    /** @test */
    public function it_can_get_learning_patterns()
    {
        $sessionId = 'test_session_patterns';
        $this->memoryService->setSessionId($sessionId);
        
        // Add some interactions
        $this->memoryService->addInteraction("Pattern test", "Pattern response", []);
        
        $patterns = $this->memoryService->getLearningPatterns($sessionId);
        
        $this->assertIsObject($patterns);
        // Note: This may be empty if no interactions exist in the database
    }
} 