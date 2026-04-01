<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateRoomTypeCapacitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('room_types')->where('id', 11)->update([
            'max_adults' => 2,
            'max_children' => 1
        ]);

        DB::table('room_types')->where('id', 12)->update([
            'max_adults' => 2,
            'max_children' => 1
        ]);

        DB::table('room_types')->where('id', 13)->update([
            'max_adults' => 2,
            'max_children' => 1
        ]);

        DB::table('room_types')->where('id', 14)->update([
            'max_adults' => 2,
            'max_children' => 2
        ]);

        DB::table('room_types')->where('id', 15)->update([
            'max_adults' => 3,
            'max_children' => 2
        ]);

        DB::table('room_types')->where('id', 16)->update([
            'max_adults' => 3,
            'max_children' => 2
        ]);

        DB::table('room_types')->where('id', 17)->update([
            'max_adults' => 6,
            'max_children' => 4
        ]);

        DB::table('room_types')->where('id', 19)->update([
            'max_adults' => 3,
            'max_children' => 2
        ]);
    }
}
