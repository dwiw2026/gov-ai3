<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\NotificationLog;
use App\Models\Officer;
use App\Models\RoutingRule;
use Illuminate\Support\Facades\Log;

/**
 * ComplaintService — orchestrates the full complaint lifecycle.
 *
 * This is the Laravel "Government Core" layer:
 * IBM Bob calls tools → tools call this service → data persisted to MariaDB.
 */
class ComplaintService
{
    public function __construct(
        protected LangflowService  $langflow,
        protected TelegramService  $telegram,
    ) {}

    /**
     * Tool 1: analyze_complaint
     * Sends complaint to Langflow and stores AI analysis result.
     */
    public function analyzeComplaint(
        string  $message,
        ?string $locationText = null,
        ?string $imageBase64 = null,
        string  $channel = 'bot',
        ?string $citizenIdentifier = null,
        ?string $citizenName = null,
    ): array {
        $analysis = $this->langflow->analyzeComplaint($message, $locationText, $imageBase64, $channel);

        // Return analysis without creating ticket yet
        return array_merge($analysis, [
            'raw_message' => $message,
            'channel'     => $channel,
        ]);
    }

    /**
     * Tool 2: get_location
     * Resolves location text to coordinates (stub — real impl calls Google Maps).
     */
    public function getLocation(string $locationText): array
    {
        // In production: call Google Maps Geocoding API
        // For demo: return enriched location info
        $geocoded = $this->geocodeLocation($locationText);

        return [
            'location_text' => $locationText,
            'latitude'      => $geocoded['lat'] ?? null,
            'longitude'     => $geocoded['lng'] ?? null,
            'formatted'     => $geocoded['formatted'] ?? $locationText,
            'district'      => $geocoded['district'] ?? null,
            'city'          => $geocoded['city'] ?? 'Kota Banjarmasin',
        ];
    }

    /**
     * Tool 3: check_routing
     * Determines which agency should handle a complaint by category.
     */
    public function checkRouting(string $category, ?string $subcategory = null, string $priority = 'medium'): array
    {
        // First: try database routing rules
        $rule = RoutingRule::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->when($subcategory, fn ($q) => $q->where(
                fn ($q2) => $q2->where('subcategory', $subcategory)->orWhereNull('subcategory')
            ))
            ->orderBy('sort_order')
            ->with('agency')
            ->first();

        if ($rule?->agency) {
            $sla = $rule->sla_minutes ?? $rule->agency->sla_minutes;
            return [
                'agency_code' => $rule->agency->code,
                'agency_name' => $rule->agency->name,
                'agency_id'   => $rule->agency->id,
                'sla_minutes' => $sla,
                'priority'    => $rule->priority_override ?? $priority,
                'rule_match'  => 'database',
            ];
        }

        // Fallback: hardcoded routing map
        $map = [
            'kebakaran'    => 'DAMKAR',
            'sampah'       => 'DLH',
            'banjir'       => 'DPUPR',
            'lalu_lintas'  => 'DISHUB',
            'ketertiban'   => 'SATPOL_PP',
            'infrastruktur'=> 'DPUPR',
            'taman'        => 'DLH',
        ];

        $agencyCode = $map[$category] ?? 'UMUM';
        $agency     = Agency::where('code', $agencyCode)->first();

        return [
            'agency_code' => $agencyCode,
            'agency_name' => $agency?->name ?? $agencyCode,
            'agency_id'   => $agency?->id,
            'sla_minutes' => $agency?->sla_minutes ?? 60,
            'priority'    => $priority,
            'rule_match'  => 'fallback',
        ];
    }

    /**
     * Tool 4: create_complaint
     * Creates the official complaint ticket in the database.
     */
    public function createComplaint(array $data): Complaint
    {
        $ticketNumber = Complaint::generateTicketNumber();
        $routing      = $this->checkRouting(
            $data['category'] ?? 'umum',
            $data['subcategory'] ?? null,
            $data['priority'] ?? 'medium',
        );

        $complaint = Complaint::create([
            'ticket_number'        => $ticketNumber,
            'agency_id'            => $routing['agency_id'],
            'channel'              => $data['channel'] ?? 'bot',
            'citizen_identifier'   => $data['citizen_identifier'] ?? null,
            'citizen_name'         => $data['citizen_name'] ?? null,
            'raw_message'          => $data['raw_message'],
            'image_path'           => $data['image_path'] ?? null,
            'location_text'        => $data['location_text'] ?? null,
            'latitude'             => $data['latitude'] ?? null,
            'longitude'            => $data['longitude'] ?? null,
            'category'             => $data['category'] ?? 'umum',
            'subcategory'          => $data['subcategory'] ?? null,
            'priority'             => $data['priority'] ?? 'medium',
            'ai_confidence'        => $data['confidence'] ?? null,
            'ai_reason'            => $data['reason'] ?? null,
            'ai_recommended_action'=> $data['recommended_action'] ?? null,
            'ai_raw_output'        => $data['raw_output'] ?? null,
            'requires_human_review'=> $data['requires_human_review'] ?? false,
            'status'               => ($data['requires_human_review'] ?? false) ? 'awaiting_review' : 'routed',
            'sla_deadline'         => now()->addMinutes($routing['sla_minutes']),
        ]);

        AuditLog::create([
            'complaint_id'  => $complaint->id,
            'event'         => 'created',
            'actor_type'    => 'system',
            'actor_id'      => $data['channel'] ?? 'bot',
            'description'   => "Laporan dibuat melalui kanal {$complaint->channel}",
            'ai_confidence' => $data['confidence'] ?? null,
            'ai_input'      => ['message' => $data['raw_message']],
            'ai_output'     => $data['raw_output'] ?? null,
            'created_at'    => now(),
        ]);

        AuditLog::create([
            'complaint_id' => $complaint->id,
            'event'        => 'routed',
            'actor_type'   => 'system',
            'actor_id'     => 'routing_engine',
            'description'  => "Diteruskan ke {$routing['agency_name']} berdasarkan kategori {$complaint->category}",
            'created_at'   => now(),
        ]);

        return $complaint->load('agency');
    }

    /**
     * Tool 5: get_complaint_status
     */
    public function getComplaintStatus(string $ticketNumber): ?array
    {
        $complaint = Complaint::where('ticket_number', $ticketNumber)
            ->with(['agency', 'officer', 'auditLogs'])
            ->first();

        if (!$complaint) {
            return null;
        }

        return [
            'ticket_number'  => $complaint->ticket_number,
            'status'         => $complaint->status,
            'status_label'   => $complaint->status_label,
            'category'       => $complaint->category,
            'priority'       => $complaint->priority,
            'agency'         => $complaint->agency?->name,
            'officer'        => $complaint->officer?->name,
            'sla_breached'   => $complaint->isSlaBreached(),
            'sla_remaining'  => $complaint->sla_remaining_minutes,
            'created_at'     => $complaint->created_at->toIso8601String(),
            'updated_at'     => $complaint->updated_at->toIso8601String(),
            'timeline'       => $complaint->auditLogs->map(fn ($l) => [
                'event'       => $l->event,
                'description' => $l->description,
                'at'          => $l->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Tool 6: find_nearby_officer
     */
    public function findNearbyOfficer(int $agencyId, ?float $lat = null, ?float $lng = null): ?array
    {
        $officer = Officer::where('agency_id', $agencyId)
            ->where('status', 'available')
            ->first();

        if (!$officer) {
            return null;
        }

        return [
            'officer_id'   => $officer->id,
            'name'         => $officer->name,
            'phone'        => $officer->phone,
            'status'       => $officer->status,
            'location'     => $officer->current_location,
        ];
    }

    /**
     * Tool 7: assign_officer
     */
    public function assignOfficer(string $ticketNumber, int $officerId): array
    {
        $complaint = Complaint::where('ticket_number', $ticketNumber)->firstOrFail();
        $officer   = Officer::findOrFail($officerId);

        $complaint->update([
            'officer_id' => $officerId,
            'status'     => 'assigned',
        ]);

        $officer->update(['status' => 'on_duty']);

        AuditLog::create([
            'complaint_id' => $complaint->id,
            'event'        => 'assigned',
            'actor_type'   => 'system',
            'actor_id'     => 'assignment_engine',
            'description'  => "Ditugaskan kepada petugas {$officer->name} ({$officer->employee_id})",
            'created_at'   => now(),
        ]);

        return [
            'ticket_number' => $ticketNumber,
            'officer_name'  => $officer->name,
            'officer_phone' => $officer->phone,
            'status'        => 'assigned',
        ];
    }

    /**
     * Tool 8: send_notification
     * Sends Telegram notification to citizen about complaint status.
     */
    public function sendNotification(string $ticketNumber, string $type = 'status_update'): array
    {
        $complaint = Complaint::where('ticket_number', $ticketNumber)
            ->with('agency')
            ->first();

        if (!$complaint) {
            return ['success' => false, 'error' => 'Complaint not found'];
        }

        $message = $this->buildNotificationMessage($complaint, $type);

        // Send via Telegram if citizen came from that channel
        $sent = false;
        if ($complaint->channel === 'telegram' && $complaint->citizen_identifier) {
            $sent = $this->telegram->sendMessage(
                $complaint->citizen_identifier,
                $message
            );
        }

        NotificationLog::create([
            'complaint_id' => $complaint->id,
            'channel'      => $complaint->channel,
            'recipient'    => $complaint->citizen_identifier ?? 'unknown',
            'type'         => $type,
            'message'      => $message,
            'status'       => $sent ? 'sent' : 'pending',
            'sent_at'      => $sent ? now() : null,
        ]);

        return ['success' => $sent, 'message' => $message, 'channel' => $complaint->channel];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function buildNotificationMessage(Complaint $complaint, string $type): string
    {
        $ticket   = $complaint->ticket_number;
        $category = ucfirst($complaint->category ?? 'Umum');
        $agency   = $complaint->agency?->name ?? 'OPD terkait';
        $status   = $complaint->status_label;

        return match ($type) {
            'status_update' => "✅ *CivicAI Update Laporan*\n\n"
                . "Nomor: `{$ticket}`\n"
                . "Kategori: {$category}\n"
                . "Status: *{$status}*\n"
                . "Ditangani: {$agency}\n\n"
                . "Terima kasih atas laporan Anda.",

            'assignment' => "👮 *Petugas Ditugaskan*\n\n"
                . "Nomor: `{$ticket}`\n"
                . "Petugas dari {$agency} telah ditugaskan untuk menangani laporan Anda.",

            'resolved' => "✅ *Laporan Selesai*\n\n"
                . "Nomor: `{$ticket}`\n"
                . "Laporan Anda telah selesai ditangani oleh {$agency}.\n"
                . "Terima kasih telah membantu membuat kota lebih baik! 🙏",

            default => "📋 Update laporan `{$ticket}`: {$status}",
        };
    }

    protected function geocodeLocation(string $text): array
    {
        // Production: integrate Google Maps API
        // Demo: regex-based enrichment
        return [
            'formatted' => $text,
            'lat'       => null,
            'lng'       => null,
            'district'  => null,
            'city'      => 'Kota Banjarmasin',
        ];
    }
}
