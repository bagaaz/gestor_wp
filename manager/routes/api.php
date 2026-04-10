<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// API usada pelo wp-manager.sh para sincronizar sites
Route::post('/sites', [SiteController::class, 'apiStore']);
Route::delete('/sites/{name}', [SiteController::class, 'apiDestroy']);
