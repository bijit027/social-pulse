<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

// Public widget endpoints
Route::get('/widget/{pixelId}', [WidgetController::class, 'serve']);
Route::post('/widget/{pixelId}/display', [WidgetController::class, 'trackDisplay']);

// Public analytics endpoint (for widget click tracking)
Route::post('/analytics/track', [AnalyticsController::class, 'track']);

// Public visitor tracking endpoint
Route::post('/visitor/{pixelId}/ping', [VisitorController::class, 'ping']);

// Public webhook endpoints
Route::post('/webhook/woocommerce/{pixelId}', [WebhookController::class, 'woocommerce']);
Route::post('/webhook/stripe/{pixelId}', [WebhookController::class, 'stripe']);

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
    Route::get('/websites/{website}', [WebsiteController::class, 'show']);
    Route::put('/websites/{website}', [WebsiteController::class, 'update']);
    Route::patch('/websites/{website}', [WebsiteController::class, 'update']);
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy']);
    Route::get('/websites/{website}/snippet', [WebsiteController::class, 'snippet']);
    Route::get('/websites/{website}/analytics', [WebsiteController::class, 'analytics']);
    Route::get('/websites/{website}/analytics/stats', [AnalyticsController::class, 'getStats']);

    // Notifications
    Route::get('/websites/{website}/notifications', [NotificationController::class, 'index']);
    Route::post('/websites/{website}/notifications', [NotificationController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/notifications/{notification}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::patch('/notifications/{notification}/toggle', [NotificationController::class, 'toggle']);
});
