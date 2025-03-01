<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
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
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($staff)
            ->post('/attendance/start')
            ->assertRedirect('/attendance');

        $attendance = Attendance::where('user_id', $staff->id)->first();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $staff->id,
            'clock_in' => now()->format('H:i:s'),
        ]);

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
            'clock_out' => now()->format('H:i:s'),
            'status' => '退勤済',
        ]);

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

    //前月と翌月の勤怠情報確認
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
            ->assertSee('01:00')
            ->assertSee('08:00');

        $this->actingAs($staff)
            ->get("/attendance/list?month={$previousDate->month}&year={$previousDate->year}")
            ->assertStatus(200)
            ->assertSee($previousDate->year)
            ->assertSee(sprintf('%02d', $previousDate->month))
            ->assertSee(Carbon::parse($previousAttendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($previousAttendance->clock_out)->format('H:i'))
            ->assertSee(CarbonInterval::minutes($totalRestTimePrev)->cascade()->format('%H:%I'))
            ->assertSee(CarbonInterval::minutes($workTimeExcludingRestPrev)->cascade()->format('%H:%I'));

        $this->actingAs($staff)
            ->get("/attendance/list?month={$nextDate->month}&year={$nextDate->year}")
            ->assertStatus(200)
            ->assertSee($nextDate->year)
            ->assertSee(sprintf('%02d', $nextDate->month))
            ->assertSee(Carbon::parse($nextAttendance->clock_in)->format('H:i'))
            ->assertSee(Carbon::parse($nextAttendance->clock_out)->format('H:i'))
            ->assertSee(CarbonInterval::minutes($totalRestTimeNext)->cascade()->format('%H:%I'))
            ->assertSee(CarbonInterval::minutes($workTimeExcludingRestNext)->cascade()->format('%H:%I'));
    }

    //勤怠詳細画面遷移
    public function test_screen_transition_attendance_detail()
    {
        [$staff, $attendance, $formattedDate] = $this->createStaffAttendanceList();

        $this->actingAs($staff)
            ->get("/attendance/{$attendance->id}")
            ->assertStatus(200);
    }

    //勤怠詳細画面情報確認
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

    public function test_attendance_update_work_time_error()
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

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
}