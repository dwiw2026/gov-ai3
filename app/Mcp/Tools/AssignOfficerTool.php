<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Assign a specific officer to handle and verify a complaint on the field.')]
class AssignOfficerTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->assignOfficer(
            $request->string('ticket_number'),
            $request->integer('officer_id'),
        );
        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_number' => $schema->string()->description('Complaint ticket number'),
            'officer_id'    => $schema->integer()->description('Officer ID to assign'),
        ];
    }
}
