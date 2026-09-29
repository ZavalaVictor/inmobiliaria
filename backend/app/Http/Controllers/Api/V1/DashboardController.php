<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardRequest;
use App\Http\Resources\DashboardResource;
use App\Services\Dashboard\DashboardPeriod;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request, DashboardService $service): DashboardResource
    {
        $period = DashboardPeriod::fromKey($request->validated('periodo') ?? 'mes');

        return new DashboardResource($service->build($request->user(), $period));
    }
}
