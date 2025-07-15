<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    Route::prefix('v1')->group(function () {
        // Rotas de Logs
        Route::prefix('logs')->group(function () {
            Route::get('/', [LogController::class, 'index']);
            Route::post('/', [LogController::class, 'store']);
            Route::get('/export', [LogController::class, 'export']);
            Route::get('/{id}/verify', [LogController::class, 'verifyIntegrity']);
        });
    });
}); 