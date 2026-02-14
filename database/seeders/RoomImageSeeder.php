<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomImage;

class RoomImageSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
