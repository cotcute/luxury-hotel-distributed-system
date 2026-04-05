<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

Route::withoutMiddleware('throttle:api')->group(function () {
    Route::post('/client-book', [NodeController::class, 'receiveFromClient']);
    Route::post('/can-commit',  [NodeController::class, 'canCommit']);
    Route::post('/pre-commit',  [NodeController::class, 'preCommit']);
    Route::post('/do-commit',   [NodeController::class, 'doCommit']);
    Route::post('/abort',       [NodeController::class, 'abort']);
    Route::post('/sync',        [NodeController::class, 'forceSync']);
    Route::get('/health',       [NodeController::class, 'health']);
});