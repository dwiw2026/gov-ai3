<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'complaint_id', 'event', 'actor_type', 'actor_id',
        'description', 'ai_model', 'ai_confidence',
        'ai_input', 'ai_output', 'previous_value', 'new_value',
    ];

    protected $casts = [
        'ai_input'      => 'array',
        'ai_output'     => 'array',
        'ai_confidence' => 'float',
        'created_at'    => 'datetime',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
