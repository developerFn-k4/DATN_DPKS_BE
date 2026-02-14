<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            // Standard Room (room_type_id = 1)
            ['room_type_id' => 1, 'room_number' => '101', 'floor' => '1'],
            ['room_type_id' => 1, 'room_number' => '102', 'floor' => '1'],

            // Deluxe Room (room_type_id = 2)
            ['room_type_id' => 2, 'room_number' => '201', 'floor' => '2'],
            ['room_type_id' => 2, 'room_number' => '202', 'floor' => '2'],

            // VIP Suite (room_type_id = 3)
            ['room_type_id' => 3, 'room_number' => '301', 'floor' => '3'],
            ['room_type_id' => 3, 'room_number' => '302', 'floor' => '3'],
        ];

        foreach ($rooms as $room) {
            Room::create([
                'room_type_id' => $room['room_type_id'],
                'room_number' => $room['room_number'],
                'floor' => $room['floor'],
                'status' => 'available',
            ]);
        }
    }
}
