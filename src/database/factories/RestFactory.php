<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;

class RestFactory extends Factory
{
    protected $model = Rest::class;

    public function definition()
    {
        $attendance = Attendance::inRandomOrder()->first();

        if (!$attendance) {
            return [
                'attendance_id' => 1,
                'rest_start' => '12:00:00',
                'rest_end' => '12:15:00',
            ];
        }

        $existingRests = Rest::where('attendance_id', $attendance->id)->get();

        $hasLunchBreak = $existingRests->contains(function ($rest) {
            return $rest->rest_start === '12:00:00' && $rest->rest_end === '13:00:00';
        });

        if (!$hasLunchBreak) {
            return [
                'attendance_id' => $attendance->id,
                'rest_start' => '12:00:00',
                'rest_end' => '13:00:00',
            ];
        }

        $morningRestExists = $existingRests->contains(function ($rest) {
            return $rest->rest_start >= '09:00:00' && $rest->rest_start < '12:00:00';
        });

        $afternoonRestExists = $existingRests->contains(function ($rest) {
            return $rest->rest_start >= '13:00:00' && $rest->rest_start < '18:00:00';
        });

        if (!$morningRestExists) {
            $restStart = Carbon::today()->addHours(rand(9, 11))->addMinutes(rand(0, 59));
            return [
                'attendance_id' => $attendance->id,
                'rest_start' => $restStart->format('H:i:s'),
                'rest_end' => $restStart->copy()->addMinutes(15)->format('H:i:s'),
            ];
        }

        if (!$afternoonRestExists) {
            $restStart = Carbon::today()->addHours(rand(13, 17))->addMinutes(rand(0, 59));
            return [
                'attendance_id' => $attendance->id,
                'rest_start' => $restStart->format('H:i:s'),
                'rest_end' => $restStart->copy()->addMinutes(15)->format('H:i:s'),
            ];
        }

        return [
            'attendance_id' => $attendance->id,
            'rest_start' => '14:00:00',
            'rest_end' => '14:15:00',
        ];
    }
}
