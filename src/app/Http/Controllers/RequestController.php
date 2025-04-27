<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
// このモデルは参照していない為、ここでは不要
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectRequest;

class RequestController extends Controller
{
    //管理者用、スタッフ用申請一覧確認
    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'wait');
        $user = Auth::user();

        if ($user->isAdmin()) {
            $attendanceCorrectRequests = AttendanceCorrectRequest::when($tab === 'wait', function ($query) {
                    return $query->where('status', '承認待ち');
                })
                ->when($tab === 'complete', function ($query) {
                    return $query->where('status', '承認済み');
                })
                ->get() ?? collect();

            return view('admin_request_list', [
                'attendances' => $attendanceCorrectRequests,
                'tab' => $tab,
            ]);
        } elseif ($user->isStaff()) {
            $attendanceCorrectRequests = AttendanceCorrectRequest::where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('admin_id', $user->id);
                })
                ->when($tab === 'wait', function ($query) {
                    return $query->where('status', '承認待ち');
                })
                ->when($tab === 'complete', function ($query) {
                    return $query->where('status', '承認済み');
                })
                ->get() ?? collect();

            return view('request_list', [
                'attendances' => $attendanceCorrectRequests,
                'tab' => $tab,
            ]);
        }

        return redirect('/login');
    }
}
