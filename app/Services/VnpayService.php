<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class VnpayService
{
    public function createPaymentUrl(Booking $booking, string $clientIp): string
    {
        $tmnCode = (string) config('services.vnpay.tmn_code');
        $hashSecret = (string) config('services.vnpay.hash_secret');
        $baseUrl = (string) config('services.vnpay.url');
        $returnUrl = (string) config('services.vnpay.return_url');

        if ($tmnCode === '' || $hashSecret === '' || $baseUrl === '' || $returnUrl === '') {
            throw new RuntimeException('VNPay configuration is incomplete.');
        }

        if (! $this->isPublicReturnUrl($returnUrl)) {
            throw new RuntimeException('VNPAY yeu cau RETURN_URL la domain public da dang ky (khong dung localhost/127.0.0.1).');
        }

        $txnRef = $booking->payment_txn_ref ?: $this->generateTxnRef($booking);

        $booking->update([
            'payment_method' => 'vnpay',
            'payment_status' => 'processing',
            'payment_txn_ref' => $txnRef,
        ]);

        $amount = (int) round(((float) $booking->total_price) * 100);
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        $payload = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => 'Thanh toan dat phong #' . $booking->id,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $clientIp,
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $now->copy()->addMinutes(15)->format('YmdHis'),
        ];

        $secureHash = $this->createSecureHash($payload, $hashSecret);
        $queryString = http_build_query($payload) . '&vnp_SecureHash=' . $secureHash;

        return $baseUrl . '?' . $queryString;
    }

    public function verifySignature(array $input): bool
    {
        $hashSecret = (string) config('services.vnpay.hash_secret');
        $receivedHash = (string) ($input['vnp_SecureHash'] ?? '');

        if ($hashSecret === '' || $receivedHash === '') {
            return false;
        }

        $hashData = $input;
        unset($hashData['vnp_SecureHash'], $hashData['vnp_SecureHashType']);

        $calculated = $this->createSecureHash($hashData, $hashSecret);

        return hash_equals($calculated, $receivedHash);
    }

    private function createSecureHash(array $payload, string $hashSecret): string
    {
        ksort($payload);
        $hashData = urldecode(http_build_query($payload));

        return hash_hmac('sha512', $hashData, $hashSecret);
    }

    private function generateTxnRef(Booking $booking): string
    {
        return 'BK' . $booking->id . strtoupper(Str::random(8));
    }

    private function isPublicReturnUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) {
            return false;
        }

        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host) === 1) {
            return false;
        }

        return true;
    }
}
