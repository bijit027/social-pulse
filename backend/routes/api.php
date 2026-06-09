<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Public widget endpoints
Route::get('/widget/{pixelId}', [WidgetController::class, 'serve']);
Route::post('/widget/{pixelId}/display', [WidgetController::class, 'trackDisplay']);

// Auth endpoints
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Websites
    Route::get('/websites', [WebsiteController::class, 'index']);
    Route::post('/websites', [WebsiteController::class, 'store'])->middleware('throttle:10,1');
    Route::put('/websites/{website}', [WebsiteController::class, 'update']);
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy']);
    Route::get('/websites/{website}/snippet', [WebsiteController::class, 'snippet']);

    // Notifications
    Route::get('/websites/{website}/notifications', [NotificationController::class, 'index']);
    Route::post('/websites/{website}/notifications', [NotificationController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/notifications/{notification}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::patch('/notifications/{notification}/toggle', [NotificationController::class, 'toggle']);
});
