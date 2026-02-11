<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('room_images')->insert([
            [
                'room_type_id' => 1,
                'image_url' => 'rooms/standard_1.jpg',
                'created_at' => now(),
            ],
            [
                'room_type_id' => 1,
                'image_url' => 'rooms/standard_2.jpg',
                'created_at' => now(),
            ],
            [
                'room_type_id' => 2,
                'image_url' => 'rooms/deluxe_1.jpg',
                'created_at' => now(),
            ],
            [
                'room_type_id' => 3,
                'image_url' => 'rooms/vip_1.jpg',
                'created_at' => now(),
            ],
            [
                'room_type_id' => 4,
                'image_url' => 'rooms/suite_1.jpg',
                'created_at' => now(),
            ],
        ]);
    }
}
