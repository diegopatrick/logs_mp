<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class LogService
{
    /**
     * Cria um novo registro de log
     *
     * @param array $data
     * @return Log
     */
    public function create(array $data): Log
    {
        // Adiciona timestamp se não fornecido
        $data['timestamp'] = $data['timestamp'] ?? now();
        
        // Gera hash do log para garantir imutabilidade
        $data['hash'] = $this->generateHash($data);

        return Log::create($data);
    }

    /**
     * Busca logs com filtros e paginação
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'logs:' . md5(json_encode($filters) . $perPage);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters, $perPage) {
            $query = Log::query();

            // Aplica filtros
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (!empty($filters['system'])) {
                $query->where('system', $filters['system']);
            }

            if (!empty($filters['action_type'])) {
                $query->where('action_type', $filters['action_type']);
            }

            if (!empty($filters['date_start'])) {
                $query->where('timestamp', '>=', $filters['date_start']);
            }

            if (!empty($filters['date_end'])) {
                $query->where('timestamp', '<=', $filters['date_end']);
            }

            // Ordena por timestamp decrescente
            $query->orderBy('timestamp', 'desc');

            return $query->paginate($perPage);
        });
    }

    /**
     * Verifica a integridade do log
     *
     * @param Log $log
     * @return bool
     */
    public function verifyIntegrity(Log $log): bool
    {
        $originalData = $log->toArray();
        unset($originalData['hash']); // Remove o hash para recalcular

        $calculatedHash = $this->generateHash($originalData);

        return hash_equals($log->hash, $calculatedHash);
    }

    /**
     * Gera hash dos dados do log
     *
     * @param array $data
     * @return string
     */
    private function generateHash(array $data): string
    {
        // Remove campos que não devem fazer parte do hash
        unset($data['hash']);
        
        // Ordena os dados para garantir consistência
        ksort($data);

        // Gera hash SHA-256
        return hash('sha256', json_encode($data));
    }

    /**
     * Exporta logs para formato específico
     *
     * @param Collection $logs
     * @param string $format
     * @return string
     */
    public function export(Collection $logs, string $format = 'csv'): string
    {
        return match ($format) {
            'csv' => $this->exportToCsv($logs),
            'json' => $this->exportToJson($logs),
            default => throw new \InvalidArgumentException('Formato não suportado'),
        };
    }

    /**
     * Exporta logs para CSV
     *
     * @param Collection $logs
     * @return string
     */
    private function exportToCsv(Collection $logs): string
    {
        $headers = ['Data/Hora', 'Usuário', 'Sistema', 'Ação', 'Recurso', 'Resultado', 'IP'];
        $output = fopen('php://temp', 'r+');

        // Escreve cabeçalho UTF-8
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);

        // Escreve dados
        foreach ($logs as $log) {
            fputcsv($output, [
                $log->timestamp->format('Y-m-d H:i:s'),
                $log->user_id,
                $log->system,
                $log->action_type,
                $log->resource,
                $log->result,
                $log->ip
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Exporta logs para JSON
     *
     * @param Collection $logs
     * @return string
     */
    private function exportToJson(Collection $logs): string
    {
        return $logs->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} 