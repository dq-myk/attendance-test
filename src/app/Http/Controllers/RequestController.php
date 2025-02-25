<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;

class RequestController extends Controller
{
    //管理者用、スタッフ用申請一覧確認
    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'wait'); // タブの選択（承認待ちなど）
        $user = Auth::user(); // ログインユーザーを取得

        if ($user->isAdmin()) {
            // 管理者の場合、全ての申請を取得
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
            // スタッフの場合、自分が申請したもの＋管理者が修正したものを取得
            $attendanceCorrectRequests = AttendanceCorrectRequest::where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('admin_id', $user->id); // 自分が申請した or 管理者が修正したデータ
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
