<?php

namespace Tests\Feature\API;

use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $apiToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiToken = config('services.api.token', 'test-token');
    }

    public function test_can_create_log()
    {
        $data = [
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1',
            'payload' => ['browser' => 'Chrome'],
            'is_sensitive' => false
        ];

        $response = $this->postJson('/api/v1/logs', $data, [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'user_id',
                        'system',
                        'action_type',
                        'resource',
                        'result',
                        'ip',
                        'hash'
                    ]
                ]);
    }

    public function test_cannot_create_log_without_token()
    {
        $data = [
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1'
        ];

        $response = $this->postJson('/api/v1/logs', $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_can_search_logs()
    {
        // Cria alguns logs para teste
        Log::create([
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1',
            'timestamp' => now()
        ]);

        $response = $this->getJson('/api/v1/logs?system=auth', [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertOk()
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        '*' => [
                            'user_id',
                            'system',
                            'action_type',
                            'resource',
                            'result',
                            'ip'
                        ]
                    ]
                ]);
    }

    public function test_can_verify_log_integrity()
    {
        $log = Log::create([
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1',
            'timestamp' => now()
        ]);

        $response = $this->getJson("/api/v1/logs/{$log->id}/verify", [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertOk()
                ->assertJsonStructure([
                    'message',
                    'is_valid'
                ]);
    }

    public function test_can_export_logs()
    {
        Log::create([
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1',
            'timestamp' => now()
        ]);

        $response = $this->getJson('/api/v1/logs/export?format=csv', [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertOk()
                ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/logs', [], [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors([
                    'user_id',
                    'system',
                    'action_type',
                    'resource',
                    'result',
                    'ip'
                ]);
    }

    public function test_validates_ip_format()
    {
        $data = [
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => 'invalid-ip'
        ];

        $response = $this->postJson('/api/v1/logs', $data, [
            'X-API-Token' => $this->apiToken
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['ip']);
    }
} 