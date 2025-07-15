<?php

namespace Tests\Unit\Services;

use App\Models\Log;
use App\Services\LogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class LogServiceTest extends TestCase
{
    use RefreshDatabase;

    private LogService $logService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logService = new LogService();
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

        $log = $this->logService->create($data);

        $this->assertInstanceOf(Log::class, $log);
        $this->assertEquals($data['user_id'], $log->user_id);
        $this->assertEquals($data['system'], $log->system);
        $this->assertNotNull($log->hash);
    }

    public function test_can_search_logs_with_filters()
    {
        // Cria alguns logs para teste
        $this->logService->create([
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1'
        ]);

        $this->logService->create([
            'user_id' => '456',
            'system' => 'payment',
            'action_type' => 'purchase',
            'resource' => 'order',
            'result' => 'success',
            'ip' => '127.0.0.1'
        ]);

        $filters = ['system' => 'auth'];
        $logs = $this->logService->search($filters);

        $this->assertEquals(1, $logs->count());
        $this->assertEquals('auth', $logs->first()->system);
    }

    public function test_verify_log_integrity()
    {
        $data = [
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1'
        ];

        $log = $this->logService->create($data);
        
        $isValid = $this->logService->verifyIntegrity($log);
        
        $this->assertTrue($isValid);
    }

    public function test_can_export_logs_to_csv()
    {
        $data = [
            'user_id' => '123',
            'system' => 'auth',
            'action_type' => 'login',
            'resource' => 'user',
            'result' => 'success',
            'ip' => '127.0.0.1'
        ];

        $log = $this->logService->create($data);
        
        $csv = $this->logService->export(collect([$log]), 'csv');
        
        $this->assertStringContainsString($data['user_id'], $csv);
        $this->assertStringContainsString($data['system'], $csv);
    }

    public function test_search_results_are_cached()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));

        $this->logService->search(['system' => 'auth']);
    }

    public function test_invalid_export_format_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->logService->export(collect([]), 'invalid');
    }
} 