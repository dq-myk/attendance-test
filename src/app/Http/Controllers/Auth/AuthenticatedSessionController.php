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
        // 認証の失敗時に特定のエラーメッセージを追加
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'login_error' => 'ログイン情報が登録されていません。',
            ]);
        }

        $request->session()->regenerate();

        // ロールに応じてリダイレクト先を設定
        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->intended('/admin/attendance/list');
        } elseif ($user->role === 'staff') {
            return redirect()->intended('/attendance');
        }

        return redirect()->intended('/home');
    }
}
