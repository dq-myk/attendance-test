<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $restStart = $this->faker->dateTimeBetween('09:00:00', '15:00:00');
        $restEnd = clone $restStart;
        $restEnd->modify('+15 minutes');

        return [
            'attendance_id' => $this->faker->numberBetween(1, 10),
            'rest_start' => $restStart->format('H:i'),
            'rest_end' => $restEnd->format('H:i'),
        ];
    }
}