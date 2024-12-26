<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'login_error' => 'ログイン情報が登録されていません。',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->isStaff()) {
            return redirect()->intended('/attendance');
        } else {
            return redirect()->intended('/login');
        }

    }

    public function adminStore(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'login_error' => 'ログイン情報が登録されていません。',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->intended('/admin/attendance/list');
        } else {
            return redirect()->intended('/admin/login');
        }
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $redirectTo = '/login';

        if ($user) {
            if ($user->role === 'admin') {
                $redirectTo = '/admin/login';
            } elseif ($user->role === 'staff') {
                $redirectTo = '/login';
            }
        }

        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($redirectTo);
    }
}




