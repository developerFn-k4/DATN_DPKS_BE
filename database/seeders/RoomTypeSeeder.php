<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $amenities = json_encode([
            "Bếp riêng",
            "Phòng tắm riêng trong phòng",
            "Ban công",
            "Nhìn ra biển",
            "Nhìn ra thành phố",
            "Điều hòa không khí",
            "TV màn hình phẳng",
            "Sân hiên",
            "WiFi miễn phí"
        ]);

        $roomTypes = [
            [
                'name' => 'Standard Room',
                'capacity' => 2,
                'bed_type' => '1 Giường đôi',
                'area' => 22,
                'base_price' => 500000,
            ],
            [
                'name' => 'Superior Room',
                'capacity' => 2,
                'bed_type' => '1 Giường Queen',
                'area' => 28,
                'base_price' => 650000,
            ],
            [
                'name' => 'Twin Room',
                'capacity' => 2,
                'bed_type' => '2 Giường đơn',
                'area' => 25,
                'base_price' => 600000,
            ],
            [
                'name' => 'Deluxe Room',
                'capacity' => 3,
                'bed_type' => '1 Giường King + 1 Giường đơn',
                'area' => 35,
                'base_price' => 900000,
            ],
            [
                'name' => 'Family Room',
                'capacity' => 4,
                'bed_type' => '2 Giường đôi',
                'area' => 45,
                'base_price' => 1200000,
            ],
            [
                'name' => 'Suite Room',
                'capacity' => 4,
                'bed_type' => '1 Giường King + Phòng khách',
                'area' => 60,
                'base_price' => 1800000,
            ],
            [
                'name' => 'Presidential Suite',
                'capacity' => 6,
                'bed_type' => '2 Giường King',
                'area' => 120,
                'base_price' => 5000000,
            ],
        ];

        foreach ($roomTypes as $roomType) {
            RoomType::create([
                'hotel_id' => 1,
                'name' => $roomType['name'],
                'capacity' => $roomType['capacity'],
                'bed_type' => $roomType['bed_type'],
                'area' => $roomType['area'],
                'amenities' => $amenities,
                'base_price' => $roomType['base_price'],
                'currency' => 'VND',
                'status' => 'active'
            ]);
        }
    }
}
