<?php

namespace Tests\Feature;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.vnpay.tmn_code', 'TESTCODE');
        config()->set('services.vnpay.hash_secret', 'TESTSECRET');
        config()->set('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        config()->set('services.vnpay.return_url', 'http://127.0.0.1:8000/api/bookings/payment/vnpay-return');
        config()->set('app.frontend_url', 'http://localhost:5173');
    }

    public function test_user_can_create_vnpay_payment_url_for_own_booking(): void
    {
        $user = User::factory()->create();
        $booking = $this->createBooking($user->id);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/bookings/' . $booking->id . '/payment/vnpay');

        $response->assertOk()->assertJsonStructure([
            'message',
            'data' => ['booking_id', 'payment_url', 'txn_ref'],
        ]);

        $booking->refresh();

        $this->assertSame('vnpay', $booking->payment_method);
        $this->assertSame('processing', $booking->payment_status);
        $this->assertNotNull($booking->payment_txn_ref);
    }

    public function test_vnpay_return_with_invalid_signature_redirects_failed(): void
    {
        $user = User::factory()->create();
        $booking = $this->createBooking($user->id);

        $response = $this->get('/api/bookings/payment/vnpay-return?vnp_TxnRef=INVALID&vnp_SecureHash=abc');

        $response->assertRedirect('http://localhost:5173/payment/result?status=failed&reason=invalid_signature');

        $booking->refresh();
        $this->assertSame('unpaid', $booking->payment_status);
    }

    public function test_vnpay_success_callback_marks_booking_paid_and_sends_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $booking = $this->createBooking($user->id);

        $booking->update([
            'payment_txn_ref' => 'BKTEST001',
            'payment_status' => 'processing',
            'payment_method' => 'vnpay',
        ]);

        $params = [
            'vnp_TxnRef' => 'BKTEST001',
            'vnp_Amount' => 1000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '99999',
        ];

        $signed = $this->signVnpayParams($params);

        $response = $this->get('/api/bookings/payment/vnpay-return?' . http_build_query($signed));

        $response->assertRedirect('http://localhost:5173/payment/result?status=success&booking_id=' . $booking->id);

        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('99999', $booking->payment_transaction_no);

        Mail::assertSent(BookingPaidMail::class, 1);
    }

    private function signVnpayParams(array $params): array
    {
        $hashData = $params;
        ksort($hashData);

        $query = urldecode(http_build_query($hashData));
        $secureHash = hash_hmac('sha512', $query, (string) config('services.vnpay.hash_secret'));

        $params['vnp_SecureHash'] = $secureHash;

        return $params;
    }

    private function createBooking(int $userId): Booking
    {
        $hotelId = DB::table('hotels')->insertGetId([
            'name' => 'Hotel Test',
            'address' => 'Address',
            'phone' => '0123456789',
            'email' => 'hotel@test.com',
            'description' => 'Desc',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomTypeId = DB::table('room_types')->insertGetId([
            'hotel_id' => $hotelId,
            'name' => 'Deluxe',
            'description' => 'Desc',
            'capacity' => 2,
            'bed_type' => 'Queen',
            'base_price' => 500000,
            'currency' => 'VND',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomId = DB::table('rooms')->insertGetId([
            'room_number' => 'A101',
            'room_type_id' => $roomTypeId,
            'floor' => 1,
            'status' => 'available',
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        return Booking::create([
            'room_id' => $roomId,
            'user_id' => $userId,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'status' => 'pending',
            'payment_method' => 'vnpay',
            'payment_status' => 'unpaid',
            'total_price' => 10000,
        ]);
    }
}
