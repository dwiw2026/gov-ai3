<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Find an available officer from a specific government agency for on-field verification.')]
class FindNearbyOfficerTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->findNearbyOfficer(
            $request->integer('agency_id'),
            $request->has('latitude')  ? (float) $request->string('latitude')  : null,
            $request->has('longitude') ? (float) $request->string('longitude') : null,
        );
        return Response::text(json_encode(
            $result ?? ['error' => 'No available officer found'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agency_id' => $schema->integer()->description('Agency ID to search officers in'),
            'latitude'  => $schema->number()->nullable(),
            'longitude' => $schema->number()->nullable(),
        ];
    }
}
