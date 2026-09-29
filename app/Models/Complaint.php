<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_number', 'agency_id', 'officer_id',
        'channel', 'citizen_identifier', 'citizen_name',
        'raw_message', 'image_path', 'location_text', 'latitude', 'longitude',
        'category', 'subcategory', 'priority', 'ai_confidence',
        'ai_reason', 'ai_recommended_action', 'ai_raw_output', 'requires_human_review',
        'status',
        'corrected_category', 'corrected_agency_id', 'correction_notes', 'corrected_by',
        'sla_deadline', 'resolved_at',
    ];

    protected $casts = [
        'ai_raw_output'       => 'array',
        'ai_confidence'       => 'float',
        'requires_human_review' => 'boolean',
        'sla_deadline'        => 'datetime',
        'resolved_at'         => 'datetime',
    ];

    // Priorities ordered by severity
    const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isSlaBreached(): bool
    {
        return $this->sla_deadline
            && $this->sla_deadline->isPast()
            && !in_array($this->status, ['resolved', 'rejected']);
    }

    public function getSlaRemainingMinutesAttribute(): ?int
    {
        if (!$this->sla_deadline) {
            return null;
        }
        return (int) now()->diffInMinutes($this->sla_deadline, false);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high'     => 'orange',
            'medium'   => 'yellow',
            default    => 'green',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'         => 'Menunggu',
            'analyzing'       => 'Menganalisis',
            'awaiting_review' => 'Perlu Review',
            'routed'          => 'Diteruskan',
            'assigned'        => 'Ditugaskan',
            'in_progress'     => 'Diproses',
            'resolved'        => 'Selesai',
            'rejected'        => 'Ditolak',
            default           => ucfirst($this->status),
        };
    }

    public static function generateTicketNumber(): string
    {
        $year  = now()->year;
        $count = static::where('created_at', '>=', now()->startOfYear())
                       ->where('created_at', '<', now()->startOfYear()->addYear())
                       ->count() + 1;
        return sprintf('CA-%d-%05d', $year, $count);
    }
}
