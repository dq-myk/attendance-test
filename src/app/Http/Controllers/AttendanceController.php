<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $currentDate = Carbon::now()->format('Y年m月d日 ') . '(' . $this->weekday(Carbon::now()->format('D')) . ')';
        $currentTime = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', Auth::id())
                                ->where('date', Carbon::now()->toDateString())
                                ->first();

        $status = $attendance ? $attendance->status : '勤務外';

        return view('attendance', [
            'status' => $status,
            'currentDate' => $currentDate,
            'currentTime' => $currentTime,
            'isAlreadyCheckedIn' => $attendance && $attendance->status !== '退勤済',
        ]);
    }

    //曜日表示を日本語へ変換
    private function weekday($weekday)
    {
        $weekdays = [
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
            'Sun' => '日',
        ];

        return $weekdays[$weekday];
    }

    // 出勤時刻を登録
    public function startWork(Request $request)
    {
        $attendance = Attendance::create([
            'user_id' => Auth::id(),
            'date' => Carbon::now()->toDateString(),
            'clock_in' => Carbon::now(),
            'status' => '出勤中',
        ]);

        session(['attendance_id' => $attendance->id]);
        session(['status' => '出勤中']);
        return redirect('/attendance');
    }

    // 退勤時刻を登録
    public function endWork(Request $request)
    {
        $attendance = Attendance::find(session('attendance_id'));
        if ($attendance) {
            $attendance->update([
                'clock_out' => Carbon::now(),
                'status' => '退勤済'
            ]);
        }

        session(['status' => '退勤済']);
        session()->flash('message', 'お疲れ様でした。');
        return redirect('/attendance');
    }

    //スタッフ勤怠一覧画面表示
    public function listShow(Request $request)
    {
        $user = Auth::user();
    if ($user->role === 'staff') {
        try {
            $month = $request->query('month', Carbon::now()->month);
            $year = $request->query('year', Carbon::now()->year);
        } catch (\Exception $e) {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
        }

        Carbon::setLocale('ja');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        if ($attendances->isEmpty()) {
            $attendances = collect();
        }

            foreach ($attendances as $attendance) {
            $totalRestTime = 0;
            $rests = isset($attendance->rests) ? $attendance->rests : [];

            foreach ($rests as $rest) {
                $restStart = Carbon::parse($rest->rest_start);
                $restEnd = Carbon::parse($rest->rest_end);
                $totalRestTime += $restStart->diffInMinutes($restEnd);
            }

            if (isset($attendance->clock_in) && isset($attendance->clock_out)) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);
                $workDuration = $clockIn->diffInMinutes($clockOut);

                $workTimeExcludingRest = $workDuration - $totalRestTime;
            } else {
                $workTimeExcludingRest = null;
            }

            $attendance->totalRestTime = $totalRestTime;
            $attendance->workTimeExcludingRest = $workTimeExcludingRest;
        }

        return view('attendance_list', compact('attendances', 'month', 'year'));
    } else {
        return redirect()->intended('/login');
    }
    }

    //勤怠詳細画面表示
    public function detail($id)
    {
        return view('attendance_detail', ['id' => $id]);
    }

    //管理者勤怠一覧、前日、翌日参照
    // public function index(Request $request)
    // {
    //     $user = Auth::user();

    //     if (!$user || $user->role !== 'admin') {
    //         return redirect('/admin/login');
    //     }

    //     $date = $request->query('date', Carbon::now()->toDateString());

    //     $attendances = Attendance::whereDate('date', $date)->with('rests')->get();

    //     return view('admin_attendance_list', compact('attendances', 'date'));
    // }
}