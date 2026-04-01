<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->insert([
            [
                'name' => 'Ăn sáng',
                'price' => 100000,
                'type' => 'Ẩm thực',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Đưa đón sân bay',
                'price' => 300000,
                'type' => 'Di chuyển',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Giặt ủi',
                'price' => 50000,
                'type' => 'Tiện ích',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Spa & Massage',
                'price' => 400000,
                'type' => 'Thư giãn',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Giường phụ',
                'price' => 200000,
                'type' => 'Phòng',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Thuê xe máy',
                'price' => 150000,
                'type' => 'Di chuyển',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Thuê xe ô tô',
                'price' => 800000,
                'type' => 'Di chuyển',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Dọn phòng',
                'price' => 50000,
                'type' => 'Tiện ích',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
