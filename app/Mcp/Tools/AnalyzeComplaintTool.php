<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Analyze a citizen complaint using the Langflow AI Intelligence Workflow. ' .
    'Returns category, subcategory, priority, responsible agency (OPD), AI confidence, ' .
    'and reasoning. Call this FIRST when a new complaint message is received.'
)]
class AnalyzeComplaintTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $message      = $request->string('message');
        $locationText = $request->string('location_text', '');
        $channel      = $request->string('channel', 'bot');

        $result = $this->svc->analyzeComplaint(
            message:      $message,
            locationText: $locationText ?: null,
            channel:      $channel ?: 'bot',
        );

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message'       => $schema->string()->description('The complaint text from the citizen'),
            'location_text' => $schema->string()->description('Location description if provided')->nullable(),
            'channel'       => $schema->string()->description('Channel: bot, telegram, web')->nullable(),
        ];
    }
}
