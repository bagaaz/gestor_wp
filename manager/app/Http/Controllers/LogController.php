<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'activity');

        // Activity logs (do banco)
        $activityLogs = ActivityLog::with('site')
            ->latest()
            ->paginate(50, ['*'], 'activity_page');

        // Laravel log file
        $laravelLog = '';
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            // Lê as últimas 500 linhas
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($lines, -500);
            $laravelLog = implode("\n", $lines);
        }

        return view('logs.index', compact('activityLogs', 'laravelLog', 'tab'));
    }

    public function clearLaravel()
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }

        return back()->with('success', 'Log do Laravel limpo com sucesso.');
    }
}
