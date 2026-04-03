<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserBookingController extends Controller
{

    /**
     * =====================================================
     * DANH SÁCH PHÒNG TRỐNG
     * =====================================================
     */
    public function availableRooms(Request $request)
    {

        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children_ages' => 'nullable|array'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        /**
         * COUNT PEOPLE
         */
        $adults = $request->adults;

        $childrenAges = $request->children_ages ?? [];

        $childrenAsAdults = collect($childrenAges)
            ->filter(fn($age) => $age >= 10)
            ->count();

        $totalGuests = $adults + $childrenAsAdults;

        /**
         * FIND ROOM TYPES
         */
        $roomTypes = RoomType::where('capacity', '>=', $totalGuests)
            ->with(['rooms', 'images'])
            ->get();

        $availableRooms = [];

        foreach ($roomTypes as $roomType) {

            /**
             * CHECK BOOKED ROOMS
             */
            $bookedRooms = BookingRoom::whereHas('booking', function ($q) use ($checkIn, $checkOut) {

                $q->where('status', 'confirmed')
                    ->where(function ($query) use ($checkIn, $checkOut) {

                        $query->whereBetween('check_in', [$checkIn, $checkOut])
                            ->orWhereBetween('check_out', [$checkIn, $checkOut])
                            ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                                $q2->where('check_in', '<=', $checkIn)
                                    ->where('check_out', '>=', $checkOut);
                            });
                    });
            })
                ->where('room_type_id', $roomType->id)
                ->sum('quantity');


            /**
             * TOTAL ROOMS
             */
            $totalRooms = $roomType->rooms->count();

            $remaining = $totalRooms - $bookedRooms;

            if ($remaining > 0) {

                $availableRooms[] = [

                    'room_type_id' => $roomType->id,
                    'name' => $roomType->name,
                    'capacity' => $roomType->capacity,
                    'price' => $roomType->price,
                    'available_rooms' => $remaining,
                    'images' => $roomType->images
                ];
            }
        }

        return response()->json([
            'guests' => $totalGuests,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'rooms' => $availableRooms
        ]);
    }
    /**
     * CALCULATE PRICE
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array',
            'rooms.*.room_type_id' => 'required|exists:room_types,id',
            'rooms.*.quantity' => 'required|integer|min:1',
            'services' => 'nullable|array'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        $nights = $checkIn->diffInDays($checkOut);

        $total = 0;
        $roomDetails = [];
        $serviceDetails = [];

        /**
         * ROOM PRICE
         */
        foreach ($request->rooms as $room) {

            $roomType = RoomType::findOrFail($room['room_type_id']);

            $price = $roomType->price * $room['quantity'] * $nights;

            $total += $price;

            $roomDetails[] = [
                'room_type_id' => $roomType->id,
                'name' => $roomType->name,
                'quantity' => $room['quantity'],
                'price' => $price
            ];
        }

        /**
         * SERVICE PRICE
         */
        foreach ($request->services ?? [] as $service) {

            $serviceModel = Service::findOrFail($service['service_id']);

            $price = $serviceModel->price * $service['quantity'];

            $total += $price;

            $serviceDetails[] = [
                'service_id' => $serviceModel->id,
                'name' => $serviceModel->name,
                'quantity' => $service['quantity'],
                'price' => $price
            ];
        }

        return response()->json([
            'nights' => $nights,
            'rooms' => $roomDetails,
            'services' => $serviceDetails,
            'total_price' => $total
        ]);
    }



























    
    /**
     * =====================================================
     * CANCEL BOOKING
     * =====================================================
     */
    public function cancel($id)
    {

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json([
                'message' => 'Booking không thể hủy'
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Đã hủy booking'
        ]);
    }

    /**
     * =====================================================
     * BOOKING CỦA USER
     * =====================================================
     */
    public function myBookings()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['room', 'room.roomType'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    /**
     * =====================================================
     * BOOKINGS CHƯA THANH TOÁN CỦA USER
     * =====================================================
     */
    public function unpaidBookings(Request $request)
    {
        $userId = $request->user()->id;

        // DEBUG: xem tất cả bookings của user này (xoá sau khi xác định vấn đề)
        $allBookings = Booking::where('user_id', $userId)->get(['id', 'status', 'deleted_at']);

        $bookings = Booking::where('user_id', $userId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['room', 'room.roomType'])
            ->latest()
            ->get();

        return response()->json([
            'success'      => true,
            'data'         => $bookings,
            '_debug'       => [
                'logged_in_user_id' => $userId,
                'all_bookings'      => $allBookings,
            ],
        ]);
    }
}
