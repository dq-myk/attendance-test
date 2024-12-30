<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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


    // スタッフ勤怠情報修正、申請
    // public function update(ApplicationRequest $request, $id)
    // {
    //     $validated = $request->validated();

    //     // 既存の勤怠情報を取得
    //     $attendance = Attendance::findOrFail($id);

    //     // 年と月日を結合して日付に変換
    //     $date = $request->year . '-' . $request->month_day;  // 'year-month_day' の形式で結合
    //     $attendance->date = \Carbon\Carbon::createFromFormat('Y-m-d', $date);  // 日付として変換して保存

    //     // 出勤・退勤時刻の更新
    //     $attendance->clock_in = \Carbon\Carbon::createFromFormat('H:i', $request->clock_in);
    //     $attendance->clock_out = \Carbon\Carbon::createFromFormat('H:i', $request->clock_out);

    //     // 休憩時間を保存（Restsテーブルの更新）
    //     if ($request->has('rest_start') && $request->has('rest_end')) {
    //         foreach ($request->rest_start as $index => $restStart) {
    //             $rest = $attendance->rests()->where('id', $index + 1)->first();
    //             if ($rest) {
    //                 $rest->rest_start = \Carbon\Carbon::createFromFormat('H:i', $restStart);
    //                 $rest->rest_end = \Carbon\Carbon::createFromFormat('H:i', $request->rest_end[$index]);
    //                 $rest->save();
    //             }
    //         }
    //     }

    //     // 備考を更新
    //     if ($request->has('remarks')) {
    //         $attendance->remarks = $request->remarks;
    //     }

    //     // 出勤情報を保存
    //     $attendance->save();

    //     // 申請処理を applications テーブルに保存
    //     $application = new Application();  // Application モデルを使用して新しい申請データを作成
    //     $application->attendance_id = $attendance->id;  // 申請に関連する勤怠IDを設定
    //     $application->admin_id = auth()->user()->id;  // admin_id を使用して現在のユーザーIDを設定
    //     $application->status = '承認待ち';  // 申請のステータスを設定
    //     $application->save();  // 申請データを保存

    //     return redirect('/stamp_correction_request/list');
    // }
}