<?php

namespace App\Jobs;

use App\Services\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLogEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Dados do log para processamento
     *
     * @var array
     */
    private array $logData;

    /**
     * Create a new job instance.
     *
     * @param array $logData
     */
    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    /**
     * Execute the job.
     *
     * @param LogService $logService
     * @return void
     */
    public function handle(LogService $logService): void
    {
        try {
            $logService->create($this->logData);
        } catch (\Exception $e) {
            // Log o erro e falha o job para retry
            \Log::error('Erro ao processar log: ' . $e->getMessage(), [
                'data' => $this->logData,
                'exception' => $e
            ]);
            
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Job de log falhou: ' . $exception->getMessage(), [
            'data' => $this->logData,
            'exception' => $exception
        ]);
    }
} 