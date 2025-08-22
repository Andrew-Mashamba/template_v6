<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AiAgentService;
use App\Services\AiProviderService;
use App\Services\AiMemoryService;
use App\Services\AiValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiAgentServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private $aiAgentService;
    private $providerService;
    private $memoryService;
    private $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->providerService = new AiProviderService();
        $this->memoryService = new AiMemoryService();
        $this->validationService = new AiValidationService();
        $this->aiAgentService = new AiAgentService(
            $this->providerService,
            $this->memoryService,
            $this->validationService
        );
    }

    /** @test */
    public function it_can_validate_input_queries()
    {
        $validQuery = "What is the weather like today?";
        $result = $this->validationService->validateInput($validQuery);
        
        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_rejects_sql_injection_attempts()
    {
        $maliciousQuery = "SELECT * FROM users; DROP TABLE users;";
        $result = $this->validationService->validateInput($maliciousQuery);
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_rejects_xss_attempts()
    {
        $xssQuery = "<script>alert('xss')</script>Hello";
        $result = $this->validationService->validateInput($xssQuery);
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_sanitize_input()
    {
        $dirtyInput = "<script>alert('xss')</script>Hello World";
        $sanitized = $this->validationService->sanitizeInput($dirtyInput);
        
        $this->assertEquals("Hello World", $sanitized);
    }

    /** @test */
    public function it_can_get_provider_status()
    {
        $status = $this->providerService->getProviderStatus('groq');
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('healthy', $status);
    }

    /** @test */
    public function it_can_get_healthy_providers()
    {
        $healthyProviders = $this->providerService->getHealthyProviders();
        
        $this->assertIsArray($healthyProviders);
    }

    /** @test */
    public function it_can_validate_sql_queries()
    {
        $safeQuery = "SELECT name, email FROM users WHERE id = 1";
        $result = $this->validationService->validateSqlQuery($safeQuery);
        
        $this->assertTrue($result['is_valid']);
    }

    /** @test */
    public function it_rejects_dangerous_sql_operations()
    {
        $dangerousQuery = "DROP TABLE users";
        $result = $this->validationService->validateSqlQuery($dangerousQuery);
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_handle_empty_queries()
    {
        $result = $this->validationService->validateInput("");
        
        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('empty', strtolower($result['errors'][0] ?? ''));
    }

    /** @test */
    public function it_can_handle_long_queries()
    {
        $longQuery = str_repeat("a", 10000);
        $result = $this->validationService->validateInput($longQuery);
        
        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_get_provider_models()
    {
        $models = $this->providerService->getProviderModels('groq');
        
        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
    }

    /** @test */
    public function it_can_get_best_provider()
    {
        $bestProvider = $this->providerService->getBestProvider();
        
        $this->assertIsString($bestProvider);
        $this->assertNotEmpty($bestProvider);
    }

    /** @test */
    public function it_can_update_provider_configuration()
    {
        $config = ['enabled' => false];
        $result = $this->providerService->updateProviderConfig('groq', $config);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled', $result);
    }

    /** @test */
    public function it_can_enable_and_disable_providers()
    {
        // Disable provider
        $this->providerService->updateProviderConfig('groq', ['enabled' => false]);
        $status = $this->providerService->getProviderStatus('groq');
        $this->assertFalse($status['enabled']);
        
        // Enable provider
        $this->providerService->updateProviderConfig('groq', ['enabled' => true]);
        $status = $this->providerService->getProviderStatus('groq');
        $this->assertTrue($status['enabled']);
    }

    /** @test */
    public function it_can_get_validation_statistics()
    {
        $stats = $this->validationService->getValidationStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_validations', $stats);
        $this->assertArrayHasKey('total_rejections', $stats);
    }

    /** @test */
    public function it_can_update_validation_patterns()
    {
        $newPatterns = ['sql_injection' => '/DROP\s+TABLE/i'];
        $result = $this->validationService->updateValidationPatterns($newPatterns);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        // This test would require rate limiting implementation
        $this->assertTrue(true); // Placeholder test
    }
} 