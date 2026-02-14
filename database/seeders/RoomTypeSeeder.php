<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
