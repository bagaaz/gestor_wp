<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\ActivityLog;
use App\Services\DockerService;
use App\Services\WordPressService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DockerService $docker,
        private WordPressService $wordpress
    ) {}

    public function index()
    {
        // Sincronizar sites do filesystem
        $this->wordpress->syncSites();

        $sites = Site::orderBy('name')->get();
        $containers = $this->docker->getContainersStatus();
        $recentLogs = ActivityLog::with('site')->latest()->take(10)->get();

        $stats = [
            'total_sites' => $sites->count(),
            'active_sites' => $sites->where('status', 'active')->count(),
            'total_disk' => $sites->sum('disk_usage'),
            'is_running' => $this->docker->isRunning(),
        ];

        return view('dashboard.index', compact('sites', 'containers', 'recentLogs', 'stats'));
    }
}
