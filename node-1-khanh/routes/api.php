<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NodeController;

// ============================================================
// TẤT CẢ ROUTES API NỘI BỘ — BỎ THROTTLE (server-to-server)
// 429 Too Many Requests fix: withoutMiddleware('throttle:api')
// ============================================================
Route::withoutMiddleware('throttle:api')->group(function () {

    // NHẬN LỆNH TỪ CENTER SERVER (Node làm nhạc trưởng)
    Route::post('/client-book', [NodeController::class, 'receiveFromClient']);

    // PHA 1: Kiểm tra phòng có bị lock không
    Route::post('/can-commit',  [NodeController::class, 'canCommit']);

    // PHA 2: Ghi tạm (pending lock)
    Route::post('/pre-commit',  [NodeController::class, 'preCommit']);

    // PHA 3: Chốt giao dịch chính thức
    Route::post('/do-commit',   [NodeController::class, 'doCommit']);

    // PHA 4: Hủy bỏ (rollback)
    Route::post('/abort',       [NodeController::class, 'abort']);

    // ĐỒNG BỘ BÙ (sau khi node wake-up)
    Route::post('/sync',        [NodeController::class, 'forceSync']);

    // HEALTH CHECK (kiểm tra node có online không)
    Route::get('/health',       [NodeController::class, 'health']);
});