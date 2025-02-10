<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Application;
use App\Http\Requests\ApplicationRequest;
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

    public function update(ApplicationRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // バリデーション済みデータ取得
        $validated = $request->validated();

        // 年の「年」表記を削除
        $year = preg_replace('/[^0-9]/', '', $request->year); 

        // `02月07日` のような形式を `02-07` に変換
        preg_match('/(\d{1,2})月(\d{1,2})日/', $request->month_day, $matches);

        if (count($matches) !== 3) {
            throw new \Exception("Invalid month_day format: {$request->month_day}");
        }

        $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT); // 2桁にする
        $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);   // 2桁にする

        $date = "$year-$month-$day";

        // `$date` の形式をチェック
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \Exception("Invalid date format: {$date}");
        }

        // `date` カラムに保存
        $attendance->date = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
        $attendance->save();

        // 勤怠データ更新
        $attendance->update([
            'clock_in' => $validated['clock_in'],
            'clock_out' => $validated['clock_out'],
        ]);

        // 休憩データの削除と再登録
        $attendance->rests()->delete();

        if (!empty($validated['rest_start']) && !empty($validated['rest_end'])) {
            foreach ($validated['rest_start'] as $index => $start) {
                if (!empty($start) && !empty($validated['rest_end'][$index])) {
                    $attendance->rests()->create([
                        'rest_start' => $start,
                        'rest_end' => $validated['rest_end'][$index],
                    ]);
                }
            }
        }

        // Applications テーブルへの新規登録
        Application::create([
            'user_id' => auth()->id(), // ログイン中のユーザーのIDをセット
            'date' => now()->toDateString(), // 申請日
            'status' => '承認待ち',
            'remarks' => $request->remarks,
        ]);

        return redirect('/request/list');
    }

    //申請状況確認
    public function requestShow(Request $request)
{
    $tab = $request->query('tab', 'wait');

    $query = Application::query();

    if ($tab === 'wait') {
        $query->where('status', '承認待ち');
    } elseif ($tab === 'complete') {
        $query->where('status', '承認済み');
    }

    $applications = $query->get();

    return view('request_list', [
        'attendances' => $applications,
        'tab' => $tab,
    ]);
}

}