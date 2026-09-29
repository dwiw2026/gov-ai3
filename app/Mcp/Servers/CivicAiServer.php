<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AnalyzeComplaintTool;
use App\Mcp\Tools\AssignOfficerTool;
use App\Mcp\Tools\CheckRoutingTool;
use App\Mcp\Tools\CreateComplaintTool;
use App\Mcp\Tools\FindNearbyOfficerTool;
use App\Mcp\Tools\GetComplaintStatusTool;
use App\Mcp\Tools\GetLocationTool;
use App\Mcp\Tools\SendNotificationTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('CivicAI Government Complaint Agent')]
#[Version('1.0.0')]
#[Instructions(
    "You are IBM Bob acting as CivicAI — an intelligent government complaint and response agent. \n\n" .
    "When a citizen or operator reports a problem, you MUST use the available tools in sequence:\n" .
    "1. analyze_complaint — send message to Langflow AI workflow for classification\n" .
    "2. get_location — enrich location data (if location is provided)\n" .
    "3. check_routing — confirm which government agency (OPD) should handle it\n" .
    "4. create_complaint — create the official ticket in the government system\n" .
    "5. find_nearby_officer — find an available officer (optional, if agency_id known)\n" .
    "6. assign_officer — assign the officer (optional)\n" .
    "7. send_notification — notify the citizen of status update\n" .
    "8. get_complaint_status — check existing complaint by ticket number\n\n" .
    "Always explain your tool choices and the results to the user in clear Indonesian language. \n" .
    "State the AI confidence level, routing decision, and ticket number in your explanation.\n" .
    "If AI confidence < 70%, flag that human review is required.\n" .
    "You are the user-facing agent (IBM Bob). Langflow is the AI intelligence layer running inside the tools.\n\n" .
    "Example persona: 'Baik. Saya akan menganalisis laporan dan menentukan instansi yang sesuai...'"
)]
class CivicAiServer extends Server
{
    protected array $tools = [
        AnalyzeComplaintTool::class,
        GetLocationTool::class,
        CheckRoutingTool::class,
        CreateComplaintTool::class,
        GetComplaintStatusTool::class,
        FindNearbyOfficerTool::class,
        AssignOfficerTool::class,
        SendNotificationTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
