<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
<<<<<<< HEAD
use App\Models\RoomImage;
=======
use Illuminate\Support\Facades\DB;
>>>>>>> main

class RoomImageSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
        $images = [
            [1, 'https://picsum.photos/seed/standard1/800/600'],
            [1, 'https://picsum.photos/seed/standard2/800/600'],
            [2, 'https://picsum.photos/seed/deluxe1/800/600'],
            [2, 'https://picsum.photos/seed/deluxe2/800/600'],
            [3, 'https://picsum.photos/seed/vip1/800/600'],
            [3, 'https://picsum.photos/seed/vip2/800/600'],
        ];

        foreach ($images as $img) {
            RoomImage::create([
                'room_type_id' => $img[0],
                'image_url' => $img[1],
                'created_at' => now(),
            ]);
        }
=======
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
>>>>>>> main
    }
}
