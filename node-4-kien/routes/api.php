<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

Route::post('/can-commit', [NodeController::class, 'canCommit']);
Route::post('/pre-commit', [NodeController::class, 'preCommit']);
Route::post('/do-commit',  [NodeController::class, 'doCommit']);
Route::post('/abort',      [NodeController::class, 'abort']);