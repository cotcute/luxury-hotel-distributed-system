<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

// Giao thức 4PC — nhận lệnh từ Server Tổng
Route::post('/can-commit', [NodeController::class, 'canCommit']);
Route::post('/pre-commit', [NodeController::class, 'preCommit']);
Route::post('/do-commit',  [NodeController::class, 'doCommit']);
Route::post('/abort',      [NodeController::class, 'abort']);

// Health-check — Server Tổng ping vào đây để kiểm tra node còn sống không
Route::get('/health',      [NodeController::class, 'health']);
Route::post('/sync', [App\Http\Controllers\NodeController::class, 'forceSync']);
Route::post('/client-book', [App\Http\Controllers\NodeController::class, 'receiveFromClient']);