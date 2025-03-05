<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;

class RestTest extends TestCase
{
    use RefreshDatabase;

    //休憩開始処理
    public function test_rest_start()
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
            ->assertSee('休憩入');

        $this->actingAs($staff)
            ->post('/attendance/rest-start')
            ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start' => $latestRest->rest_start,
        ]);

        $this->actingAs($staff)
            ->get('/attendance')
            ->assertSee('休憩中');
    }

    //複数回の休憩入ボタン表示確認
    public function test_many_rest_start()
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
            ->get('/attendance')
            ->assertSee('休憩入');
    }

    //出勤時刻を管理画面で確認と休憩戻時のステータス確認
    public function test_admin_confirm_staff_rest_end()
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
            ->post('/attendance/rest-start')
            ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start' => $latestRest->rest_start,
        ]);

        $this->actingAs($staff)
            ->get('/attendance')
            ->assertSee('休憩戻');


        $this->actingAs($staff)
            ->post('/attendance/rest-end')
            ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_end' => $latestRest->rest_end,
        ]);

        $this->actingAs($staff)
            ->get('/attendance')
            ->assertSee('出勤中');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword'),
        ]);

        $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}")
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
    }

    //複数回の休憩戻ボタン表示確認
    public function test_many_rest_end()
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
        ->post('/attendance/rest-start')
        ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_start' => $latestRest->rest_start,
        ]);

        $this->actingAs($staff)
        ->get('/attendance')
        ->assertSee('休憩戻');

        $this->actingAs($staff)
        ->post('/attendance/rest-end')
        ->assertRedirect('/attendance');

        $latestRest = Rest::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'rest_end' => $latestRest->rest_end,
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
        ->get('/attendance')
        ->assertSee('休憩戻');
    }

    //休憩時刻を管理画面で確認
    public function test_admin_confirm_staff_rest_time()
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

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword'),
        ]);

        $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}")
            ->assertStatus(200)
            ->assertSee(Carbon::parse($attendance->date)->format('Y年'))
            ->assertSee(Carbon::parse($attendance->date)->format('n月j日'))
            ->assertSee(Carbon::parse($latestRest->rest_start)->format('H:i'))
            ->assertSee(Carbon::parse($latestRest->rest_end)->format('H:i'));
    }
}
