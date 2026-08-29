<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\AdminQuestionResource;
use App\Services\Admin\AdminStatsService;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminStatsService $stats,
    ) {}

    public function index()
    {
        try {
            return $this->successResponse($this->stats->dashboard());
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin dashboard failed');
        }
    }

    public function questionBalance(Request $request)
    {
        try {
            $balance = $this->stats->questionBalance(
                max(1, (int) $request->integer('min_answers', 20)),
                min(50, max(1, (int) $request->integer('limit', 20))),
            );

            return $this->successResponse([
                'hardest' => AdminQuestionResource::collection($balance['hardest']),
                'easiest' => AdminQuestionResource::collection($balance['easiest']),
            ]);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin question balance failed');
        }
    }

    public function levelFunnel(Request $request)
    {
        try {
            return $this->successResponse($this->stats->levelFunnel(
                min(100, max(1, (int) $request->integer('limit', 30)))
            ));
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin level funnel failed');
        }
    }
}
