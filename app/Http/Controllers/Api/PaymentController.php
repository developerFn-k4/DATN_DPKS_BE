<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    /**
     * TẠO LINK THANH TOÁN VNPAY
     */
    public function createVnpay(Request $request, $bookingId)
    {
        // Lấy booking
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status === 'confirmed') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        // Cấu hình VNPAY
        $vnp_TmnCode   = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url       = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        // Mã tham chiếu giao dịch
        $vnp_TxnRef = 'PAY' . time() . rand(1000, 9999);

        $vnp_OrderInfo = "Thanh toan booking #" . $booking->id;
        $vnp_OrderType = "billpayment";
        $vnp_Locale    = "vn";
        $vnp_IpAddr    = $request->ip();

        // Sửa lại phần tính số tiền để tránh tràn và mất chữ số
        $vnp_Amount = bcmul((string)$booking->total_price, '100', 0); // chính xác, dạng string

        // Lưu Payment vào DB
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'order_id'   => $vnp_TxnRef,
            'request_id' => uniqid(),
            'amount'     => $booking->total_price,
            'method'     => 'vnpay',
            'status'     => 'pending'
        ]);

        // Chuẩn bị dữ liệu gửi VNPAY
        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => $vnp_IpAddr,
            "vnp_Locale"     => $vnp_Locale,
            "vnp_OrderInfo"  => $vnp_OrderInfo,
            "vnp_OrderType"  => $vnp_OrderType,
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef,
        ];

        // Sắp xếp theo key tăng dần
        ksort($inputData);

        // Tạo query string & hash
        $query = http_build_query($inputData);
        $vnpSecureHash = hash_hmac('sha512', urldecode($query), $vnp_HashSecret);

        // Link thanh toán
        $paymentUrl = $vnp_Url . "?" . $query . "&vnp_SecureHash=" . $vnpSecureHash;

        return response()->json([
            "payment_url" => $paymentUrl
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

        // Loại bỏ vnp_SecureHash trước khi tính hash
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        // Sắp xếp key theo thứ tự alphabet
        ksort($inputData);

        // Build hash data string theo chuẩn VNPAY
        $hashData = [];
        foreach ($inputData as $key => $value) {
            $hashData[] = urlencode($key) . '=' . urlencode($value);
        }
        $hashDataString = implode('&', $hashData);

        // Tính hash HMAC SHA512
        $secureHash = hash_hmac('sha512', $hashDataString, $vnp_HashSecret);

        // VERIFY SIGNATURE
        if (!$vnp_SecureHash || !hash_equals($secureHash, $vnp_SecureHash)) {
            return redirect()->to(
                "http://localhost:5173/payment-return?" .
                    http_build_query([
                        'success' => false,
                        'message' => 'Invalid signature'
                    ])
            );
        }

        $orderId = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;
        $amount = $request->vnp_Amount / 100; // VNPAY trả về *100
        $bankCode = $request->vnp_BankCode;
        $cardType = $request->vnp_CardType;
        $transactionNo = $request->vnp_TransactionNo;
        $payDate = $request->vnp_PayDate;

        $payment = Payment::where('order_id', $orderId)
            ->orWhereRaw("REPLACE(order_id, '-', '') = ?", [$orderId])
            ->first();

        if (!$payment) {
            return redirect()->to(
                "http://localhost:5173/payment-return?" .
                    http_build_query([
                        'success' => false,
                        'message' => 'Payment not found'
                    ])
            );
        }

        // DOUBLE PAYMENT
        if ($payment->status === 'success') {
            return redirect()->to(
                "http://localhost:5173/payment-return?" .
                    http_build_query([
                        'success' => true,
                        'message' => 'Payment already processed'
                    ])
            );
        }

        // VERIFY AMOUNT
        if ($payment->amount != $amount) {
            return redirect()->to(
                "http://localhost:5173/payment-return?" .
                    http_build_query([
                        'success' => false,
                        'message' => 'Invalid amount'
                    ])
            );
        }

        // PAYMENT SUCCESS
        if ($responseCode === '00') {
            DB::beginTransaction();
            try {
                // Cập nhật payment
                $payment->update([
                    'status' => 'success',
                    'transaction_no' => $transactionNo,
                    'bank_code' => $bankCode,
                    'card_type' => $cardType,
                    'pay_date' => $payDate
                ]);

                // Cập nhật booking
                $booking = Booking::findOrFail($payment->booking_id);
                $booking->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'paid_at' => now()
                ]);

                // Cập nhật trạng thái phòng
                $bookingRooms = DB::table('booking_rooms')
                    ->where('booking_id', $booking->id)
                    ->get();
                foreach ($bookingRooms as $item) {
                    DB::table('rooms')
                        ->where('id', $item->room_id)
                        ->update(['status' => 'booked']);
                }

                DB::commit();

                // Redirect frontend success
                return redirect()->to(
                    "http://localhost:5173/payment-return?" .
                        http_build_query([
                            'success' => true,
                            'order_id' => $orderId,
                            'transaction_id' => $transactionNo,
                            'amount' => $amount,
                            'bank' => $bankCode,
                            'card_type' => $cardType,
                            'pay_date' => $payDate,
                            'booking_status' => $booking->status
                        ])
                );
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("VNPay payment error: " . $e->getMessage());
                return redirect()->to(
                    "http://localhost:5173/payment-return?" .
                        http_build_query([
                            'success' => false,
                            'message' => 'Payment processing failed'
                        ])
                );
            }
        }

        // PAYMENT FAILED
        $payment->update(['status' => 'failed']);
        return redirect()->to(
            "http://localhost:5173/payment-return?" .
                http_build_query([
                    'success' => false,
                    'message' => 'Thanh toán thất bại'
                ])
        );
    }


    /**
     * LỊCH SỬ THANH TOÁN
     */
    public function history(Request $request)
    {

        $payments = Payment::with(['booking.room.roomType'])
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
}
