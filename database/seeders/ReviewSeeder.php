<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\Booking;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {

        $roomTypes = [11, 12, 13, 14, 15, 16, 17, 19];

        $users = User::pluck('id')->toArray();
        $bookings = Booking::pluck('id')->toArray();

        $comments = [
            "Phòng rất sạch sẽ và thoải mái",
            "Trải nghiệm tuyệt vời, sẽ quay lại",
            "Nhân viên thân thiện",
            "Vị trí khách sạn rất thuận tiện",
            "Wifi ổn định, làm việc rất tốt",
            "Phòng đẹp hơn trong hình",
            "Giá cả hợp lý",
            "Không gian yên tĩnh",
            "Giường ngủ rất êm",
            "Dịch vụ tốt"
        ];

        for ($i = 0; $i < 100; $i++) {

            $cleanliness = rand(3, 5);
            $comfort = rand(3, 5);
            $location = rand(3, 5);
            $service = rand(3, 5);
            $value = rand(3, 5);
            $wifi = rand(3, 5);

            $overall = (
                $cleanliness +
                $comfort +
                $location +
                $service +
                $value +
                $wifi
            ) / 6;

            Review::create([
                'room_type_id' => $roomTypes[array_rand($roomTypes)],
                'user_id' => $users[array_rand($users)],
                'booking_id' => $bookings[array_rand($bookings)],

                'cleanliness' => $cleanliness,
                'comfort' => $comfort,
                'location' => $location,
                'service' => $service,
                'value' => $value,
                'wifi' => $wifi,

                'overall_score' => round($overall, 1),

                'comment' => $comments[array_rand($comments)],

                'created_at' => Carbon::now()->subDays(rand(1, 120)),
                'updated_at' => now()
            ]);
        }
    }
}
