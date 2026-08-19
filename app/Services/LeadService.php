<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Contains all lead related business rules so controllers stay thin.
 */
class LeadService
{
    /**
     * Build the paginated listing respecting role based scoping and filters.
     */
    public function list(User $user, Request $request)
    {
        $query = Lead::query()
            ->with('assignee:id,name,email')
            ->withCount('followups');

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('lead_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($source = $request->string('source')->trim()->toString()) {
            $query->where('source', $source);
        }

        if ($assigned = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assigned);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15) ?: 15);
    }

    /**
     * Count leads by status for the dashboard.
     */
    public function dashboard(User $user): array
    {
        $query = Lead::query();

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        }

        $counts = (clone $query)->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [
            'total_leads' => (clone $query)->count(),
        ];

        foreach (LeadStatus::all() as $status) {
            $result[strtolower($status)] = $counts[$status] ?? 0;
        }

        return $result;
    }

    /**
     * Create a new lead applying assignment and duplicate rules.
     */
    public function create(User $authUser, array $data): Lead
    {
        $email = strtolower($data['email']);

        $this->assertNoActiveDuplicate($email);

        $assignedTo = $data['assigned_to'] ?? null;

        if ($assignedTo) {
            $this->assertAssignableTo($assignedTo);
            if (! $authUser->isAdmin() && (int) $assignedTo !== $authUser->id) {
                throw new BusinessException('A sales user may only assign leads to themselves.', 403);
            }
        } elseif (! $authUser->isAdmin()) {
            $assignedTo = $authUser->id;
        }

        return Lead::create([
            'lead_code' => $this->generateLeadCode(),
            'customer_name' => $data['customer_name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'],
            'assigned_to' => $assignedTo,
            'status' => $data['status'] ?? LeadStatus::NEW,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    /**
     * Update an existing lead respecting status transitions, conversion
     * protection, assignment rules and duplicate prevention.
     */
    public function update(User $authUser, Lead $lead, array $data): Lead
    {
        $this->assertLeadAccess($authUser, $lead);

        if ($lead->status === LeadStatus::CONVERTED) {
            throw new BusinessException('Converted leads cannot be edited.', 409);
        }

        if (array_key_exists('status', $data) && $data['status'] !== $lead->status) {
            if (! LeadStatus::allows($lead->status, $data['status'])) {
                throw new BusinessException(
                    "Invalid status transition: {$lead->status} -> {$data['status']} is not allowed."
                );
            }
        }

        if (array_key_exists('assigned_to', $data)) {
            if (! $authUser->isAdmin()) {
                throw new BusinessException('Only an administrator can (re)assign leads.', 403);
            }
            $this->assertAssignableTo($data['assigned_to']);
        }

        if (array_key_exists('email', $data) && strtolower($data['email']) !== $lead->email) {
            $this->assertNoActiveDuplicate(strtolower($data['email']), $lead->id);
        }

        $data['email'] = strtolower($data['email'] ?? $lead->email);

        $lead->update($data);

        return $lead->fresh();
    }

    /**
     * Delete a lead. Converted leads are protected; only admins may delete.
     */
    public function delete(User $authUser, Lead $lead): void
    {
        if (! $authUser->isAdmin()) {
            throw new BusinessException('Only an administrator can delete leads.', 403);
        }

        if ($lead->status === LeadStatus::CONVERTED) {
            throw new BusinessException('Converted leads cannot be deleted.', 409);
        }

        $lead->delete();
    }

    /**
     * Ensure the authenticated user may see/operate on a lead.
     */
    public function assertLeadAccess(User $authUser, Lead $lead): void
    {
        if (! $authUser->isAdmin() && $lead->assigned_to !== $authUser->id) {
            throw new BusinessException('You do not have access to this lead.', 403);
        }
    }

    /**
     * Validate that a user can be assigned leads (active SALES member).
     */
    public function assertAssignableTo(int $userId): void
    {
        $user = User::find($userId);

        if (! $user || ! $user->isActive()) {
            throw new BusinessException('A lead can only be assigned to an active user.');
        }

        if (! $user->isSales()) {
            throw new BusinessException('A lead can only be assigned to a user with the SALES role.');
        }
    }

    /**
     * Reject creation of a second active lead for the same email.
     */
    protected function assertNoActiveDuplicate(string $email, ?int $exceptId = null): void
    {
        $exists = Lead::query()
            ->whereIn('status', LeadStatus::activeStatuses())
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->where('email', $email)
            ->exists();

        if ($exists) {
            throw new BusinessException('A lead with this email already exists and is still active.');
        }
    }

    /**
     * Generate a unique, human friendly lead code.
     */
    protected function generateLeadCode(): string
    {
        do {
            $code = 'LD-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Lead::where('lead_code', $code)->exists());

        return $code;
    }
}
