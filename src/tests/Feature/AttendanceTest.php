<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    //勤怠ページに現在の日付と時刻が正しく表示される
    public function test_attendance_displays_current_datetime()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $now = Carbon::now()->translatedFormat('Y年n月j日 (D)');
        $time = Carbon::now()->format('H:i');

        $response->assertSee($now);

        $response->assertSee($time);
    }

    //ステータス確認
    public function test_attendance_status()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200)
                ->assertSee('勤務外');

        $this->post('/attendance/start');

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');

        $this->post('/attendance/rest-start');

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');

        $this->post('/attendance/rest-end');

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');

        $this->post('/attendance/end');

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    //出勤処理
    public function test_clock_in()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200)
            ->assertSee('勤務外');

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('出勤');

        $response = $this->actingAs($user)->post('/attendance/start');

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    //退勤済の場合は出勤ボタン非表示
    public function test_clock_out_button_is_not_displayed()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(), // ここを追加
            'clock_out' => now()->format('H:i:s'),
            'status' => '退勤済',
        ]);

        $this->actingAs($user)
            ->withSession([
                'attendance_id' => $attendance->id,
                'status' => '退勤済'
            ]);

        $status = session('status');

        $response = $this->get('/attendance');

        $response->assertSee($status);

        $response->assertDontSee('出勤');
    }

    //出勤時刻を管理画面で確認
    public function test_admin_confirm_staff_clock_in()
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
        $this->assertTrue(
            Carbon::parse($attendance->clock_in)->between(now()->subMinutes(6), now()),
            "clock_in is not within the expected range: {$attendance->clock_in}"
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword'),
        ]);

        $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}")
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendance->date)->format('Y年') . '"', false)
            ->assertSee(Carbon::parse($attendance->date)->format('n月j日') . '"', false)
            ->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
    }

    //退勤処理
    public function test_clock_out()
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
        $this->assertTrue(
            Carbon::parse($attendance->clock_in)->between(now()->subMinutes(6), now()),
            "clock_in is not within the expected range: {$attendance->clock_in}"
        );

        $this->actingAs($staff)
            ->get('/attendance')
            ->assertSee('退勤');

        $this->actingAs($staff)
            ->post('/attendance/end')
            ->assertRedirect('/attendance');

        $attendance = Attendance::where('user_id', $staff->id)->first();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $staff->id,
            'clock_out' => now()->format('H:i:s'),
        ]);

        $this->actingAs($staff)
            ->get('/attendance')
            ->assertSee('退勤済');
    }

    //退勤時刻を管理画面で確認
    public function test_admin_confirm_staff_clock_out()
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
        $this->assertTrue(
            Carbon::parse($attendance->clock_in)->between(now()->subMinutes(6), now()),
            "clock_in is not within the expected range: {$attendance->clock_in}"
        );

        $this->actingAs($staff)
            ->post('/attendance/end')
            ->assertRedirect('/attendance');

        $attendance = Attendance::where('user_id', $staff->id)->first();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $staff->id,
            'clock_out' => now()->format('H:i:s'),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword'),
        ]);

        $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}")
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendance->date)->format('Y年') . '"', false)
            ->assertSee(Carbon::parse($attendance->date)->format('n月j日') . '"', false)
            ->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
    }
}
