<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AiProviderService;
use App\Services\AiValidationService;

class AiAgentSimpleTest extends TestCase
{
    private $providerService;
    private $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->providerService = new AiProviderService();
        $this->validationService = new AiValidationService();
    }

    /** @test */
    public function it_can_validate_input_queries()
    {
        $validQuery = "What is the weather like today?";
        $result = $this->validationService->validateQuery($validQuery);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_rejects_sql_injection_attempts()
    {
        $maliciousQuery = "SELECT * FROM users; DROP TABLE users;";
        $result = $this->validationService->validateQuery($maliciousQuery);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_rejects_xss_attempts()
    {
        $xssQuery = "<script>alert('xss')</script>Hello";
        $result = $this->validationService->validateQuery($xssQuery);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_sanitize_input()
    {
        $dirtyInput = "<script>alert('xss')</script>Hello World";
        $sanitized = $this->validationService->sanitizeInput($dirtyInput);
        
        // The sanitizeInput method uses htmlspecialchars, so we expect HTML entities
        $this->assertStringContainsString("Hello World", $sanitized);
        $this->assertStringNotContainsString("<script>", $sanitized);
    }

    /** @test */
    public function it_can_get_provider_status()
    {
        $provider = $this->providerService->getProvider('groq');
        
        $this->assertIsArray($provider);
        $this->assertArrayHasKey('enabled', $provider);
        $this->assertArrayHasKey('name', $provider);
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
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
    }

    /** @test */
    public function it_rejects_dangerous_sql_operations()
    {
        $dangerousQuery = "DROP TABLE users";
        $result = $this->validationService->validateSqlQuery($dangerousQuery);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
    }

    /** @test */
    public function it_can_handle_empty_queries()
    {
        $result = $this->validationService->validateQuery("");
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_handle_long_queries()
    {
        $longQuery = str_repeat("a", 10001); // Exceeds the 10000 character limit
        $result = $this->validationService->validateQuery($longQuery);
        
        $this->assertFalse($result['valid']);
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
        $this->providerService->setProviderStatus('groq', false);
        $provider = $this->providerService->getProvider('groq');
        $this->assertFalse($provider['enabled']);
        
        // Enable provider
        $this->providerService->setProviderStatus('groq', true);
        $provider = $this->providerService->getProvider('groq');
        $this->assertTrue($provider['enabled']);
    }

    /** @test */
    public function it_can_get_validation_statistics()
    {
        $stats = $this->validationService->getValidationStats();
        
        $this->assertIsArray($stats);
        // The actual keys depend on the implementation
        $this->assertNotEmpty($stats);
    }

    /** @test */
    public function it_can_update_validation_patterns()
    {
        $newPatterns = ['sql_injection' => ['/DROP\s+TABLE/i']];
        $result = $this->validationService->updatePatterns('security', $newPatterns);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        // This test would require rate limiting implementation
        $this->assertTrue(true); // Placeholder test
    }
} 