<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AiAgentApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_send_message_and_receive_response()
    {
        $payload = [
            'query' => 'Hello, AI agent!'
        ];

        $response = $this->postJson('/api/ai-agent/message', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.response'));
    }

    /**
     * Test AI agent SQL-first approach
     */
    public function test_ai_agent_sql_first_approach()
    {
        $response = $this->postJson('/api/ai-agent/sql-question', [
            'question' => 'How many users are in the system?',
            'context' => ['test' => true],
            'options' => ['provider' => 'groq']
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'response',
                    'original_query',
                    'sql_queries',
                    'sql_results',
                    'timestamp',
                    'method'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        
        // Check what method was actually used
        $method = $response->json('data.method');
        $this->assertContains($method, ['sql_first', 'traditional']);
        
        if ($method === 'sql_first') {
            $this->assertNotEmpty($response->json('data.sql_queries'));
            $this->assertNotEmpty($response->json('data.response'));
        } else {
            // Traditional method should still provide a response
            $this->assertNotEmpty($response->json('data.response'));
        }
    }

    /**
     * Test AI agent fallback with SQL-first approach
     */
    public function test_ai_agent_sql_first_fallback()
    {
        // Test with a question that should trigger SQL-first approach
        $response = $this->postJson('/api/ai-agent/sql-question', [
            'question' => 'Show me the total number of clients',
            'context' => ['test' => true],
            'options' => ['provider' => 'groq']
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'response',
                    'original_query',
                    'sql_queries',
                    'sql_results',
                    'timestamp',
                    'method'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('sql_first', $response->json('data.method'));
        
        // Verify SQL queries were generated
        $sqlQueries = $response->json('data.sql_queries');
        $this->assertIsArray($sqlQueries);
        $this->assertNotEmpty($sqlQueries);
        
        // Verify SQL results were obtained
        $sqlResults = $response->json('data.sql_results');
        $this->assertIsArray($sqlResults);
        $this->assertNotEmpty($sqlResults);
    }
} 