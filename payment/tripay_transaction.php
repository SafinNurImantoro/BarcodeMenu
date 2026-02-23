<?php
require_once __DIR__ . '/../config.php';

/**
 * Create transaction to Tripay and return decoded JSON response.
 *
 * @param string $merchantRef Unique invoice reference
 * @param int|float $amount Total amount
 * @param string $customerName Customer name
 * @param string $customerEmail Customer email
 * @param array $orderItems Array item transaksi untuk Tripay
 * @param string $customerPhone Customer phone
 * @param string $method Tripay payment channel code (e.g. BRIVA, QRIS2)
 * @return array
 */
function createTripayTransaction(
    $merchantRef,
    $amount,
    $customerName,
    $customerEmail,
    $orderItems = [],
    $customerPhone = '',
    $method = 'BRIVA'
)
{
    $apiKey = TRIPAY_API_KEY;
    $privateKey = TRIPAY_PRIVATE_KEY;
    $merchantCode = TRIPAY_MERCHANT_CODE;

    if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
        return [
            'success' => false,
            'message' => 'Tripay credentials are not configured'
        ];
    }

    $amount = (int) round($amount);
    if ($amount <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid amount'
        ];
    }

    if (empty($orderItems) || !is_array($orderItems)) {
        return [
            'success' => false,
            'message' => 'Undefined parameter order items'
        ];
    }

    // DEV-* API keys are for sandbox environment
    $isSandbox = strpos($apiKey, 'DEV-') === 0;
    $tripayUrl = $isSandbox
        ? 'https://tripay.co.id/api-sandbox/transaction/create'
        : 'https://tripay.co.id/api/transaction/create';

    $baseUrl = rtrim(APP_URL, '/');

    $payload = [
        'method' => $method,
        'merchant_ref' => $merchantRef,
        'amount' => $amount,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'order_items' => $orderItems,
        'callback_url' => $baseUrl . '/payment/tripay_callback.php',
        'return_url' => $baseUrl . '/checkout.php',
        'expired_time' => time() + (24 * 60 * 60),
        'signature' => hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey),
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_URL => $tripayUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
        CURLOPT_FAILONERROR => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($rawResponse === false) {
        return [
            'success' => false,
            'message' => 'Tripay request failed',
            'error' => $curlError
        ];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Invalid Tripay response',
            'raw' => $rawResponse
        ];
    }

    return $decoded;
}
