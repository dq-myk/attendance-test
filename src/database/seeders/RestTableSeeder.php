<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rest;
use App\Models\Attendance;

class RestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::all();

        $attendances->each(function ($attendance) {
            Rest::factory()->count(rand(1, 2))->create([
                'attendance_id' => $attendance->id,
            ]);
        });
    }
}