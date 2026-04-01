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

            // 1. Tạo booking chính
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
                'status' => 'pending',
                'expired_at' => now()->addMinutes(10)
            ]);

            // 2. Gán phòng
            foreach ($request->rooms as $roomData) {
                $roomType = RoomType::findOrFail($roomData['room_type_id']);
                $quantity = $roomData['quantity'] ?? 1;

                $availableRooms = Room::where('room_type_id', $roomType->id)
                    ->where('status', 'available')
                    ->whereDoesntHave('bookingRooms.booking', function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<', $checkOut)
                            ->where('check_out', '>', $checkIn);
                    })
                    ->lockForUpdate()
                    ->take($quantity)
                    ->get();

                if ($availableRooms->count() < $quantity) {
                    throw new \Exception("Không đủ phòng trống cho loại: {$roomType->name}");
                }

                foreach ($availableRooms as $room) {
                    BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $room->id,
                        'room_type_id' => $roomType->id,
                        'quantity' => 1,
                        'price' => $roomType->base_price * $nights
                    ]);

                    $total += $roomType->base_price * $nights;

                    $room->update(['status' => 'occupied']);
                }
            }

            // 3. Ghi dịch vụ nếu có
            if (!empty($request->services) && is_array($request->services)) {
                foreach ($request->services as $serviceData) {
                    $service = Service::findOrFail($serviceData['service_id']);
                    $quantity = $serviceData['quantity'] ?? 1;
                    $price = $service->price * $quantity;

                    BookingService::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'quantity' => $quantity,
                        'price' => $price
                    ]);

                    $total += $price;
                }
            }

            // 4. Cập nhật tổng tiền
            $booking->update(['total_price' => $total]);

            // 5. Tạo payment VNPay
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'order_id' => Str::uuid(),
                'request_id' => Str::uuid(),
                'amount' => $total,
                'method' => 'vnpay',
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'total' => $total,
                'payment_url' => route('vnpay.pay', $payment->order_id)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /*
|---------------------------------------------------------------------------
| VNPay REDIRECT
|---------------------------------------------------------------------------
*/
    public function vnpayPay($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        // Lấy thông tin từ env
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_Returnurl = env('VNPAY_RETURN_URL');

        // Chuẩn hóa tham số
        $vnp_TxnRef = preg_replace('/[^A-Z0-9]/i', '', $payment->order_id);
        $vnp_Amount = (int) ($payment->amount * 100);
        // Quan trọng: Loại bỏ ký tự đặc biệt trong OrderInfo để tránh lỗi encoding
        $vnp_OrderInfo = 'Booking ' . preg_replace('/[^A-Z0-9]/i', '', $payment->booking->booking_code);
        $vnp_CreateDate = now()->format('YmdHis');

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => request()->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => $vnp_OrderInfo,
            "vnp_OrderType"  => "other", // Thêm tham số bắt buộc theo chuẩn 2.1.0
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef,
        ];

        // 1. Sắp xếp key theo bảng chữ cái ASCII
        ksort($inputData);

        // 2. Tạo chuỗi Hash và Query đồng nhất
        $query = "";
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                $query .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $query .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // 3. Tạo SecureHash (Sử dụng chuỗi đã được urlencode)
        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // 4. Xây dựng URL cuối cùng
        $finalUrl = $vnp_Url . "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        return redirect($finalUrl);
    }
    /*
|---------------------------------------------------------------------------
| VNPay WEBHOOK
|---------------------------------------------------------------------------
*/
    public function vnpayWebhook(Request $request)
    {
        // Lấy toàn bộ query từ VNPay
        $inputData = $request->all();

        // Lấy vnp_SecureHash
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        // Sắp xếp dữ liệu theo key
        ksort($inputData);
        $hashData = implode('&', array_map(fn($k, $v) => "$k=$v", array_keys($inputData), $inputData));

        $vnp_HashSecret = env('VNP_HASH_SECRET'); // secret key

        // Kiểm tra hash
        $secureHashCheck = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        if ($secureHashCheck !== $vnp_SecureHash) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature'
            ]);
        }

        // Tìm payment
        $payment = Payment::where('order_id', $inputData['vnp_TxnRef'])->first();
        if (!$payment) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Payment not found'
            ]);
        }

        // Cập nhật trạng thái booking và payment
        if ($inputData['vnp_ResponseCode'] === '00') {
            $payment->update(['status' => 'success']);
            $payment->booking->update(['status' => 'confirmed']);
        } else {
            $payment->update(['status' => 'failed']);
            $payment->booking->update(['status' => 'cancelled']);
        }

        // Trả về response cho VNPay
        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm success'
        ]);
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
