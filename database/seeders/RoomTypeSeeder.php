<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
use App\Models\RoomType;
=======
use Illuminate\Support\Facades\DB;
>>>>>>> main

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
        $roomTypes = [
            [
                'name' => 'Standard Room',
                'description' => 'Phòng tiêu chuẩn phù hợp cho 2 người.',
                'capacity' => 2,
                'bed_type' => 'Queen Bed',
                'base_price' => 500000,
            ],
            [
                'name' => 'Deluxe Room',
                'description' => 'Phòng cao cấp với view thành phố.',
                'capacity' => 3,
                'bed_type' => 'King Bed',
                'base_price' => 800000,
            ],
            [
                'name' => 'VIP Suite',
                'description' => 'Phòng VIP sang trọng với đầy đủ tiện nghi.',
                'capacity' => 4,
                'bed_type' => 'King Bed',
                'base_price' => 1500000,
            ],
        ];

        foreach ($roomTypes as $type) {
            RoomType::create([
                'hotel_id' => 1,
                'name' => $type['name'],
                'description' => $type['description'],
                'capacity' => $type['capacity'],
                'bed_type' => $type['bed_type'],
                'base_price' => $type['base_price'],
                'currency' => 'VND',
                'status' => 'active',
            ]);
        }
=======
        DB::table('room_types')->insert([
            [
                'hotel_id' => 1,
                'name' => 'Standard',
                'description' => 'Phòng tiêu chuẩn',
                'capacity' => 2,
                'bed_type' => '1 Queen Bed',
                'base_price' => 800000,
                'currency' => 'VND',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => 1,
                'name' => 'Deluxe',
                'description' => 'Phòng cao cấp view biển',
                'capacity' => 3,
                'bed_type' => '1 King Bed',
                'base_price' => 1200000,
                'currency' => 'VND',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => 2,
                'name' => 'VIP',
                'description' => 'Phòng VIP sang trọng',
                'capacity' => 4,
                'bed_type' => '2 Double Beds',
                'base_price' => 2000000,
                'currency' => 'VND',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => 3,
                'name' => 'Suite',
                'description' => 'Phòng Suite dành cho gia đình',
                'capacity' => 5,
                'bed_type' => '2 Queen Beds',
                'base_price' => 2500000,
                'currency' => 'VND',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hotel_id' => 4,
                'name' => 'Economy',
                'description' => 'Phòng giá rẻ',
                'capacity' => 2,
                'bed_type' => '2 Single Beds',
                'base_price' => 500000,
                'currency' => 'VND',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
>>>>>>> main
    }
}
