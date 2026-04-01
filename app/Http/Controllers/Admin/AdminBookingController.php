<?php

namespace App\Http\Controllers\Admin;

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
use App\Http\Controllers\Controller;

class AdminBookingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SEARCH AVAILABLE ROOM
    |--------------------------------------------------------------------------
    */
    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        $roomTypes = RoomType::withCount(['rooms as available_rooms' => function ($query) use ($checkIn, $checkOut) {
            $query->where('status', 'available')
                ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
                    $q->where(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    });
                });
        }])->get();

        return response()->json($roomTypes);
    }

    /*
    |--------------------------------------------------------------------------
    | REALTIME PRICE
    |--------------------------------------------------------------------------
    */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array'
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);

        $total = 0;
        $details = [];

        foreach ($request->rooms as $room) {
            $roomType = RoomType::findOrFail($room['room_type_id']);
            $price = $roomType->base_price * ($room['quantity'] ?? 1) * $nights;

            $details[] = [
                'room_type' => $roomType->name,
                'quantity' => $room['quantity'] ?? 1,
                'price' => $price
            ];

            $total += $price;
        }

        return response()->json([
            'nights' => $nights,
            'rooms' => $details,
            'total' => $total
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE BOOKING
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            // Tính tổng khách
            $totalGuests = 0;
            foreach ($request->rooms as $room) {
                $totalGuests += ($room['adults'] ?? 0) + ($room['children'] ?? 0);
            }

            $total = 0;

            // CREATE BOOKING
            $booking = Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(6)),
                'user_id' => Auth::id(),
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'nights' => $nights,
                'guests' => $totalGuests,
                'status' => 'pending',
                'expired_at' => now()->addMinutes(10)
            ]);

            // ASSIGN ROOMS
            foreach ($request->rooms as $room) {
                $roomType = RoomType::findOrFail($room['room_type_id']);
                $quantity = $room['quantity'] ?? 1;

                $availableRooms = Room::where('room_type_id', $roomType->id)
                    ->where('status', 'available')
                    ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
                        $q->where(function ($q2) use ($checkIn, $checkOut) {
                            $q2->where('check_in', '<', $checkOut)
                                ->where('check_out', '>', $checkIn);
                        });
                    })
                    ->lockForUpdate()
                    ->take($quantity)
                    ->get();

                if ($availableRooms->count() < $quantity) {
                    throw new \Exception("Không đủ phòng cho loại: " . $roomType->name);
                }

                foreach ($availableRooms as $r) {
                    $price = $roomType->base_price * $nights;

                    BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $r->id,
                        'price' => $price
                    ]);

                    $total += $price;
                }
            }

            // SAVE SERVICES
            if (!empty($request->services) && is_array($request->services)) {
                foreach ($request->services as $service) {
                    $serviceModel = Service::findOrFail($service['service_id']);
                    $price = $serviceModel->price * ($service['quantity'] ?? 1);

                    BookingService::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service['service_id'],
                        'quantity' => $service['quantity'] ?? 1,
                        'price' => $price
                    ]);

                    $total += $price;
                }
            }

            // UPDATE TOTAL PRICE
            $booking->update(['total_price' => $total]);

            // CREATE PAYMENT
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'order_id' => Str::uuid(),
                'amount' => $total,
                'method' => 'momo',
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'total' => $total,
                'payment_url' => route('momo.pay', $payment->order_id)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MOMO WEBHOOK
    |--------------------------------------------------------------------------
    */
    public function momoWebhook(Request $request)
    {
        $payment = Payment::where('order_id', $request->orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'payment not found'], 404);
        }

        if ($request->resultCode == 0) {
            $payment->update(['status' => 'success']);
            $payment->booking->update(['status' => 'confirmed']);
        } else {
            $payment->update(['status' => 'failed']);
            $payment->booking->update(['status' => 'cancelled']);
        }

        return response()->json(['message' => 'webhook processed']);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL BOOKING
    |--------------------------------------------------------------------------
    */
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status == 'confirmed') {
            return response()->json(['message' => 'Cannot cancel confirmed booking'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled']);
    }

    /*
    |--------------------------------------------------------------------------
    | CRON AUTO CANCEL
    |--------------------------------------------------------------------------
    */
    public function autoCancel()
    {
        $bookings = Booking::where('status', 'pending')
            ->where('expired_at', '<', now())
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
        }

        return "Auto cancel done";
    }
}
