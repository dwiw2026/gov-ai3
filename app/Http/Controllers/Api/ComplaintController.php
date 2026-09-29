<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ComplaintController — RESTful API for complaint management.
 *
 * Used by:
 * - IBM Bob (via MCP tools, which call ComplaintService directly)
 * - Dashboard frontend
 * - External integrations
 */
class ComplaintController extends Controller
{
    public function __construct(protected ComplaintService $svc) {}

    /**
     * List complaints with filters.
     * GET /api/complaints
     */
    public function index(Request $request): JsonResponse
    {
        $query = Complaint::with(['agency', 'officer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', "%{$request->search}%")
                  ->orWhere('raw_message', 'like', "%{$request->search}%")
                  ->orWhere('location_text', 'like', "%{$request->search}%");
            });
        }

        $complaints = $query->paginate($request->get('per_page', 20));

        return response()->json($complaints);
    }

    /**
     * Show a single complaint.
     * GET /api/complaints/{ticket}
     */
    public function show(string $ticket): JsonResponse
    {
        $complaint = Complaint::where('ticket_number', $ticket)
            ->with(['agency', 'officer', 'auditLogs', 'notifications'])
            ->firstOrFail();

        return response()->json($complaint);
    }

    /**
     * Analyze a complaint via Langflow (without creating a ticket).
     * POST /api/complaints/analyze
     *
     * Body: { message, location_text?, channel? }
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'message'       => 'required|string|max:5000',
            'location_text' => 'nullable|string|max:500',
            'channel'       => 'nullable|string|in:bot,telegram,web',
        ]);

        $analysis = $this->svc->analyzeComplaint(
            message:      $request->message,
            locationText: $request->location_text,
            channel:      $request->get('channel', 'bot'),
        );

        return response()->json($analysis);
    }

    /**
     * Create a complaint ticket.
     * POST /api/complaints
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'raw_message' => 'required|string|max:5000',
            'category'    => 'required|string',
            'channel'     => 'nullable|string',
        ]);

        $complaint = $this->svc->createComplaint($request->all());

        return response()->json([
            'ticket_number'  => $complaint->ticket_number,
            'status'         => $complaint->status,
            'category'       => $complaint->category,
            'priority'       => $complaint->priority,
            'agency'         => $complaint->agency?->name,
            'sla_deadline'   => $complaint->sla_deadline?->toIso8601String(),
            'requires_review'=> $complaint->requires_human_review,
        ], 201);
    }

    /**
     * Full flow: analyze → create (single API call convenience endpoint).
     * POST /api/complaints/submit
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'message'          => 'required|string|max:5000',
            'location_text'    => 'nullable|string|max:500',
            'channel'          => 'nullable|string',
            'citizen_name'     => 'nullable|string|max:200',
            'citizen_identifier' => 'nullable|string|max:200',
        ]);

        $analysis  = $this->svc->analyzeComplaint(
            message:             $request->message,
            locationText:        $request->location_text,
            channel:             $request->get('channel', 'web'),
            citizenIdentifier:   $request->citizen_identifier,
            citizenName:         $request->citizen_name,
        );

        $complaint = $this->svc->createComplaint(array_merge($analysis, [
            'raw_message'        => $request->message,
            'location_text'      => $request->location_text,
            'channel'            => $request->get('channel', 'web'),
            'citizen_name'       => $request->citizen_name,
            'citizen_identifier' => $request->citizen_identifier,
        ]));

        return response()->json([
            'ticket_number'    => $complaint->ticket_number,
            'status'           => $complaint->status,
            'category'         => $complaint->category,
            'subcategory'      => $complaint->subcategory,
            'priority'         => $complaint->priority,
            'agency'           => $complaint->agency?->name,
            'ai_confidence'    => $complaint->ai_confidence,
            'ai_reason'        => $complaint->ai_reason,
            'requires_review'  => $complaint->requires_human_review,
            'sla_deadline'     => $complaint->sla_deadline?->toIso8601String(),
        ], 201);
    }

    /**
     * Update complaint status (operator action).
     * PATCH /api/complaints/{ticket}/status
     */
    public function updateStatus(Request $request, string $ticket): JsonResponse
    {
        $request->validate([
            'status'             => 'required|in:routed,assigned,in_progress,resolved,rejected',
            'correction_notes'   => 'nullable|string',
            'corrected_category' => 'nullable|string',
        ]);

        $complaint = Complaint::where('ticket_number', $ticket)->firstOrFail();
        $oldStatus = $complaint->status;

        $complaint->update(array_filter([
            'status'             => $request->status,
            'correction_notes'   => $request->correction_notes,
            'corrected_category' => $request->corrected_category,
            'corrected_by'       => $request->user()?->id,
            'resolved_at'        => $request->status === 'resolved' ? now() : null,
        ]));

        \App\Models\AuditLog::create([
            'complaint_id'  => $complaint->id,
            'event'         => 'status_changed',
            'actor_type'    => 'operator',
            'actor_id'      => $request->user()?->id ?? 'api',
            'description'   => "Status berubah dari {$oldStatus} menjadi {$request->status}",
            'previous_value'=> $oldStatus,
            'new_value'     => $request->status,
            'created_at'    => now(),
        ]);

        return response()->json(['success' => true, 'status' => $complaint->status]);
    }

    /**
     * GET /api/complaints/{ticket}/status
     */
    public function status(string $ticket): JsonResponse
    {
        $result = $this->svc->getComplaintStatus($ticket);
        if (!$result) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json($result);
    }
}
