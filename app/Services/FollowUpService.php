<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\FollowupStatus;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * Contains follow-up business rules.
 */
class FollowUpService
{
    public function __construct(private readonly LeadService $leads) {}

    /**
     * List follow-ups for a lead with role scoping.
     */
    public function list(User $authUser, Lead $lead): Paginator
    {
        $this->leads->assertLeadAccess($authUser, $lead);

        return $lead->followups()
            ->latest()
            ->simplePaginate(50);
    }

    /**
     * Create a follow-up for a lead.
     */
    public function create(User $authUser, Lead $lead, array $data): LeadFollowup
    {
        $this->leads->assertLeadAccess($authUser, $lead);

        if ($lead->isTerminal()) {
            throw new BusinessException(
                'Follow-ups cannot be created for a '.strtolower($lead->status).' lead.'
            );
        }

        if (now()->startOfDay()->isAfter($data['followup_date'])) {
            throw new BusinessException('The follow-up date cannot be in the past.');
        }

        return $lead->followups()->create([
            'followup_date' => $data['followup_date'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? FollowupStatus::PENDING,
            'created_by' => $authUser->id,
        ]);
    }

    /**
     * Update a follow-up.
     */
    public function update(User $authUser, LeadFollowup $followup, array $data): LeadFollowup
    {
        $this->leads->assertLeadAccess($authUser, $followup->lead);

        if (isset($data['followup_date']) && now()->startOfDay()->isAfter($data['followup_date'])) {
            throw new BusinessException('The follow-up date cannot be in the past.');
        }

        $followup->update($data);

        return $followup->fresh();
    }
}
