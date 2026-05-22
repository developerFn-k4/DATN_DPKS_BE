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
     * Tạo review cho LOẠI PHÒNG
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

        $booking = Booking::with('bookingRooms.room.roomType')
            ->findOrFail($data['booking_id']);

        /**
         * Check user sở hữu booking
         */
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Không có quyền review'
            ], 403);
        }

        if (!in_array($booking->status, ['confirmed', 'checked_out', 'completed'])) {
            return response()->json([
                'message' => 'Chỉ được bình luận sau khi đặt phòng đã xác nhận'
            ], 422);
        }

        if (($booking->payment_status ?? 'unpaid') !== 'paid') {
            return response()->json([
                'message' => 'Đơn đặt phòng chưa thanh toán thành công'
            ], 422);
        }

        /**
         * Booking chỉ review 1 lần
         */
        $roomTypeId = optional($booking->bookingRooms->first())->room_type_id;

        if (!$roomTypeId) {
            return response()->json([
                'message' => 'Booking không có loại phòng hợp lệ để review'
            ], 422);
        }

        $exists = Review::where('booking_id', $booking->id)
            ->where('room_type_id', $roomTypeId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Booking này đã review'
            ], 422);
        }

        /**
         * Lấy room_type_id từ booking
         */
        $roomTypeId = $booking->bookingRooms->first()->room_type_id;

        /**
         * Tính overall
         */
        $overall = (
            $data['cleanliness'] +
            $data['comfort'] +
            $data['location'] +
            $data['service'] +
            $data['value'] +
            $data['wifi']
        ) / 6;

        $review = Review::create([
            'room_type_id' => $roomTypeId,
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
     * Sửa review
     */
    public function update(Request $request, Review $review)
    {

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
     * Xóa review
     */
    public function destroy(Review $review)
    {

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

    /**
     * Danh sách review theo loại phòng để FE hiển thị dưới form bình luận.
     */
    public function roomTypeReviews(Request $request, $roomTypeId)
    {
        $perPage = (int) $request->query('per_page', 10);

        $reviewsQuery = Review::with(['user:id,name,avatar'])
            ->where('room_type_id', $roomTypeId)
            ->latest();

        $reviews = $reviewsQuery->paginate($perPage);

        $summary = Review::where('room_type_id', $roomTypeId)
            ->selectRaw('COUNT(*) as total_reviews')
            ->selectRaw('AVG(overall_score) as average_overall')
            ->selectRaw('AVG(cleanliness) as average_cleanliness')
            ->selectRaw('AVG(comfort) as average_comfort')
            ->selectRaw('AVG(location) as average_location')
            ->selectRaw('AVG(service) as average_service')
            ->selectRaw('AVG(value) as average_value')
            ->selectRaw('AVG(wifi) as average_wifi')
            ->first();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_reviews' => (int) ($summary->total_reviews ?? 0),
                'average_overall' => round((float) ($summary->average_overall ?? 0), 1),
                'average_cleanliness' => round((float) ($summary->average_cleanliness ?? 0), 1),
                'average_comfort' => round((float) ($summary->average_comfort ?? 0), 1),
                'average_location' => round((float) ($summary->average_location ?? 0), 1),
                'average_service' => round((float) ($summary->average_service ?? 0), 1),
                'average_value' => round((float) ($summary->average_value ?? 0), 1),
                'average_wifi' => round((float) ($summary->average_wifi ?? 0), 1),
            ],
            'data' => $reviews,
        ]);
    }

    /**
     * Kiểm tra user hiện tại có đủ điều kiện review cho room type hay không.
     */
    public function reviewEligibility(Request $request, $roomTypeId)
    {
        $userId = $request->user()->id;

        $eligibleBookings = Booking::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['confirmed', 'checked_out', 'completed'])
            ->where('payment_status', 'paid')
            ->whereHas('bookingRooms', function ($query) use ($roomTypeId) {
                $query->where('room_type_id', $roomTypeId);
            })
            ->whereDoesntHave('reviews', function ($query) use ($roomTypeId) {
                $query->where('room_type_id', $roomTypeId);
            })
            ->select(['id', 'booking_code', 'check_in', 'check_out', 'status', 'payment_status'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'can_review' => $eligibleBookings->isNotEmpty(),
            'eligible_bookings' => $eligibleBookings,
        ]);
    }
}
