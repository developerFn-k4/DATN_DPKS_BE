<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{

    /**
     * ======================================================
     * DANH SÁCH REVIEW
     * ======================================================
     */
    public function index(Request $request)
    {

        $query = Review::with([
            'room:id,room_number',
            'user:id,name'
        ]);

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                // tìm theo số phòng
                $q->whereHas('room', function ($room) use ($request) {
                    $room->where('room_number', 'like', '%' . $request->search . '%');
                })

                    // tìm theo tên khách
                    ->orWhereHas('user', function ($user) use ($request) {
                        $user->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $reviews = $query->latest()->paginate(10);

        $data = $reviews->getCollection()->map(function ($review) {

            return [
                'id' => $review->id,

                'room' => $review->room->room_number ?? null,

                'customer' => $review->user->name ?? null,

                'rating' => $review->overall_score,

                'comment' => $review->comment,

                'date' => $review->created_at->format('d-m-Y')
            ];
        });

        return response()->json([
            'success' => true,
            'total_reviews' => $reviews->total(),
            'data' => $data,
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total()
            ]
        ]);
    }


    /**
     * ======================================================
     * CHI TIẾT REVIEW
     * ======================================================
     */
    public function show($id)
    {

        $review = Review::with([
            'room:id,room_number',
            'user:id,name',
            'booking:id,check_in,check_out'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'room' => $review->room->name ?? null,
                'customer' => $review->user->name ?? null,

                'ratings' => [
                    'cleanliness' => $review->cleanliness,
                    'comfort' => $review->comfort,
                    'location' => $review->location,
                    'service' => $review->service,
                    'value' => $review->value,
                    'wifi' => $review->wifi,
                    'overall' => $review->overall_score
                ],

                'comment' => $review->comment,

                'booking' => [
                    'check_in' => $review->booking->check_in ?? null,
                    'check_out' => $review->booking->check_out ?? null
                ],

                'date' => $review->created_at
            ]
        ]);
    }


    /**
     * ======================================================
     * XÓA REVIEW
     * ======================================================
     */
    public function destroy($id)
    {

        $review = Review::findOrFail($id);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa review'
        ]);
    }
}
