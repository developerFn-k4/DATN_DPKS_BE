<?php

<<<<<<< HEAD
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
=======

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
>>>>>>> main

class HotelSeeder extends Seeder
{
    public function run(): void
    {
<<<<<<< HEAD
        Hotel::create([
            'name' => 'VietStay Hotel',
            'address' => '123 Trần Duy Hưng, Hà Nội, Việt Nam',
            'phone' => '0901234567',
            'email' => 'contact@vietstay.vn',
            'description' => 'VietStay là khách sạn hiện đại, tiện nghi, nằm ngay trung tâm thành phố.',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'status' => 'active',
=======
        DB::table('hotels')->insert([
            [
                'name' => 'Sunrise Hotel',
                'address' => '123 Trần Phú, Nha Trang',
                'phone' => '0909123456',
                'email' => 'admin@sunrisehotel.com',
                'description' => 'Khách sạn 4 sao gần biển',
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Moonlight Hotel',
                'address' => '45 Nguyễn Huệ, Đà Nẵng',
                'phone' => '0988777666',
                'email' => 'contact@moonlight.com',
                'description' => 'Khách sạn trung tâm thành phố',
                'check_in_time' => '13:00:00',
                'check_out_time' => '11:00:00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Golden Sea Hotel',
                'address' => '88 Võ Nguyên Giáp, Đà Nẵng',
                'phone' => '0911222333',
                'email' => 'info@goldensea.com',
                'description' => 'Khách sạn view biển cao cấp',
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Central Park Hotel',
                'address' => '12 Lê Lợi, Quận 1, TP.HCM',
                'phone' => '0933444555',
                'email' => 'support@centralpark.com',
                'description' => 'Khách sạn dành cho doanh nhân',
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mountain View Hotel',
                'address' => '5 Fansipan, Sa Pa, Lào Cai',
                'phone' => '0977666888',
                'email' => 'hello@mountainview.com',
                'description' => 'Khách sạn nghỉ dưỡng trên núi',
                'check_in_time' => '15:00:00',
                'check_out_time' => '11:00:00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
>>>>>>> main
        ]);
    }
}
