<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [

            // Standard Room (11)
            ['room_number' => '101', 'room_type_id' => 11, 'floor' => 1, 'price' => 500000],
            ['room_number' => '102', 'room_type_id' => 11, 'floor' => 1, 'price' => 500000],
            ['room_number' => '103', 'room_type_id' => 11, 'floor' => 1, 'price' => 500000],
            ['room_number' => '104', 'room_type_id' => 11, 'floor' => 1, 'price' => 500000],

            // Superior Room (12)
            ['room_number' => '201', 'room_type_id' => 12, 'floor' => 2, 'price' => 650000],
            ['room_number' => '202', 'room_type_id' => 12, 'floor' => 2, 'price' => 650000],
            ['room_number' => '203', 'room_type_id' => 12, 'floor' => 2, 'price' => 650000],
            ['room_number' => '204', 'room_type_id' => 12, 'floor' => 2, 'price' => 650000],

            // Twin Room (13)
            ['room_number' => '301', 'room_type_id' => 13, 'floor' => 3, 'price' => 600000],
            ['room_number' => '302', 'room_type_id' => 13, 'floor' => 3, 'price' => 600000],
            ['room_number' => '303', 'room_type_id' => 13, 'floor' => 3, 'price' => 600000],

            // Deluxe Room (14)
            ['room_number' => '401', 'room_type_id' => 14, 'floor' => 4, 'price' => 900000],
            ['room_number' => '402', 'room_type_id' => 14, 'floor' => 4, 'price' => 900000],
            ['room_number' => '403', 'room_type_id' => 14, 'floor' => 4, 'price' => 900000],

            // Family Room (15)
            ['room_number' => '501', 'room_type_id' => 15, 'floor' => 5, 'price' => 1200000],
            ['room_number' => '502', 'room_type_id' => 15, 'floor' => 5, 'price' => 1200000],
            ['room_number' => '503', 'room_type_id' => 15, 'floor' => 5, 'price' => 1200000],

            // Suite Room (16)
            ['room_number' => '601', 'room_type_id' => 16, 'floor' => 6, 'price' => 1800000],
            ['room_number' => '602', 'room_type_id' => 16, 'floor' => 6, 'price' => 1800000],

            // Presidential Suite (17)
            ['room_number' => '701', 'room_type_id' => 17, 'floor' => 7, 'price' => 5000000],
            ['room_number' => '702', 'room_type_id' => 17, 'floor' => 7, 'price' => 5000000],

        ];

        foreach ($rooms as $room) {
            Room::create([
                'room_number' => $room['room_number'],
                'room_type_id' => $room['room_type_id'],
                'floor' => $room['floor'],
                'price' => $room['price'],
                'status' => 'available',
                'note' => null
            ]);
        }
    }
}
