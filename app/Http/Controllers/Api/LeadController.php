<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    /**
     * GET /api/leads
     */
    public function index(Request $request): JsonResponse
    {
        $leads = $this->leads->list($request->user(), $request);

        return response()->json($leads);
    }

    /**
     * POST /api/leads
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leads->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Lead created successfully.',
            'lead' => $lead->load('assignee:id,name,email'),
        ], 201);
    }

    /**
     * GET /api/leads/{id}
     */
    public function show(Request $request, Lead $lead): JsonResponse
    {
        $this->leads->assertLeadAccess($request->user(), $lead);

        return response()->json([
            'lead' => $lead->load('assignee:id,name,email', 'followups:id,lead_id,followup_date,notes,status,created_by,created_at'),
        ]);
    }

    /**
     * PUT /api/leads/{id}
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leads->update($request->user(), $lead, $request->validated());

        return response()->json([
            'message' => 'Lead updated successfully.',
            'lead' => $lead->load('assignee:id,name,email'),
        ]);
    }

    /**
     * DELETE /api/leads/{id}
     */
    public function destroy(Request $request, Lead $lead): JsonResponse
    {
        $this->leads->delete($request->user(), $lead);

        return response()->json(['message' => 'Lead deleted successfully.']);
    }
}
