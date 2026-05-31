<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('panel_authenticated')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $validEmail    = config('wp.panel_email');
        $validPassword = config('wp.panel_password');

        if (
            hash_equals($validEmail, $request->email) &&
            hash_equals($validPassword, $request->password)
        ) {
            $request->session()->put('panel_authenticated', true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('panel_authenticated');
        $request->session()->regenerate();

        return redirect()->route('login');
    }
}
