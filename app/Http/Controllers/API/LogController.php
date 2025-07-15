<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    /**
     * @var LogService
     */
    private LogService $logService;

    /**
     * Constructor
     *
     * @param LogService $logService
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Registra um novo log
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'system' => 'required|string',
            'action_type' => 'required|string',
            'resource' => 'required|string',
            'result' => 'required|string',
            'ip' => 'required|ip',
            'payload' => 'sometimes|array',
            'is_sensitive' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $log = $this->logService->create($request->all());

            return response()->json([
                'message' => 'Log registrado com sucesso',
                'data' => $log
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar log',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Busca logs com filtros
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|string',
            'system' => 'sometimes|string',
            'action_type' => 'sometimes|string',
            'date_start' => 'sometimes|date',
            'date_end' => 'sometimes|date|after_or_equal:date_start',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Filtros inválidos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $logs = $this->logService->search(
                $request->only(['user_id', 'system', 'action_type', 'date_start', 'date_end']),
                $request->input('per_page', 15)
            );

            return response()->json([
                'message' => 'Logs recuperados com sucesso',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar logs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verifica integridade do log
     *
     * @param string $id
     * @return JsonResponse
     */
    public function verifyIntegrity(string $id): JsonResponse
    {
        try {
            $log = Log::findOrFail($id);
            $isValid = $this->logService->verifyIntegrity($log);

            return response()->json([
                'message' => $isValid ? 'Log íntegro' : 'Log foi alterado',
                'is_valid' => $isValid
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao verificar integridade',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exporta logs
     *
     * @param Request $request
     * @return JsonResponse|StreamedResponse
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json',
            'user_id' => 'sometimes|string',
            'system' => 'sometimes|string',
            'action_type' => 'sometimes|string',
            'date_start' => 'sometimes|date',
            'date_end' => 'sometimes|date|after_or_equal:date_start'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parâmetros inválidos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $paginator = $this->logService->search(
                $request->only(['user_id', 'system', 'action_type', 'date_start', 'date_end']),
                1000 // Limite máximo para exportação
            );

            $logs = collect($paginator->items());
            $content = $this->logService->export($logs, $request->input('format'));
            $filename = 'logs_' . now()->format('Y-m-d_His') . '.' . $request->input('format');

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao exportar logs',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 