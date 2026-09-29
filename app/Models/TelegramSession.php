<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramSession extends Model
{
    protected $fillable = [
        'telegram_user_id', 'telegram_username', 'first_name', 'last_name',
        'state', 'session_data', 'last_complaint_id', 'last_activity_at',
    ];

    protected $casts = [
        'session_data'       => 'array',
        'last_activity_at'   => 'datetime',
    ];

    public function lastComplaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'last_complaint_id');
    }

    public function getSessionValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->session_data, $key, $default);
    }

    public function setSessionValue(string $key, mixed $value): void
    {
        $data = $this->session_data ?? [];
        data_set($data, $key, $value);
        $this->update(['session_data' => $data]);
    }

    public function clearSession(): void
    {
        $this->update(['session_data' => [], 'state' => 'idle']);
    }
}
