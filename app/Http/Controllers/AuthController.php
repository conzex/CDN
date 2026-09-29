<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:' . strtolower($request->input('username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            ActivityLogger::log('login_failed', [
                'meta' => ['username' => $request->input('username'), 'reason' => 'rate_limited'],
            ]);
            return back()->withErrors([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('username');
        }

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            ActivityLogger::log('login', [
                'user_id' => Auth::id(),
                'meta' => ['username' => $credentials['username']],
            ]);

            return redirect()->intended('/admin');
        }

        RateLimiter::hit($throttleKey, 60);

        ActivityLogger::log('login_failed', [
            'meta' => ['username' => $credentials['username'], 'reason' => 'invalid_credentials'],
        ]);

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLogger::log('logout', [
                'user_id' => Auth::id(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
