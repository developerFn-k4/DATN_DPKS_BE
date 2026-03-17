<?php

$baseUrl = 'http://127.0.0.1:8000/api';

function req(string $method, string $url, array $data = [], array $headers = []): array {
    $options = [
        'http' => [
            'method' => $method,
            'header' => array_merge(['Content-Type: application/json'], $headers),
            'ignore_errors' => true,
        ],
    ];

    if ($method !== 'GET') {
        $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    return [
        'status' => $http_response_header[0] ?? null,
        'body' => $result,
    ];
}

// 1) Login
$login = req('POST', $baseUrl . '/auth/login', [
    'email' => 'customer@gmail.com',
    'password' => '123456',
]);

echo "LOGIN RESPONSE:\n";
echo $login['status'] . "\n";
echo $login['body'] . "\n\n";

$data = json_decode($login['body'], true);
if (!isset($data['success']) || !$data['success'] || !isset($data['token'])) {
    exit(1);
}

$token = $data['token'];

// 2) Create booking
$checkIn = (new DateTime('+10 days'))->format('Y-m-d');
$checkOut = (new DateTime('+11 days'))->format('Y-m-d');

$bookingData = req('POST', $baseUrl . '/bookings', [
    'room_id' => 1,
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'guests' => 1,
], [
    'Authorization: Bearer ' . $token,
]);

echo "CREATE BOOKING RESPONSE:\n";
echo $bookingData['status'] . "\n";
echo $bookingData['body'] . "\n\n";

$bookingJson = json_decode($bookingData['body'], true);
$bookingId = $bookingJson['data']['id'] ?? null;
if (! $bookingId) {
    exit(1);
}

// 3) Request VNPay payment URL
$paymentData = req('POST', $baseUrl . "/bookings/{$bookingId}/payment/vnpay", [], [
    'Authorization: Bearer ' . $token,
]);

echo "VNPay PAYMENT URL RESPONSE:\n";
echo $paymentData['status'] . "\n";
echo $paymentData['body'] . "\n\n";

// 4) (Optional) Simulate successful payment using the mock return endpoint
$mockReturn = req('GET', $baseUrl . "/bookings/{$bookingId}/payment/mock-return");

echo "MOCK RETURN RESPONSE:\n";
echo $mockReturn['status'] . "\n";
echo $mockReturn['body'] . "\n\n";
