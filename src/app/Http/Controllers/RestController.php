<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

class RestController extends Controller
{
    // 休憩開始情報を登録
    public function startRest(Request $request)
    {
        if (!session('attendance_id')) {
            return redirect('/attendance');
        }

        $rest = Rest::create([
            'attendance_id' => session('attendance_id'),
            'rest_start' => Carbon::now(),
        ]);

        session(['rest_id' => $rest->id]);

        $attendance = Attendance::find(session('attendance_id'));
        if ($attendance) {
            $attendance->update(['status' => '休憩中']);
        }

        session(['status' => '休憩中']);
        return redirect('/attendance');
    }

    //休憩終了情報を登録
    public function endRest(Request $request)
    {
        $rest = Rest::find(session('rest_id'));
        if ($rest) {
            $rest->update([
                'rest_end' => Carbon::now(),
            ]);
        }

        $attendance = Attendance::find(session('attendance_id'));
        if ($attendance) {
            $attendance->update(['status' => '出勤中']);
        }

        session(['status' => '出勤中']);
        return redirect('/attendance');
    }
}
