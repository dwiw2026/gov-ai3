<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Determine which government agency (OPD) should handle a complaint based on its category. ' .
    'Returns agency name, agency ID, SLA minutes, priority, and routing rule match source. ' .
    'Call this after analyze_complaint to confirm routing.'
)]
class CheckRoutingTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->checkRouting(
            $request->string('category'),
            $request->string('subcategory', '') ?: null,
            $request->string('priority', 'medium') ?: 'medium',
        );
        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category'    => $schema->string()->description('Complaint category (e.g. kebakaran, sampah, ketertiban, lalu_lintas)'),
            'subcategory' => $schema->string()->description('Optional subcategory')->nullable(),
            'priority'    => $schema->string()->description('Priority: low, medium, high, critical')->nullable(),
        ];
    }
}
