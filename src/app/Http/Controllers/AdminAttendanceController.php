<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    //管理者勤怠一覧画面表示
    public function adminList(Request $request)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {

            try {
                $date = Carbon::parse($request->query('date', Carbon::today()->toDateString()))->toDateString();
            } catch (\Exception $e) {
                $date = Carbon::today()->toDateString();
            }

            $attendances = Attendance::whereDate('date', $date)->get();

            foreach ($attendances as $attendance) {
            $totalRestTime = 0;
            $rests = $attendance->rests;

            foreach ($rests as $rest) {
                $restStart = Carbon::parse($rest->rest_start);
                $restEnd = Carbon::parse($rest->rest_end);
                $totalRestTime += $restStart->diffInMinutes($restEnd);
            }

            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);
            $workDuration = $clockIn->diffInMinutes($clockOut);

            $workTimeExcludingRest = $workDuration - $totalRestTime;

            $attendance->totalRestTime = $totalRestTime;
            $attendance->workTimeExcludingRest = $workTimeExcludingRest;
        }

            return view('admin_attendance_list', compact('attendances', 'date'));
        } else {
            return redirect()->intended('/admin/login');
        }
    }

    //管理者勤怠詳細画面表示
    public function adminDetail($id)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            $attendance = Attendance::with('rests', 'user')->find($id);

            $rests = $attendance->rests;

            $date = Carbon::parse($attendance->date);
            $year = $date->format('Y年');
            $monthDay = $date->format('m月d日');

            return view('admin_attendance_detail', compact('attendance', 'rests', 'year', 'monthDay'));
        }
    }

}
