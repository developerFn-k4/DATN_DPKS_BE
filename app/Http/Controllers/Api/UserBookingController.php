<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Service;
use App\Models\BookingService;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserBookingController extends Controller
{

    /*
    |-----------------------------------------------------------
    | REALTIME PRICE
    |-----------------------------------------------------------
    */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array',
            'services' => 'nullable|array'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        $total = 0;

        $roomDetails = [];
        $serviceDetails = [];

        /*
    |-----------------------------------------------------------
    | TÍNH TIỀN PHÒNG
    |-----------------------------------------------------------
    */
        foreach ($request->rooms as $room) {

            $roomType = RoomType::findOrFail($room['room_type_id']);

            $quantity = $room['quantity'] ?? 1;

            $price = $roomType->base_price * $quantity * $nights;

            $roomDetails[] = [
                'room_type' => $roomType->name,
                'quantity' => $quantity,
                'price_per_night' => $roomType->base_price,
                'nights' => $nights,
                'total' => $price
            ];

            $total += $price;
        }

        /*
    |-----------------------------------------------------------
    | TÍNH TIỀN DỊCH VỤ
    |-----------------------------------------------------------
    */
        if ($request->services) {

            foreach ($request->services as $service) {

                $serviceModel = Service::findOrFail($service['service_id']);

                $quantity = $service['quantity'] ?? 1;

                $price = $serviceModel->price * $quantity;

                $serviceDetails[] = [
                    'service_name' => $serviceModel->name,
                    'quantity' => $quantity,
                    'price' => $serviceModel->price,
                    'total' => $price
                ];

                $total += $price;
            }
        }

        return response()->json([
            'nights' => $nights,
            'rooms' => $roomDetails,
            'services' => $serviceDetails,
            'total' => $total
        ]);
    }


    /*
    |-----------------------------------------------------------
    | CREATE BOOKING
    |-----------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array',
            'services' => 'nullable|array'
        ]);

        DB::beginTransaction();

        try {

            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            $nights = $checkIn->diffInDays($checkOut);

            $totalGuests = collect($request->rooms)
                ->sum(fn($room) => ($room['adults'] ?? 0) + ($room['children'] ?? 0));

            /*
        |------------------------------------------------
        | CREATE BOOKING
        |------------------------------------------------
        */

            $booking = Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(6)),
                'user_id' => Auth::id(),
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'guests' => $totalGuests,
                'status' => 'confirmed' // xác nhận luôn
            ]);

            $subTotal = 0;

            /*
        |------------------------------------------------
        | PROCESS ROOM
        |------------------------------------------------
        */

            foreach ($request->rooms as $roomData) {

                $roomType = RoomType::findOrFail($roomData['room_type_id']);
                $quantity = $roomData['quantity'] ?? 1;

                $availableRooms = Room::where('room_type_id', $roomType->id)
                    ->where('status', 'available')
                    ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn)
                            ->whereIn('status', ['pending', 'confirmed']);
                    })
                    ->lockForUpdate()
                    ->take($quantity)
                    ->get();

                if ($availableRooms->count() < $quantity) {
                    throw new \Exception("Room {$roomType->name} not enough.");
                }

                foreach ($availableRooms as $room) {

                    $price = $roomType->base_price * $nights;

                    BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $room->id,
                        'room_type_id' => $roomType->id,
                        'quantity' => 1,
                        'price' => $price
                    ]);

                    $subTotal += $price;

                    // chuyển trạng thái phòng
                    $room->update([
                        'status' => 'booked'
                    ]);
                }
            }

            /*
        |------------------------------------------------
        | SERVICES
        |------------------------------------------------
        */

            if ($request->has('services')) {

                foreach ($request->services as $sData) {

                    $service = Service::findOrFail($sData['service_id']);

                    $qty = $sData['quantity'] ?? 1;

                    $price = $service->price * $qty;

                    BookingService::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'quantity' => $qty,
                        'price' => $price
                    ]);

                    $subTotal += $price;
                }
            }

            /*
        |------------------------------------------------
        | FINANCE
        |------------------------------------------------
        */

            $tax = $subTotal * 0.05;

            $total = $subTotal + $tax;

            $booking->update([
                'total_price' => $total
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Đặt phòng thành công',
                'booking_code' => $booking->booking_code,
                'total_price' => $total
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |-----------------------------------------------------------
    | CANCEL BOOKING
    |-----------------------------------------------------------
    */

    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status == 'confirmed') {

            return response()->json([
                'message' => 'Cannot cancel confirmed booking'
            ], 400);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Booking cancelled'
        ]);
    }

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













     /*
    |-----------------------------------------------------------
    | CREATE BOOKING đã chuyển sang AdminBookingController vì có liên quan đến payment
    |-----------------------------------------------------------
    */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email',
    //         'phone' => 'required',
    //         'check_in' => 'required|date',
    //         'check_out' => 'required|date|after:check_in',
    //         'rooms' => 'required|array',
    //         'services' => 'nullable|array'
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $checkIn = Carbon::parse($request->check_in);
    //         $checkOut = Carbon::parse($request->check_out);

    //         $nights = $checkIn->diffInDays($checkOut);

    //         $totalGuests = collect($request->rooms)
    //             ->sum(fn($room) => ($room['adults'] ?? 0) + ($room['children'] ?? 0));

    //         /*
    //         |------------------------------------------------
    //         | CREATE BOOKING
    //         |------------------------------------------------
    //         */

    //         $booking = Booking::create([
    //             'booking_code' => 'BK-' . strtoupper(Str::random(6)),
    //             'user_id' => Auth::id(),
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'phone' => $request->phone,
    //             'check_in' => $checkIn,
    //             'check_out' => $checkOut,
    //             'nights' => $nights,
    //             'guests' => $totalGuests,
    //             'status' => 'pending',
    //             'expired_at' => now()->addMinutes(2)
    //         ]);

    //         $subTotal = 0;
    //         $roomInfo = [];

    //         /*
    //         |------------------------------------------------
    //         | PROCESS ROOM
    //         |------------------------------------------------
    //         */

    //         foreach ($request->rooms as $roomData) {

    //             $roomType = RoomType::findOrFail($roomData['room_type_id']);
    //             $quantity = $roomData['quantity'] ?? 1;

    //             $availableRooms = Room::where('room_type_id', $roomType->id)
    //                 ->where('status', 'available')
    //                 ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
    //                     $q->where('check_in', '<', $checkOut)
    //                         ->where('check_out', '>', $checkIn)
    //                         ->whereIn('status', ['pending', 'confirmed']);
    //                 })
    //                 ->lockForUpdate()
    //                 ->take($quantity)
    //                 ->get();

    //             if ($availableRooms->count() < $quantity) {
    //                 throw new \Exception("Room {$roomType->name} not enough.");
    //             }

    //             foreach ($availableRooms as $room) {

    //                 $price = $roomType->base_price * $nights;

    //                 BookingRoom::create([
    //                     'booking_id' => $booking->id,
    //                     'room_id' => $room->id,
    //                     'room_type_id' => $roomType->id,
    //                     'quantity' => 1,
    //                     'price' => $price
    //                 ]);


