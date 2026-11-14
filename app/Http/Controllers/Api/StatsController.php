<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private StatsService $stats)
    {
    }

    public function overview(StatsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->overview($request->validated()),
        ]);
    }
}
