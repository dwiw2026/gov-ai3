<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — CivicAI Government Backend
|--------------------------------------------------------------------------
|
| These routes are called by:
|   - IBM Bob (via MCP tools which wrap ComplaintService directly)
|   - Dashboard frontend (JavaScript / Blade views)
|   - Telegram webhook (citizen intake)
|   - External integrations
|
*/

// ─── Complaint API ────────────────────────────────────────────────────────────
Route::prefix('complaints')->group(function () {
    Route::get('/',             [ComplaintController::class, 'index']);
    Route::post('/analyze',     [ComplaintController::class, 'analyze']);
    Route::post('/submit',      [ComplaintController::class, 'submit']);     // full flow: analyze + create
    Route::post('/',            [ComplaintController::class, 'store']);
    Route::get('/{ticket}',     [ComplaintController::class, 'show']);
    Route::get('/{ticket}/status', [ComplaintController::class, 'status']);
    Route::patch('/{ticket}/status', [ComplaintController::class, 'updateStatus']);
});

// ─── Analytics API ────────────────────────────────────────────────────────────
Route::prefix('analytics')->group(function () {
    Route::get('/overview',      [AnalyticsController::class, 'overview']);
    Route::get('/by-category',   [AnalyticsController::class, 'byCategory']);
    Route::get('/by-agency',     [AnalyticsController::class, 'byAgency']);
    Route::get('/timeline',      [AnalyticsController::class, 'timeline']);
    Route::get('/ai-performance',[AnalyticsController::class, 'aiPerformance']);
});

// ─── Telegram Webhook ─────────────────────────────────────────────────────────
// POST /api/webhook/telegram/{secret}
// Register this URL with Telegram: php artisan civicai:set-webhook
Route::post('/webhook/telegram/{secret}', [TelegramController::class, 'webhook'])
    ->name('webhook.telegram');
