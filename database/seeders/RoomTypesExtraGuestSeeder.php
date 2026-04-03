<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomTypesExtraGuestSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $rooms = [
            [
                'id' => 11,
                'extra_adult_price' => 200000,
                'extra_child_price' => 100000,
                'max_occupancy' => 4,
                'updated_at' => $now,
            ],
            [
                'id' => 12,
                'extra_adult_price' => 250000,
                'extra_child_price' => 120000,
                'max_occupancy' => 4,
                'updated_at' => $now,
            ],
            [
                'id' => 13,
                'extra_adult_price' => 250000,
                'extra_child_price' => 120000,
                'max_occupancy' => 4,
                'updated_at' => $now,
            ],
            [
                'id' => 14,
                'extra_adult_price' => 300000,
                'extra_child_price' => 150000,
                'max_occupancy' => 5,
                'updated_at' => $now,
            ],
            [
                'id' => 15,
                'extra_adult_price' => 350000,
                'extra_child_price' => 150000,
                'max_occupancy' => 6,
                'updated_at' => $now,
            ],
            [
                'id' => 16,
                'extra_adult_price' => 400000,
                'extra_child_price' => 200000,
                'max_occupancy' => 6,
                'updated_at' => $now,
            ],
            [
                'id' => 17,
                'extra_adult_price' => 500000,
                'extra_child_price' => 250000,
                'max_occupancy' => 8,
                'updated_at' => $now,
            ],
            [
                'id' => 19,
                'extra_adult_price' => 300000,
                'extra_child_price' => 150000,
                'max_occupancy' => 6,
                'updated_at' => $now,
            ],
        ];

        foreach ($rooms as $room) {
            DB::table('room_types')
                ->where('id', $room['id'])
                ->update([
                    'extra_adult_price' => $room['extra_adult_price'],
                    'extra_child_price' => $room['extra_child_price'],
                    'max_occupancy' => $room['max_occupancy'],
                    'updated_at' => $room['updated_at'],
                ]);
        }
    }
}
