<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;

class AttendanceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereBetween('id', [1, 10])->get();

        $users->each(function ($user) {
            Attendance::factory()->count(2)->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
