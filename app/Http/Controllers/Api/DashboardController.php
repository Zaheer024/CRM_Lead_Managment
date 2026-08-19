<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    /**
     * GET /api/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->leads->dashboard($request->user()));
    }
}
