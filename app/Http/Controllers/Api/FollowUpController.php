<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowUpRequest;
use App\Http\Requests\UpdateFollowUpRequest;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function __construct(private readonly FollowUpService $followups) {}

    /**
     * GET /api/leads/{id}/followups
     */
    public function index(Request $request, Lead $lead): JsonResponse
    {
        $followups = $this->followups->list($request->user(), $lead);

        return response()->json($followups);
    }

    /**
     * POST /api/leads/{id}/followups
     */
    public function store(StoreFollowUpRequest $request, Lead $lead): JsonResponse
    {
        $followup = $this->followups->create($request->user(), $lead, $request->validated());

        return response()->json([
            'message' => 'Follow-up created successfully.',
            'followup' => $followup->fresh(),
        ], 201);
    }

    /**
     * PUT /api/followups/{id}
     */
    public function update(UpdateFollowUpRequest $request, LeadFollowup $followup): JsonResponse
    {
        $followup = $this->followups->update($request->user(), $followup, $request->validated());

        return response()->json([
            'message' => 'Follow-up updated successfully.',
            'followup' => $followup,
        ]);
    }
}
