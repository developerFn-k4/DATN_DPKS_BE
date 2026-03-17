<?php

namespace Tests\Feature;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
        
        // Setup payment config
        config()->set('services.vnpay.tmn_code', 'TESTCODE');
        config()->set('services.vnpay.hash_secret', 'TESTSECRET');
        config()->set('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        config()->set('services.vnpay.return_url', 'http://127.0.0.1:8000/api/bookings/payment/vnpay-return');
        config()->set('app.frontend_url', 'http://localhost:5173');
    }

    private function createTestData(): void
    {
        $this->user = User::factory()->create();

        $hotel = Hotel::create([
            'name' => 'Test Hotel',
            'address' => 'Test Address',
            'phone' => '0123456789',
            'email' => 'hotel@test.com',
            'description' => 'Test',
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'status' => 'active',
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'Deluxe',
            'description' => 'Test',
            'capacity' => 2,
            'bed_type' => 'Queen',
            'base_price' => 1000000,
            'currency' => 'VND',
            'status' => 'active',
        ]);

        $room = Room::create([
            'room_number' => 'A101',
            'room_type_id' => $roomType->id,
            'floor' => 1,
            'status' => 'available',
        ]);

        $this->booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'guest_name' => 'Test Guest',
            'guest_email' => 'test@example.com',
            'guest_phone' => '0987654321',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_price' => 1000000,
        ]);
    }

    // ================================================================
    // Test 1: Tạo link thanh toán VNPay thành công - 200
    // ================================================================
    public function test_create_vnpay_payment_url_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'booking_id',
                    'payment_url',
                    'txn_ref',
                ]
            ]);

        $this->booking->refresh();
        $this->assertSame('processing', $this->booking->payment_status);
        $this->assertSame('vnpay', $this->booking->payment_method);
        $this->assertNotNull($this->booking->payment_txn_ref);
    }

    // ================================================================
    // Test 2: Không thể tạo link thanh toán nếu không login - 401
    // ================================================================
    public function test_create_payment_url_without_auth_returns_401(): void
    {
        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertStatus(401);
    }

    // ================================================================
    // Test 3: Tạo link thanh toán cho booking không tồn tại - 404
    // ================================================================
    public function test_create_payment_url_for_nonexistent_booking_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/bookings/99999/payment/vnpay');

        $response->assertStatus(404);
    }

    // ================================================================
    // Test 4: Không thể tạo link thanh toán cho booking người khác - 404
    // ================================================================
    public function test_cannot_create_payment_for_other_user_booking(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertStatus(404);
    }

    // ================================================================
    // Test 5: Không thể tạo link thanh toán nếu đã thanh toán - 422
    // ================================================================
    public function test_cannot_create_payment_if_already_paid(): void
    {
        Sanctum::actingAs($this->user);

        $this->booking->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Booking da duoc thanh toan');
    }

    // ================================================================
    // Test 6: Không thể tạo link thanh toán nếu booking bị cancel - 422
    // ================================================================
    public function test_cannot_create_payment_if_booking_cancelled(): void
    {
        Sanctum::actingAs($this->user);

        $this->booking->update(['status' => 'cancelled']);

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertStatus(422);
    }

    // ================================================================
    // Test 7: VNPay callback với chữ ký không hợp lệ - redirect failed
    // ================================================================
    public function test_vnpay_return_with_invalid_signature(): void
    {
        $response = $this->get('/api/bookings/payment/vnpay-return?vnp_TxnRef=INVALID&vnp_SecureHash=wrongsignature');

        $response->assertRedirect('http://localhost:5173/payment/result');
    }

    // ================================================================
    // Test 8: VNPay callback thành công - mark booking as paid - 200
    // ================================================================
    public function test_vnpay_return_success_marks_booking_paid(): void
    {
        Mail::fake();

        $this->booking->update([
            'payment_txn_ref' => 'TESTBK001',
            'payment_status' => 'processing',
            'payment_method' => 'vnpay',
        ]);

        $params = [
            'vnp_TxnRef' => 'TESTBK001',
            'vnp_Amount' => 100000000, // 1,000,000 VND * 100
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '99999',
        ];

        $query = $this->buildVnpaySignature($params);

        $response = $this->get('/api/bookings/payment/vnpay-return?' . http_build_query($query));

        $response->assertRedirect();

        $this->booking->refresh();
        $this->assertSame('paid', $this->booking->payment_status);
        $this->assertSame('confirmed', $this->booking->status);
        $this->assertSame('99999', $this->booking->payment_transaction_no);

        Mail::assertSent(BookingPaidMail::class);
    }

    // ================================================================
    // Test 9: VNPay callback - amount mismatch - 422
    // ================================================================
    public function test_vnpay_return_with_wrong_amount(): void
    {
        $this->booking->update([
            'payment_txn_ref' => 'TESTBK002',
            'payment_status' => 'processing',
        ]);

        $params = [
            'vnp_TxnRef' => 'TESTBK002',
            'vnp_Amount' => 500000000, // Wrong amount
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];

        $query = $this->buildVnpaySignature($params);

        $response = $this->get('/api/bookings/payment/vnpay-return?' . http_build_query($query));

        $response->assertRedirect();

        $this->booking->refresh();
        $this->assertSame('failed', $this->booking->payment_status);
    }

    // ================================================================
    // Test 10: VNPay callback - payment rejected by gateway - 422
    // ================================================================
    public function test_vnpay_return_with_gateway_rejection(): void
    {
        $this->booking->update([
            'payment_txn_ref' => 'TESTBK003',
            'payment_status' => 'processing',
        ]);

        $params = [
            'vnp_TxnRef' => 'TESTBK003',
            'vnp_Amount' => 100000000,
            'vnp_ResponseCode' => '01', // Reject code
            'vnp_TransactionStatus' => '02',
        ];

        $query = $this->buildVnpaySignature($params);

        $response = $this->get('/api/bookings/payment/vnpay-return?' . http_build_query($query));

        $response->assertRedirect();

        $this->booking->refresh();
        $this->assertSame('failed', $this->booking->payment_status);
    }

    // ================================================================
    // Test 11: VNPay IPN callback (server-to-server) - success
    // ================================================================
    public function test_vnpay_ipn_success(): void
    {
        Mail::fake();

        $this->booking->update([
            'payment_txn_ref' => 'TESTBK004',
            'payment_status' => 'processing',
            'payment_method' => 'vnpay',
        ]);

        $params = [
            'vnp_TxnRef' => 'TESTBK004',
            'vnp_Amount' => 100000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TransactionNo' => '88888',
        ];

        $query = $this->buildVnpaySignature($params);

        $response = $this->postJson('/api/bookings/payment/vnpay-ipn', $query);

        $response->assertOk()
            ->assertJsonPath('RspCode', '00');

        $this->booking->refresh();
        $this->assertSame('paid', $this->booking->payment_status);

        Mail::assertSent(BookingPaidMail::class);
    }

    // ================================================================
    // Test 12: VNPay IPN - idempotent check (prevents double-charging)
    // ================================================================
    public function test_vnpay_ipn_idempotent_prevents_double_payment(): void
    {
        Mail::fake();

        $this->booking->update([
            'payment_txn_ref' => 'TESTBK005',
            'payment_status' => 'paid', // Already paid
            'payment_method' => 'vnpay',
        ]);

        $params = [
            'vnp_TxnRef' => 'TESTBK005',
            'vnp_Amount' => 100000000,
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ];

        $query = $this->buildVnpaySignature($params);

        $response = $this->postJson('/api/bookings/payment/vnpay-ipn', $query);

        $response->assertOk()
            ->assertJsonPath('RspCode', '02'); // Already confirmed

        Mail::assertNotSent(BookingPaidMail::class);
    }

    // ================================================================
    // Test 13: Mock payment mode - returns mock URL
    // ================================================================
    public function test_mock_payment_mode_creates_mock_url(): void
    {
        config()->set('app.payment_mode', 'mock');

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment/vnpay");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'booking_id',
                    'payment_url',
                    'txn_ref',
                ]
            ]);

        $url = $response->json('data.payment_url');
        $this->assertStringContainsString('payment/mock-return', $url);
    }

    // ================================================================
    // Test 14: Mock payment return - auto marks booking as paid
    // ================================================================
    public function test_mock_payment_return_marks_booking_paid(): void
    {
        Mail::fake();

        config()->set('app.payment_mode', 'mock');

        $response = $this->get("/api/bookings/{$this->booking->id}/payment/mock-return");

        $response->assertRedirect();

        $this->booking->refresh();
        $this->assertSame('paid', $this->booking->payment_status);
        $this->assertSame('confirmed', $this->booking->status);

        Mail::assertSent(BookingPaidMail::class);
    }

    // ================================================================
    // Helper Functions
    // ================================================================

    private function buildVnpaySignature(array $params): array
    {
        ksort($params);
        
        $hashData = [];
        foreach ($params as $key => $value) {
            $hashData[] = urlencode($key) . '=' . urlencode($value);
        }
        
        $query = implode('&', $hashData);
        $secureHash = hash_hmac('sha512', $query, (string) config('services.vnpay.hash_secret'));
        
        $params['vnp_SecureHash'] = $secureHash;
        
        return $params;
    }
}
