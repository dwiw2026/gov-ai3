<?php

namespace App\Mcp\Tools;

use App\Services\ComplaintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Resolve and enrich a location string to get geocoordinates, district, and city information. ' .
    'Use this to validate and standardize location data from a complaint.'
)]
class GetLocationTool extends Tool
{
    public function __construct(protected ComplaintService $svc) {}

    public function handle(Request $request): Response
    {
        $result = $this->svc->getLocation($request->string('location_text'));
        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location_text' => $schema->string()->description('Location text to resolve (e.g. Jl. A. Yani, Kecamatan X)'),
        ];
    }
}
