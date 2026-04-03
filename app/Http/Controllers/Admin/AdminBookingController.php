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
use App\Jobs\CancelBookingJob;

class AdminBookingController extends Controller
{
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

            $totalGuests = collect($request->rooms)->sum(fn($room) => ($room['adults'] ?? 0) + ($room['children'] ?? 0));

            // 1. Tạo Booking ban đầu
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
                'expired_at' => now()->addMinutes(2)
            ]);

            $subTotal = 0;
            $roomInfo = [];

            // 2. Xử lý phòng
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
                    throw new \Exception("Phòng {$roomType->name} không còn đủ chỗ.");
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
                    $roomInfo[] = ['name' => $roomType->name, 'price' => $price];
                    $room->update(['status' => 'occupied']);
                }
            }

            // 3. Xử lý dịch vụ
            $serviceInfo = [];
            if ($request->has('services')) {
                foreach ($request->services as $sData) {
                    $service = Service::findOrFail($sData['service_id']);
                    $sQty = $sData['quantity'] ?? 1;
                    $sPrice = $service->price * $sQty;

                    BookingService::create([
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'quantity' => $sQty,
                        'price' => $sPrice
                    ]);
                    $subTotal += $sPrice;
                    $serviceInfo[] = ['name' => $service->name, 'quantity' => $sQty, 'price' => $sPrice];
                }
            }

            // 4. Tính toán tài chính (Thuế 5%)
            $tax = $subTotal * 0.05;
            $totalAfterTax = $subTotal + $tax;
            $booking->update(['total_price' => $totalAfterTax]);

            // 5. Tạo Payment
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'order_id' => (string) Str::uuid(),
                'request_id' => (string) Str::uuid(),
                'amount' => $totalAfterTax,
                'method' => 'vnpay',
                'status' => 'pending'
            ]);

            DB::commit();

            // Dispatch Job tự động hủy sau 2 phút
            \App\Jobs\CancelBookingJob::dispatch($booking->id)->delay(now()->addMinutes(2));

            // 6. Trả về thông tin đầy đủ cho hóa đơn cuối cùng
            return response()->json([
                'status' => 'success',
                'data' => [
                    'booking_code' => $booking->booking_code,
                    'customer' => ['name' => $booking->name, 'phone' => $booking->phone],
                    'details' => [
                        'check_in' => $booking->check_in->format('d-m-Y'),
                        'check_out' => $booking->check_out->format('d-m-Y'),
                        'nights' => $nights,
                        'rooms' => $roomInfo,
                        'services' => $serviceInfo,
                    ],
                    'finance' => [
                        'sub_total' => $subTotal,
                        'tax_5' => $tax,
                        'total_final' => $totalAfterTax
                    ],
                    'payment_url' => route('vnpay.pay', $payment->order_id)
                ]
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
    public function createBooking(Request $request)
    {
        $booking = Booking::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'status' => 'pending',
            'expired_at' => Carbon::now()->addMinutes(2) // giữ phòng 2 phút
        ]);

        // phòng sẽ giữ trạng thái 'occupied' khi tạo booking
        $room = Room::find($request->room_id);
        if ($room) {
            $room->update(['status' => 'occupied']);
        }

        return response()->json([
            'message' => 'Booking created. Please pay within 2 minutes.',
            'booking' => $booking
        ]);
    }
}
