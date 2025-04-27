<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// ここでは送信されたデータを取得したり、リクエストに関する情報を処理する必要が無い為不要
use Illuminate\Support\Facades\Auth;
// Authファサードは使用されていない為、ここでは不要
use App\Http\Requests\LoginRequest;
// LoginRequestを使用しているメソッドが無い為、ここでは不要
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
// 3つともこの中で必要なデータには該当しない為、ここでは不要
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    //ユーザー登録処理
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()->route('login');
    }

    //管理者ログイン画面表示
    public function adminShow()
    {
        return view('admin_login');
    }

}

