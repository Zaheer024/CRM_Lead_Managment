<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_code',
        'customer_name',
        'email',
        'phone',
        'source',
        'assigned_to',
        'status',
        'remarks',
    ];

    /**
     * The user the lead is assigned to.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * The follow-ups scheduled for this lead.
     */
    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class);
    }

    /**
     * A lead is considered "active" when its status is NEW or FOLLOW_UP.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [LeadStatus::NEW, LeadStatus::FOLLOW_UP], true);
    }

    /**
     * Returns true when the lead has reached a terminal status.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [LeadStatus::CONVERTED, LeadStatus::LOST], true);
    }
}
