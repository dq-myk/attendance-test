<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;


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

        return redirect('/login');
    }

    //管理者ログイン画面表示
    public function adminShow()
    {
        return view('admin_login');
    }

    //管理者ログイン
    public function adminLogin(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'login_error' => 'ログイン情報が登録されていません。',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->role === 'admin') {

            try {
                $date = Carbon::parse($request->query('date', Carbon::today()->toDateString()))->toDateString();
            } catch (\Exception $e) {
                $date = Carbon::today()->toDateString();
            }

            $attendances = Attendance::whereDate('date', $date)->get();

            // 各勤怠の情報に休憩時間と勤務時間を追加
            foreach ($attendances as $attendance) {
            // Calculate total rest time for each attendance
            $totalRestTime = 0;
            $rests = $attendance->rests;  // Assuming the attendance has a relationship to rests

            foreach ($rests as $rest) {
                $restStart = Carbon::parse($rest->rest_start);
                $restEnd = Carbon::parse($rest->rest_end);
                $totalRestTime += $restStart->diffInMinutes($restEnd);
            }

            // Calculate work time excluding rest
            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);
            $workDuration = $clockIn->diffInMinutes($clockOut);

            $workTimeExcludingRest = $workDuration - $totalRestTime;

            // Add to the attendance object or pass to view
            $attendance->totalRestTime = $totalRestTime;
            $attendance->workTimeExcludingRest = $workTimeExcludingRest;
        }

            return view('admin_attendance_list', compact('attendances', 'date'));
        } else {
            return redirect()->intended('/admin/login');
        }
    }
}
