<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Application;
use App\Models\User;
use App\Http\Requests\ApplicationRequest;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    //管理者勤怠一覧画面表示
    public function adminListShow(Request $request)
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

    //管理者スタッフ一覧表示
    public function adminStaffList()
    {
        // roleカラムが 'admin' 以外のユーザーを取得
        $users = User::where('role', '!=', 'admin')
                        ->select('id', 'name', 'email')
                        ->get();

        // admin_staff_list.blade.php にデータを渡す
        return view('admin_staff_list', compact('users'));
    }

    //管理者スタッフ別勤怠一覧画面表示
    public function adminAttendanceStaff(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {

            $user = User::findOrFail($id);

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
                return view('admin_attendance_staff', compact('user', 'attendances', 'month', 'year'));
            } else {
                return redirect()->intended('/admin/login');
            }
        }


    //管理者勤怠詳細修正
    public function adminUpdate(ApplicationRequest $request, $id)
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

        // Applications テーブル更新
        Application::updateOrCreate(
            ['attendance_id' => $attendance->id],
            [
                'user_id' => auth()->id(),
                'date' => now()->toDateString(),
                'status' => '承認待ち',
                'remarks' => $request->remarks,
            ]
        );

        return redirect('/stamp_correction_request/list');
    }

    //管理者承認画面表示
    public function showApprove($application_id)
    {
        $application = application::findOrFail($application_id);
        $attendance = $application->attendance;

        $date = Carbon::parse($attendance->date);
        $year = $date->format('Y年');
        $monthDay = $date->format('m月d日');

        $rests = $attendance->rests;

        return view('admin_approve', compact('attendance', 'year', 'monthDay', 'rests'));
    }

    //管理者承認処理
    public function adminApprove(Application $attendance_correct_request)
    {
         // 承認処理
        $attendance_correct_request->status = '承認済み';
        $attendance_correct_request->approved_at = now();
        $attendance_correct_request->save();

        // リダイレクト
        return redirect('/admin/attendance/list');
    }
}
