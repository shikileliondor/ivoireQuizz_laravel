<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'stats' => $dashboard->stats(),
            'activity' => $dashboard->dailyActivity(),
            'latestPlayers' => $dashboard->latestPlayers(),
            'topPlayers' => $dashboard->topPlayers(),
            'pendingReports' => $dashboard->pendingReports(),
        ]);
    }
}
