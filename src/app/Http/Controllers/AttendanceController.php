<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Http\Requests\RequestRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    //勤怠画面表示
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
        if ($user->isStaff()) {
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

    //スタッフ勤怠詳細画面表示
    public function detail($id)
    {
        $attendance = Attendance::with('rests', 'user')->find($id);

        $rests = $attendance->rests;

        $date = Carbon::parse($attendance->date);
        $year = $date->format('Y年');
        $monthDay = $date->format('m月d日');

        return view('attendance_detail', compact('attendance', 'rests', 'year', 'monthDay'));
    }

    public function update(RequestRequest $request, $attendanceId)
    {
        // 既存の勤怠情報を取得
        $attendance = Attendance::findOrFail($attendanceId);

        // 年と月日を結合して日付形式に変換
        $date = Carbon::parse($request->year . '-' . $request->month_day);

        // 出勤時間と退勤時間を保存
        $attendance->update([
            'date' => $date,
            'clock_in' => Carbon::parse($date->format('Y-m-d') . ' ' . $request->clock_in),
            'clock_out' => Carbon::parse($date->format('Y-m-d') . ' ' . $request->clock_out),
        ]);

        // 休憩時間を保存（Restsテーブルの更新）
        if ($request->has('rest_start') && $request->has('rest_end')) {
            foreach ($request->rest_start as $index => $restStart) {
                $restEnd = $request->rest_end[$index] ?? null;
                $attendance->user->rests()->updateOrCreate(
                    ['id' => $attendance->user->rests[$index]->id ?? null],
                    [
                        'rest_start' => Carbon::parse($date->format('Y-m-d') . ' ' . $restStart),
                        'rest_end' => $restEnd ? Carbon::parse($date->format('Y-m-d') . ' ' . $restEnd) : null,
                    ]
                );
            }
        }

        // 備考を保存し、申請処理日付（現在の日付）をdateカラムに保存
        $attendance->user->requests()->updateOrCreate(
            ['user_id' => $attendance->user->id],
            [
                'remarks' => $request->remarks,
                'date' => Carbon::now(),  // 現在の日付を保存
            ]
        );

        return redirect('/stamp_correction_request/list', ['attendance' => $attendanceId]);
    }



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