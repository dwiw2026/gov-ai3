<?php

return [
    // ─── Langflow AI Workflow Engine ─────────────────────────────────────────
    'langflow' => [
        'base_url'   => env('LANGFLOW_BASE_URL', 'http://localhost:7860'),
        'api_key'    => env('LANGFLOW_API_KEY'),
        'flow_id'    => env('LANGFLOW_FLOW_ID', ''),
        'auth_token' => env('LANGFLOW_AUTH_TOKEN'),
    ],

    // ─── Telegram Bot ─────────────────────────────────────────────────────────
    'telegram' => [
        'token'          => env('TELEGRAM_BOT_TOKEN', ''),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    ],

    // ─── MCP Server (IBM Bob integration) ─────────────────────────────────────
    'mcp' => [
        'secret' => env('CIVICAI_MCP_SECRET', 'civicai-secret'),
    ],

    // ─── Responsible AI settings ──────────────────────────────────────────────
    'ai' => [
        'confidence_threshold'       => (float) env('AI_CONFIDENCE_THRESHOLD', 0.70),
        'require_human_review_below' => (float) env('AI_REQUIRE_HUMAN_REVIEW_BELOW', 0.70),
    ],

    // ─── Hermes Automation (optional) ─────────────────────────────────────────
    'hermes' => [
        'enabled'     => (bool) env('HERMES_ENABLED', false),
        'webhook_url' => env('HERMES_WEBHOOK_URL', ''),
    ],

    // ─── Google Maps (optional) ───────────────────────────────────────────────
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],
];
