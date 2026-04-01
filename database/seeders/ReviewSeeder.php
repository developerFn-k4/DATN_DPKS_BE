<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Booking;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::take(10)->get();

        foreach ($bookings as $booking) {

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
                'room_id' => $booking->room_id,
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'cleanliness' => $cleanliness,
                'comfort' => $comfort,
                'location' => $location,
                'service' => $service,
                'value' => $value,
                'wifi' => $wifi,
                'overall_score' => $overall,
                'comment' => fake()->sentence()
            ]);
        }
    }
}
