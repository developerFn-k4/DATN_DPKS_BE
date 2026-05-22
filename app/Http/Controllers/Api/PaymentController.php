<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function checkout(Request $request, $bookingId)
    {
        $validated = $request->validate([
            'method' => 'required|in:vnpay,momo,cash'
        ]);

        if ($validated['method'] === 'vnpay') {
            return $this->createVnpay($request, $bookingId);
        }

        if ($validated['method'] === 'momo') {
            return $this->createMomo($request, $bookingId);
        }

        return $this->payCash($request, $bookingId);
    }


    /**
     * TẠO LINK THANH TOÁN VNPAY
     */
    public function createVnpay(Request $request, $bookingId)
    {
        $booking = $this->resolveBookingForUser($request, $bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        $vnp_TxnRef = 'VNP' . now()->format('YmdHis') . rand(1000, 9999);

        $vnp_OrderInfo = "Thanh toan booking #" . $booking->id;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $booking->total_price * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $request->ip();

        /**
         * LƯU PAYMENT
         */
        Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $vnp_TxnRef,
            'request_id' => uniqid(),
            'amount' => $booking->total_price,
            'method' => 'vnpay',
            'status' => 'pending'
        ]);

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);

        $query = "";
        $hashdata = "";
        $i = 0;

        foreach ($inputData as $key => $value) {

            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return response()->json([
            "success" => true,
            "method" => "vnpay",
            "order_id" => $vnp_TxnRef,
            "payment_url" => $paymentUrl
        ]);
    }

    public function createMomo(Request $request, $bookingId)
    {
        $booking = $this->resolveBookingForUser($request, $bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        $orderId = 'MOMO' . now()->format('YmdHis') . rand(1000, 9999);

        Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $orderId,
            'request_id' => (string) Str::uuid(),
            'amount' => $booking->total_price,
            'method' => 'momo',
            'status' => 'pending',
        ]);

        // Mock MoMo URL for local testing. FE can replace with real MoMo gateway URL.
        $paymentUrl = route('api.payment.momo.simulate-success', ['orderId' => $orderId]);

        return response()->json([
            'success' => true,
            'method' => 'momo',
            'order_id' => $orderId,
            'payment_url' => $paymentUrl,
        ]);
    }

    public function payCash(Request $request, $bookingId)
    {
        $booking = $this->resolveBookingForUser($request, $bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        $orderId = 'CASH' . now()->format('YmdHis') . rand(1000, 9999);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $orderId,
            'request_id' => (string) Str::uuid(),
            'amount' => $booking->total_price,
            'method' => 'cash',
            'status' => 'success',
        ]);

        $this->markPaymentSuccess($payment);

        return response()->json([
            'success' => true,
            'method' => 'cash',
            'message' => 'Xac nhan thanh toan tien mat thanh cong',
            'payment' => [
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
            ],
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'paid_at' => $booking->paid_at,
            ],
            'actions' => [
                'home' => env('FRONTEND_HOME_URL', '/'),
                'my_bookings' => env('FRONTEND_BOOKINGS_URL', '/my-bookings'),
            ],
        ]);
    }

    /**
     * CALLBACK SAU KHI THANH TOÁN
     */
    public function vnpayReturn(Request $request)
    {

        $vnp_HashSecret = config('vnpay.hash_secret');

        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? null;

        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);

        $hashData = "";
        $i = 0;

        foreach ($inputData as $key => $value) {

            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        /**
         * VERIFY SIGNATURE
         */
        if (!$vnp_SecureHash || !hash_equals($secureHash, $vnp_SecureHash)) {

            Log::error("VNPay Invalid Signature", [
                "data" => $request->all()
            ]);

            return response()->json([
                "message" => "Invalid signature"
            ], 400);
        }

        $orderId = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;
        $amount = $request->vnp_Amount / 100;

        $payment = Payment::where('order_id', $orderId)
            ->orWhereRaw("REPLACE(order_id, '-', '') = ?", [$orderId])
            ->first();

        if (!$payment) {
            return response()->json([
                "message" => "Payment not found"
            ], 404);
        }

        /**
         * CHỐNG DOUBLE PAYMENT
         */
        if ($payment->status == 'success') {
            return response()->json([
                "message" => "Payment already confirmed"
            ]);
        }

        /**
         * VERIFY AMOUNT
         */
        if ($payment->amount != $amount) {
            return response()->json([
                "message" => "Invalid amount"
            ], 400);
        }

        if ($responseCode == '00') {
            $this->markPaymentSuccess($payment);

            return redirect()->away($this->buildFrontendResultUrl(true, $payment));
        }

        $payment->status = 'failed';
        $payment->save();

        return redirect()->away($this->buildFrontendResultUrl(false, $payment));
    }

    public function fakeVnpaySuccess(Request $request, $orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        $this->markPaymentSuccess($payment);

        return redirect()->away($this->buildFrontendResultUrl(true, $payment));
    }

    public function simulateMomoSuccess($orderId)
    {
        $payment = Payment::where('order_id', $orderId)
            ->where('method', 'momo')
            ->firstOrFail();

        $this->markPaymentSuccess($payment);

        return redirect()->away($this->buildFrontendResultUrl(true, $payment));
    }

    public function momoReturn(Request $request)
    {
        $orderId = $request->input('orderId') ?? $request->input('order_id');

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'orderId is required',
            ], 422);
        }

        $payment = Payment::where('order_id', $orderId)
            ->where('method', 'momo')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $resultCode = (string) $request->input('resultCode', '0');

        if ($resultCode === '0') {
            $this->markPaymentSuccess($payment);
            return redirect()->away($this->buildFrontendResultUrl(true, $payment));
        }

        $payment->status = 'failed';
        $payment->save();

        return redirect()->away($this->buildFrontendResultUrl(false, $payment));
    }

    public function status(Request $request, $orderId)
    {
        $payment = Payment::with(['booking.bookingRooms.room.roomType'])
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if (!$payment->booking || $payment->booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $payment->order_id,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'booking' => [
                    'id' => $payment->booking->id,
                    'booking_code' => $payment->booking->booking_code,
                    'status' => $payment->booking->status,
                    'payment_status' => $payment->booking->payment_status,
                    'check_in' => $payment->booking->check_in,
                    'check_out' => $payment->booking->check_out,
                    'total_price' => $payment->booking->total_price,
                ],
                'actions' => [
                    'home' => env('FRONTEND_HOME_URL', '/'),
                    'my_bookings' => env('FRONTEND_BOOKINGS_URL', '/my-bookings'),
                ],
            ],
        ]);
    }


    /**
     * LỊCH SỬ THANH TOÁN
     */
    public function history(Request $request)
    {

        $payments = Payment::with(['booking.bookingRooms.room.roomType'])
            ->whereHas('booking', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json([
            "success" => true,
            "data" => $payments
        ]);
    }

    private function resolveBookingForUser(Request $request, $bookingId): Booking
    {
        return Booking::where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    private function markPaymentSuccess(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->status !== 'success') {
                $payment->status = 'success';
                $payment->save();
            }

            $booking = $payment->booking;

            if (!$booking) {
                return;
            }

            $booking->status = 'confirmed';
            $booking->payment_status = 'paid';
            $booking->paid_at = now();
            $booking->save();

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->status = 'booked';
                    $bookingRoom->room->save();
                }
            }
        });
    }

    private function buildFrontendResultUrl(bool $isSuccess, Payment $payment): string
    {
        $defaultRoute = $isSuccess
            ? env('FRONTEND_HOME_URL', '/payment-result')
            : env('FRONTEND_PAYMENT_RETRY_URL', '/payment-result');

        $baseUrl = $isSuccess
            ? env('FRONTEND_PAYMENT_SUCCESS_URL', $defaultRoute)
            : env('FRONTEND_PAYMENT_FAILED_URL', $defaultRoute);

        $params = [
            'booking_id' => $payment->booking_id,
            'order_id' => $payment->order_id,
            'method' => $payment->method,
            'status' => $payment->status,
        ];

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . http_build_query($params);
    }
}
