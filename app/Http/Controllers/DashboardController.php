<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics)
    {
    }

    public function index(Request $request)
    {
        return view('dashboard', [
            'dashboard' => $this->metrics->forUser(
                $request->user(),
                $request->integer('renewal_year')
            ),
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        return response()->json(
            $this->metrics->forUser(
                $request->user(),
                $request->integer('renewal_year')
            )
        );
    }
}
