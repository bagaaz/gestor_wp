<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Sites CRUD
Route::resource('sites', SiteController::class)->except(['edit', 'update']);

// Ações extras nos sites
Route::post('/sites/{site}/backup', [SiteController::class, 'backup'])->name('sites.backup');
Route::post('/sites/{site}/clone', [SiteController::class, 'clone'])->name('sites.clone');

// Export para produção
Route::get('/sites/{site}/export', [ExportController::class, 'showExportForm'])->name('sites.export');
Route::post('/sites/{site}/export', [ExportController::class, 'export'])->name('sites.export.generate');

// Logs
Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::post('/logs/clear-laravel', [LogController::class, 'clearLaravel'])->name('logs.clear-laravel');

// Configurações
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
