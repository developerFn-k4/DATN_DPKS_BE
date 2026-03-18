<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserReviewController extends Controller
{

    /**
     * Tạo review
     */
    public function store(Request $request)
    {

        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'cleanliness' => 'required|integer|min:1|max:5',
            'comfort' => 'required|integer|min:1|max:5',
            'location' => 'required|integer|min:1|max:5',
            'service' => 'required|integer|min:1|max:5',
            'value' => 'required|integer|min:1|max:5',
            'wifi' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $booking = Booking::findOrFail($data['booking_id']);

        /**
         * Check user sở hữu booking
         */
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Không có quyền review'
            ], 403);
        }

        // /**
        //  * Chỉ booking completed mới review
        //  */
        // if ($booking->status !== 'completed') {
        //     return response()->json([
        //         'message' => 'Bạn chưa hoàn thành trải nghiệm phòng'
        //     ], 422);
        // }

        /**
         * Booking chỉ review 1 lần
         */
        $exists = Review::where('booking_id', $booking->id)->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Booking này đã review'
            ], 422);
        }

        $overall = (
            $data['cleanliness'] +
            $data['comfort'] +
            $data['location'] +
            $data['service'] +
            $data['value'] +
            $data['wifi']
        ) / 6;

        $review = Review::create([
            'room_id' => $booking->room_id,
            'user_id' => Auth::id(),
            'booking_id' => $booking->id,
            'cleanliness' => $data['cleanliness'],
            'comfort' => $data['comfort'],
            'location' => $data['location'],
            'service' => $data['service'],
            'value' => $data['value'],
            'wifi' => $data['wifi'],
            'overall_score' => $overall,
            'comment' => $data['comment']
        ]);

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }


    /**
     * sửa review
     */
    public function update(Request $request, Review $review)
    {

        /**
         * Check quyền
         */
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Không có quyền sửa review'
            ], 403);
        }

        $data = $request->validate([
            'cleanliness' => 'required|integer|min:1|max:5',
            'comfort' => 'required|integer|min:1|max:5',
            'location' => 'required|integer|min:1|max:5',
            'service' => 'required|integer|min:1|max:5',
            'value' => 'required|integer|min:1|max:5',
            'wifi' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        /**
         * Tính lại overall
         */
        $overall = (
            $data['cleanliness'] +
            $data['comfort'] +
            $data['location'] +
            $data['service'] +
            $data['value'] +
            $data['wifi']
        ) / 6;

        $review->update([
            'cleanliness' => $data['cleanliness'],
            'comfort' => $data['comfort'],
            'location' => $data['location'],
            'service' => $data['service'],
            'value' => $data['value'],
            'wifi' => $data['wifi'],
            'overall_score' => $overall,
            'comment' => $data['comment']
        ]);

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    /**
     * xóa review
     */
    public function destroy(Review $review)
    {

        /**
         * Check quyền
         */
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Không có quyền xóa review'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa review'
        ]);
    }
}
