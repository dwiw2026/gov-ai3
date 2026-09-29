<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * TelegramController — handles incoming Telegram webhook updates.
 *
 * Telegram Bot webhook endpoint:
 * POST /webhook/telegram/{secret}
 *
 * Register with:
 * php artisan civicai:set-webhook
 */
class TelegramController extends Controller
{
    public function __construct(protected TelegramService $telegram) {}

    public function webhook(Request $request, string $secret): Response
    {
        // Validate webhook secret
        $expected = config('civicai.telegram.webhook_secret') ?: config('civicai.mcp.secret');
        if ($secret !== $expected) {
            Log::warning('Telegram webhook: invalid secret', ['provided' => $secret]);
            return response('Unauthorized', 401);
        }

        $update = $request->json()->all();

        if (empty($update)) {
            return response('OK', 200);
        }

        Log::info('Telegram update received', ['update_id' => $update['update_id'] ?? null]);

        try {
            $this->telegram->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook handler error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response('OK', 200);
    }
}
