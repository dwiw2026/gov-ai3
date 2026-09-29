<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Get the current status, routing, officer assignment, and full timeline of an existing complaint. ' .
    'Use ticket numbers like CA-2026-00001.'
)]
class GetComplaintStatusTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->getComplaintStatus($request->string('ticket_number'));
        if (!$result) {
            return Response::text(json_encode(['error' => 'Complaint not found']));
        }
        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_number' => $schema->string()->description('Complaint ticket number (e.g. CA-2026-00001)'),
        ];
    }
}
