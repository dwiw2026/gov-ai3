<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Create an official complaint ticket in the government Laravel system. ' .
    'Call this AFTER analyze_complaint and check_routing. ' .
    'Returns ticket number, status, routing, and SLA deadline.'
)]
class CreateComplaintTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $complaint = $this->svc->createComplaint($request->all());

        $result = [
            'ticket_number'   => $complaint->ticket_number,
            'status'          => $complaint->status,
            'category'        => $complaint->category,
            'priority'        => $complaint->priority,
            'agency'          => $complaint->agency?->name,
            'sla_deadline'    => $complaint->sla_deadline?->toIso8601String(),
            'requires_review' => $complaint->requires_human_review,
            'confidence'      => $complaint->ai_confidence,
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'raw_message'          => $schema->string()->description('Original complaint text'),
            'category'             => $schema->string()->description('Complaint category from analysis'),
            'subcategory'          => $schema->string()->nullable(),
            'priority'             => $schema->string()->nullable(),
            'location_text'        => $schema->string()->nullable(),
            'confidence'           => $schema->number()->nullable(),
            'reason'               => $schema->string()->nullable(),
            'recommended_action'   => $schema->string()->nullable(),
            'requires_human_review'=> $schema->boolean()->nullable(),
            'citizen_name'         => $schema->string()->nullable(),
            'channel'              => $schema->string()->nullable(),
        ];
    }
}
