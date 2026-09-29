<?php

namespace App\Providers;

use App\Mcp\Servers\CivicAiServer;
use App\Services\ComplaintService;
use App\Services\LangflowService;
use App\Services\TelegramService;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services as singletons
        $this->app->singleton(LangflowService::class);

        $this->app->singleton(TelegramService::class);

        $this->app->singleton(ComplaintService::class, function ($app) {
            return new ComplaintService(
                langflow: $app->make(LangflowService::class),
                telegram: $app->make(TelegramService::class),
            );
        });
    }

    public function boot(): void
    {
        // Register CivicAI MCP Server (IBM Bob connects via stdio transport)
        // IBM Bob uses handle "civicai" → php artisan mcp:start civicai
        Mcp::local('civicai', CivicAiServer::class);

        // Also expose as HTTP endpoint for web-based MCP clients
        // Mcp::web('/mcp/civicai', CivicAiServer::class);
    }
}
