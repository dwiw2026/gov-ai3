<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature   = 'civicai:set-webhook {--remove : Remove the webhook instead of setting it}';
    protected $description = 'Register or remove the Telegram Bot webhook URL';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('remove')) {
            $result = $telegram->setWebhook('');
            $this->info('Webhook removed: ' . json_encode($result));
            return 0;
        }

        $appUrl = config('app.url');
        $secret = config('civicai.telegram.webhook_secret') ?: config('civicai.mcp.secret');
        $url    = "{$appUrl}/api/webhook/telegram/{$secret}";

        $result = $telegram->setWebhook($url, $secret);

        if (($result['ok'] ?? false)) {
            $this->info("✅ Webhook set to: {$url}");
            $this->line('Response: ' . json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->error('Failed to set webhook: ' . json_encode($result));
            return 1;
        }

        return 0;
    }
}
