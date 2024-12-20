<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rest;

class RestTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Rest::factory()->count(15)->create();
    }
}
