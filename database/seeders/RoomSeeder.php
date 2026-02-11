<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::insert([
            [
                'room_number' => '101',
                'room_type_id' => 1,
                'floor' => 1,
                'status' => 'available',
            ],
            [
                'room_number' => '102',
                'room_type_id' => 2,
                'floor' => 1,
                'status' => 'maintenance',
            ],
            [
                'room_number' => '201',
                'room_type_id' => 3,
                'floor' => 2,
                'status' => 'available',
            ],
            [
                'room_number' => '202',
                'room_type_id' => 4,
                'floor' => 2,
                'status' => 'maintenance',
            ],
            [
                'room_number' => '301',
                'room_type_id' => 5,
                'floor' => 3,
                'status' => 'available',
            ],
        ]);
    }
}
