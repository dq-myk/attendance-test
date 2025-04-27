<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use App\Models\User;
use App\Http\Requests\ApplicationRequest;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            $monthDay = $date->format('n月j日');

            return view('admin_attendance_detail', compact('attendance', 'rests', 'year', 'monthDay'));
        }
    }

    //管理者スタッフ一覧表示
    public function adminStaffList()
    {
        $users = User::where('role', '!=', 'admin')
                        ->select('id', 'name', 'email')
                        ->get();

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

        $validated = $request->validated();

        $year = preg_replace('/[^0-9]/', '', $request->year);

        preg_match('/(\d{1,2})月(\d{1,2})日/', $request->month_day, $matches);

        if (count($matches) !== 3) {
            throw new \Exception("Invalid month_day format: {$request->month_day}");
        }

        $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);

        $date = "$year-$month-$day";

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \Exception("Invalid date format: {$date}");
        }

        $attendance->date = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
        $attendance->save();

        $attendance->update([
            'clock_in' => $validated['clock_in'],
            'clock_out' => $validated['clock_out'],
        ]);

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

        $latestRequest = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        if ($latestRequest) {
            $latestRequest->update([
                'user_id'  => $attendance->user_id,
                'admin_id' => auth()->id(),
                'date'     => now()->toDateString(),
                'status'   => '承認待ち',
                'remarks'  => $request->remarks,
            ]);
        } else {
            AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'  => $attendance->user_id,
                'admin_id' => auth()->id(),
                'date'     => now()->toDateString(),
                'status'   => '承認待ち',
                'remarks'  => $request->remarks,
            ]);
        }

        return redirect('/stamp_correction_request/list');
    }

    //スタッフ毎の勤怠一覧をCSV出力
    public function output(Request $request, $id)
    {
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $user = User::find($id);
        $userName = $user ? $user->name : '未登録ユーザー';

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with('rests')
            ->get();

        $response = new StreamedResponse(function () use ($attendances, $year, $month, $id, $userName) {
            $handle = fopen('php://output', 'w');

            $userRow = ["スタッフ名: {$userName}"];
            mb_convert_variables('SJIS-win', 'UTF-8', $userRow);
            fputcsv($handle, $userRow);

            $csvHeader = ['日付', '出勤', '退勤', '休憩', '合計'];
            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);
            fputcsv($handle, $csvHeader);

            foreach ($attendances as $attendance) {
                $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in) : null;
                $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out) : null;
                $totalRestTime = 0;

                if ($attendance->rests) {
                    foreach ($attendance->rests as $rest) {
                        if ($rest->rest_start && $rest->rest_end) {
                            $restStart = \Carbon\Carbon::parse($rest->rest_start);
                            $restEnd = \Carbon\Carbon::parse($rest->rest_end);
                            $totalRestTime += $restStart->diffInMinutes($restEnd);
                        }
                    }
                }

                $workTimeExcludingRest = 0;
                if ($clockIn && $clockOut) {
                    $workTimeExcludingRest = max($clockIn->diffInMinutes($clockOut) - $totalRestTime, 0);
                }

                $row = [
                    $attendance->date ? \Carbon\Carbon::parse($attendance->date)->format('Y/m/d') : '',
                    $clockIn ? $clockIn->format('H:i') : '',
                    $clockOut ? $clockOut->format('H:i') : '',
                    sprintf('%02d:%02d', floor($totalRestTime / 60), $totalRestTime % 60),
                    sprintf('%02d:%02d', floor($workTimeExcludingRest / 60), $workTimeExcludingRest % 60),
                ];

                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $fileName = "attendance_{$id}_{$year}_{$month}.csv";
        $response->headers->set('Content-Type', 'text/csv; charset=Shift_JIS');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }
}

