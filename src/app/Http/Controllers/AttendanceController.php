<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $currentDate = Carbon::now()->format('Y年m月d日 ') . '(' . $this->weekday(Carbon::now()->format('D')) . ')';
        $currentTime = Carbon::now()->format('H:i');
        $status = session('status', '勤務外');

        return view('attendance', compact('currentDate', 'currentTime', 'status'));
    }

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

    public function startWork(Request $request)
    {
        session(['status' => '勤務中']);
        return redirect('/attendance');
    }

    public function endWork(Request $request)
    {
        session(['status' => '勤務外']);
        return redirect('/attendance');
    }
}
