<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Application;
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

        // Applications テーブルへの新規登録
        Application::create([
            'user_id' => auth()->id(), // ログイン中のユーザーのIDをセット
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(), // 申請日
            'status' => '承認待ち',
            'remarks' => $request->remarks,
        ]);

        return redirect('/admin/stamp_correction_request/list');
    }

    //管理者申請一覧確認
    public function adminList(Request $request)
    {
        $tab = $request->query('tab', 'wait'); // タブの選択（承認待ちなど）

        $user = Auth::user(); // ログインユーザーを取得

        // ユーザーが管理者か確認
        if ($user->isAdmin()) {
            // 管理者用の申請一覧を取得（管理者は他のスタッフの申請も確認できる）
            $applications = Application::when($tab === 'wait', function ($query) {
                    return $query->where('status', '承認待ち');
                })
                ->when($tab === 'complete', function ($query) {
                    return $query->where('status', '承認済み');
                })
                ->get();

            return view('request_list', [
                'attendances' => $applications,
                'tab' => $tab,
            ]);
        }
    }
}
