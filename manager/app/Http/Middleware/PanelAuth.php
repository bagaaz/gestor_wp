<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PanelAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->get('panel_authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
