<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\User;
use App\Models\Approval;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    //管理者承認画面表示
    public function showApprove(AttendanceCorrectRequest $attendanceCorrectRequest)
    {
        $attendance = $attendanceCorrectRequest->attendance; // AttendanceCorrectRequest から関連する Attendance を取得
        $rests = $attendance->rests;

        // 日付フォーマット
        $date = Carbon::parse($attendance->date);
        $year = $date->format('Y年');
        $monthDay = $date->format('m月d日');

        // 備考を取得
        $remarks = $attendanceCorrectRequest->remarks;

        $isEditable = $attendanceCorrectRequest->status === '承認待ち';

        // 休憩時間の表示ルール
        $restsToDisplay = [];
        if ($attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち') {
            // 休憩時間が1つしかない場合でも、2つ目を追加
            $restsToDisplay = $rests->take(2);
        }

        return view('admin_approve', compact('attendance', 'rests', 'year', 'monthDay', 'remarks', 'restsToDisplay', 'attendanceCorrectRequest', 'isEditable'));
    }

    //管理者承認処理
    public function adminApprove($id)
    {
        // 1) 対象の申請を取得
        $attendanceCorrectRequest = AttendanceCorrectRequest::findOrFail($id);

        // 2) 既に承認済みかどうかなどのチェック(任意)
        if ($attendanceCorrectRequest->status === '承認済み') {
            return redirect()->back();
        }

        // 3) AttendanceCorrectRequestsテーブルの status を更新
        $attendanceCorrectRequest->status = '承認済み';
        $attendanceCorrectRequest->save();

        // 4) Approvalsテーブルに承認ログを追加
        Approval::create([
            'attendance_correct_request_id' => $attendanceCorrectRequest->id,
            'admin_id'=> auth()->id(),  // ログイン中の管理者IDを保存
        ]);

        return redirect()->back();
    }
}
