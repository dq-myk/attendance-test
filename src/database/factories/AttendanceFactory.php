<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $clockInTime = $this->faker->time('H:i');
        $clockOutTime = $this->faker->time('H:i', strtotime("+9 hour", strtotime($clockInTime)));
        return [
            'user_id' => $this->faker->numberBetween(1, 10),
            'date' => $this->faker->date('Y-m-d'),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
            'status' => $this->faker->randomElement(['勤務外', '出勤中', '休憩中', '退勤済']),
        ];
    }
}
