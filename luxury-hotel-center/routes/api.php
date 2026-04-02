<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Các route này sẽ được load bởi RouteServiceProvider và đều có prefix /api
|
*/

// Route mặc định (Laravel)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// ---------------------------------------------------------
// 🚀 4 PHASE COMMIT - SYNC DATA GIỮA CÁC NODE
Route::post('/sync', [App\Http\Controllers\NodeController::class, 'forceSync']);