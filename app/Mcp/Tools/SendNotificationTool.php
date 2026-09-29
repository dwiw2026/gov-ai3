<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Send a status notification to the citizen who submitted the complaint, ' .
    'via their original channel (Telegram). ' .
    'Types: status_update, assignment, resolved.'
)]
class SendNotificationTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->sendNotification(
            $request->string('ticket_number'),
            $request->string('type', 'status_update') ?: 'status_update',
        );
        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_number' => $schema->string()->description('Complaint ticket number'),
            'type'          => $schema->string()->description('Notification type: status_update, assignment, resolved')->nullable(),
        ];
    }
}
