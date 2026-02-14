<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        Hotel::create([
            'name' => 'VietStay Hotel',
            'address' => '123 Trần Duy Hưng, Hà Nội, Việt Nam',
            'phone' => '0901234567',
            'email' => 'contact@vietstay.vn',
            'description' => 'VietStay là khách sạn hiện đại, tiện nghi, nằm ngay trung tâm thành phố.',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'status' => 'active',
        ]);
    }
}
