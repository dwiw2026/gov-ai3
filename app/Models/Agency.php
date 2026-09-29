<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $fillable = [
        'code', 'name', 'phone', 'email', 'description', 'sla_minutes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(RoutingRule::class);
    }

    public function availableOfficers(): HasMany
    {
        return $this->hasMany(Officer::class)->where('status', 'available');
    }
}
