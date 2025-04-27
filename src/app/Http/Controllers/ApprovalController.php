<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// フォームから送信されたデータや URL パラメータ、セッション情報などを取得できるが、ここでは不要
use Illuminate\Support\Facades\Auth;
// ログインしているユーザーの情報を取得したり、認証を制御したりできるが、ここでは不要
use App\Models\User;
// Userモデルは使用していないので、ここでは不要
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\Approval;
use Carbon\Carbon;

class ApprovalController extends Controller
{
    //管理者承認画面表示
    public function showApprove(AttendanceCorrectRequest $attendanceCorrectRequest)
    {
        $attendance = $attendanceCorrectRequest->attendance;
        $rests = $attendance->rests;

        $date = Carbon::parse($attendance->date);
        $year = $date->format('Y年');
        $monthDay = $date->format('n月j日');

        $remarks = $attendanceCorrectRequest->remarks;

        $isEditable = $attendanceCorrectRequest->status === '承認待ち';

        $restsToDisplay = [];
        if ($attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち') {
            $restsToDisplay = $rests->take(2);
        }

        return view('admin_approve', compact('attendance', 'rests', 'year', 'monthDay', 'remarks', 'restsToDisplay', 'attendanceCorrectRequest', 'isEditable'));
    }

    //管理者承認処理
    public function adminApprove($id)
    {
        $attendanceCorrectRequest = AttendanceCorrectRequest::findOrFail($id);

        if ($attendanceCorrectRequest->status === '承認済み') {
            return redirect()->back();
        }

        $attendanceCorrectRequest->status = '承認済み';
        $attendanceCorrectRequest->save();

        Approval::create([
            'attendance_correct_request_id' => $attendanceCorrectRequest->id,
            'admin_id'=> auth()->id(),
        ]);

        return redirect()->back();
    }
}
