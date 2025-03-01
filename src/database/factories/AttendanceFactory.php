<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
use App\Models\Attendance;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $randomMonthOffset = rand(-1, 1);
        $date = Carbon::today()->addMonths($randomMonthOffset)->addDays(rand(0, 27));


        $clockIn = (clone $date)->addHours(rand(9, 10))->addMinutes(rand(0, 59));

        $isWorking = $this->faker->boolean(50);

        if ($isWorking) {
            $clockOut = null;
            $status = '出勤中';
        } else {
            $clockOut = (clone $clockIn)->addHours(rand(7, 9))->addMinutes(rand(0, 59));
            $status = '退勤済';
        }

        return [
            'user_id' => $this->faker->numberBetween(1, 10),
            'date' => $clockIn->toDateString(),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut ? $clockOut->format('H:i:s') : null,
            'status' => $status,
        ];
    }
}