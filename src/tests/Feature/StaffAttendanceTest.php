<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectRequest;
use Tests\TestCase;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function createStaffAttendanceList()
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($staff)
            ->post('/attendance/start')
            ->assertRedirect('/attendance');

        $attendance = Attendance::where('user_id', $staff->id)->first();
        $this->assertTrue(
            Carbon::parse($attendance->clock_in)->between(now()->subMinutes(6), now()),
            "clock_in is not within the expected range: {$attendance->clock_in}"
        );
                $this->actingAs($staff)
            ->post('/attendance/rest-start')
            ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start' => $latestRest->rest_start,
        ]);

        $this->actingAs($staff)
            ->post('/attendance/rest-end')
            ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_end' => $latestRest->rest_end,
        ]);

        $this->actingAs($staff)
            ->post('/attendance/end')
            ->assertRedirect('/attendance');

        $attendance = Attendance::where('user_id', $staff->id)->first();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $staff->id,
            'status' => '退勤済',
        ]);

        $this->assertTrue(
            Carbon::parse($attendance->clock_out)->between(now()->subMinutes(6), now()),
            "clock_out is not within the expected range: {$attendance->clock_out}"
        );

        $weekDays = [
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
            'Sun' => '日',
        ];

        $date = Carbon::parse($attendance->date);
        $formattedDate = $date->format('m/d') . ' (' . $weekDays[$date->format('D')] . ')';

        return [$staff, $attendance, $formattedDate];
    }

    //スタッフにて勤怠情報確認
    public function test_staff_attendance_list()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff)
            ->get("/attendance/list")
            ->assertStatus(200)
            ->assertSee($formattedDate)
            ->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'))
            ->assertSee(Carbon::parse($attendance->totalRestTime * 60)->format('H:i'))
            ->assertSee(Carbon::parse($attendance->workTimeExcludingRest * 60)->format('H:i'));

    }

    //スタッフにて当月と前月と翌月の勤怠情報確認
    public function test_previous_and_following_month()
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
        ]);

        $currentDate = Carbon::now();
        $previousDate = Carbon::now()->subMonth();
        $nextDate = Carbon::now()->addMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => $currentDate->toDateString(),
            'clock_in' => $currentDate->format('Y-m-01 09:00:00'),
            'clock_out' => $currentDate->format('Y-m-01 18:00:00'),
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => $currentDate->format('Y-m-01 12:00:00'),
            'rest_end' => $currentDate->format('Y-m-01 13:00:00'),
        ]);

        $previousAttendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => $previousDate->toDateString(),
            'clock_in' => $previousDate->format('Y-m-01 09:00:00'),
            'clock_out' => $previousDate->format('Y-m-01 18:00:00'),
        ]);

        Rest::factory()->create([
            'attendance_id' => $previousAttendance->id,
            'rest_start' => $previousDate->format('Y-m-01 12:00:00'),
            'rest_end' => $previousDate->format('Y-m-01 13:00:00'),
        ]);

        $nextAttendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => $nextDate->toDateString(),
            'clock_in' => $nextDate->format('Y-m-01 09:00:00'),
            'clock_out' => $nextDate->format('Y-m-01 18:00:00'),
        ]);

        Rest::factory()->create([
            'attendance_id' => $nextAttendance->id,
            'rest_start' => $nextDate->format('Y-m-01 12:00:00'),
            'rest_end' => $nextDate->format('Y-m-01 13:00:00'),
        ]);

        $totalRestTimePrev = $previousAttendance->rests->sum(fn ($rest) => Carbon::parse($rest->rest_start)->diffInMinutes($rest->rest_end));
        $workTimeExcludingRestPrev = Carbon::parse($previousAttendance->clock_in)->diffInMinutes($previousAttendance->clock_out) - $totalRestTimePrev;

        $totalRestTimeNext = $nextAttendance->rests->sum(fn ($rest) => Carbon::parse($rest->rest_start)->diffInMinutes($rest->rest_end));
        $workTimeExcludingRestNext = Carbon::parse($nextAttendance->clock_in)->diffInMinutes($nextAttendance->clock_out) - $totalRestTimeNext;

        $this->actingAs($staff)
            ->get("/attendance/list?month={$currentDate->month}&year={$currentDate->year}")
            ->assertStatus(200)
            ->assertSee($currentDate->year)
            ->assertSee(sprintf('%02d', $currentDate->month))
            ->assertSee(Carbon::parse($attendance->date)->translatedFormat('m/d (D)'))
            ->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'))
            ->assertSee('01:00')
            ->assertSee('08:00');

        $this->actingAs($staff)
            ->get("/attendance/list?month={$previousDate->month}&year={$previousDate->year}")
            ->assertStatus(200)
            ->assertSee($previousDate->year)
            ->assertSee(sprintf('%02d', $previousDate->month))
            ->assertSee(Carbon::parse($previousAttendance->date)->translatedFormat('m/d (D)'))
            ->assertSee(Carbon::parse($previousAttendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($previousAttendance->clock_out)->format('H:i'))
            ->assertSee(CarbonInterval::minutes($totalRestTimePrev)->cascade()->format('%H:%I'))
            ->assertSee(CarbonInterval::minutes($workTimeExcludingRestPrev)->cascade()->format('%H:%I'));

        $this->actingAs($staff)
            ->get("/attendance/list?month={$nextDate->month}&year={$nextDate->year}")
            ->assertStatus(200)
            ->assertSee($nextDate->year)
            ->assertSee(sprintf('%02d', $nextDate->month))
            ->assertSee(Carbon::parse($nextAttendance->date)->translatedFormat('m/d (D)'))
            ->assertSee(Carbon::parse($nextAttendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($nextAttendance->clock_out)->format('H:i'))
            ->assertSee(CarbonInterval::minutes($totalRestTimeNext)->cascade()->format('%H:%I'))
            ->assertSee(CarbonInterval::minutes($workTimeExcludingRestNext)->cascade()->format('%H:%I'));
    }

    //スタッフにて勤怠詳細画面遷移
    public function test_screen_transition_attendance_detail()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff)
            ->get("/attendance/{$attendance->id}")
            ->assertStatus(200);
    }

    //スタッフにて勤怠詳細画面情報確認
    public function test_attendance_detail()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();
        $rest = $attendance->rests->first();

        $response = $this->actingAs($staff)->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $response->assertSee($staff->name)
                ->assertSee(Carbon::parse($attendance->date)->format('Y年') . '"', false)
                ->assertSee(Carbon::parse($attendance->date)->format('n月j日') . '"', false)
                ->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'))
                ->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));

        if ($rest) {
            $response->assertSee(Carbon::parse($rest->rest_start)->format('H:i'))
                    ->assertSee(Carbon::parse($rest->rest_end)->format('H:i'));
        }
    }

    //スタッフ出勤時間が退勤時間より後になっている場合
    public function test_attendance_update_work_time_error()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        $response = $this->put("/attendance/{$attendance->id}", [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
        ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //スタッフ休憩開始時間が退勤時間より後の場合
    public function test_attendance_update_rest_start_time_error()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        $restData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rest_start' => ['18:30'],
            'rest_end' => ['13:00'],
            'remarks' => 'テスト用の備考',
        ];

        $response = $this->put("/attendance/{$attendance->id}", $restData);

        $response->assertSessionHasErrors(['custom_error' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    //スタッフ休憩終了間が退勤時間より後の確認
    public function test_attendance_update_rest_end_time_error()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        $restData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rest_start' => ['12:00'],
            'rest_end' => ['18:30'],
            'remarks' => 'テスト用の備考',
        ];

        $response = $this->put("/attendance/{$attendance->id}", $restData);

        $response->assertSessionHasErrors(['custom_error' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    //スタッフ備考の入力必須
    public function test_remarks_is_required()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        $restData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rest_start' => ['12:00'],
            'rest_end' => ['13:00'],
            'remarks' => '',
        ];

        $response = $this->put("/attendance/{$attendance->id}", $restData);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

    //スタッフ修正処理
    public function test_attendance_request()
    {
        $staff1 = $this->createStaffAttendanceList();
        $staff2 = $this->createStaffAttendanceList();
        $staff3 = $this->createStaffAttendanceList();

        $attendance1 = $staff1[1];
        $attendance2 = $staff2[1];
        $attendance3 = $staff3[1];

        $this->actingAs($staff1[0]);
        $this->put("/attendance/{$attendance1->id}", $this->getAttendanceData());

        $this->actingAs($staff2[0]);
        $this->put("/attendance/{$attendance2->id}", $this->getAttendanceData());

        $this->actingAs($staff3[0]);
        $this->put("/attendance/{$attendance3->id}", $this->getAttendanceData());

        $this->attendanceRequest($staff1[0], $attendance1, '承認待ち');
        $this->attendanceRequest($staff2[0], $attendance2, '承認待ち');
        $this->attendanceRequest($staff3[0], $attendance3, '承認待ち');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=wait')
            ->assertStatus(200)
            ->assertSee($staff1[0]->name)
            ->assertSee($staff2[0]->name)
            ->assertSee($staff3[0]->name);

        $attendanceCorrectRequest = AttendanceCorrectRequest::where('attendance_id', $attendance1->id)->latest()->first();
        $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=wait')
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendanceCorrectRequest->date)->format('Y/m/d'))
            ->assertSee('テスト用の備考')
            ->assertSee(Carbon::parse($attendanceCorrectRequest->created_at)->format('Y/m/d'));

        $this->actingAs($admin)
            ->get("/stamp_correction_request/approve/{$attendanceCorrectRequest->id}")
            ->assertStatus(200)
            ->assertSee($staff1[0]->name)
            ->assertSee('2025年')
            ->assertSee('3月1日')
            ->assertSee('09:30')
            ->assertSee('18:30')
            ->assertSee('12:15')
            ->assertSee('13:15')
            ->assertSee('テスト用の備考');

        $this->actingAs($staff1[0])
            ->get('/stamp_correction_request/list?tab=wait')
            ->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee(Carbon::parse($attendanceCorrectRequest->date)->format('Y/m/d'))
            ->assertSee('テスト用の備考')
            ->assertSee(Carbon::parse($attendanceCorrectRequest->created_at)->format('Y/m/d'));

        $this->approveRequest($staff1[0], $attendance1);
        $this->approveRequest($staff2[0], $attendance2);
        $this->approveRequest($staff3[0], $attendance3);

        $this->actingAs($staff1[0])
            ->get('/stamp_correction_request/list?tab=complete')
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee(Carbon::parse($attendanceCorrectRequest->date)->format('Y/m/d'))
            ->assertSee('テスト用の備考')
            ->assertSee(Carbon::parse($attendanceCorrectRequest->created_at)->format('Y/m/d'));

        $this->actingAs($staff1[0])
            ->get("/attendance/{$attendanceCorrectRequest->attendance_id}")
            ->assertStatus(200)
            ->assertSee('2025年')
            ->assertSee('3月1日')
            ->assertSee('09:30')
            ->assertSee('18:30')
            ->assertSee('12:15')
            ->assertSee('13:15')
            ->assertSee('テスト用の備考')
            ->assertSee('承認待ちのため修正はできません。');
    }

    protected function getAttendanceData()
    {
        return [
            'year' => '2025年',
            'month_day' => '3月1日',
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'rest_start' => ['12:15'],
            'rest_end' => ['13:15'],
            'remarks' => 'テスト用の備考',
        ];
    }

    protected function attendanceRequest($staff, $attendance, $status)
    {
        $attendanceRequest = AttendanceCorrectRequest::where('user_id', $staff->id)
            ->where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        $this->assertNotNull($attendanceRequest);
        $this->assertEquals($status, $attendanceRequest->status);
    }

    protected function approveRequest($staff, $attendance)
    {
        $attendanceRequest = AttendanceCorrectRequest::where('user_id', $staff->id)
            ->where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        $attendanceRequest->update(['status' => '承認済み']);
    }
}