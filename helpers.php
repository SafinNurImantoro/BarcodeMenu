<?php
/**
 * Security & Validation Helper Functions
 */

/**
 * Validate and sanitize phone number
 * @param string $phone Phone number to validate
 * @return string|false Validated phone number or false
 */
function validatePhoneNumber($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it starts with 62 and has length between 10-15
    if (preg_match('/^62\d{9,13}$/', $phone)) {
        return $phone;
    }
    
    return false;
}

/**
 * Validate table number
 * @param string $tableNo Table number to validate
 * @return string|false Validated table number or false
 */
function validateTableNumber($tableNo) {
    $tableNo = trim($tableNo);
    
    // Allow alphanumeric and common separators
    if (preg_match('/^[A-Z0-9\-_]{1,20}$/i', $tableNo)) {
        return $tableNo;
    }
    
    return false;
}

/**
 * Validate and sanitize customer name
 * @param string $name Customer name to validate
 * @return string|false Validated name or false
 */
function validateCustomerName($name) {
    $name = trim($name);
    
    // Check length (min 3, max 100)
    if (strlen($name) < 3 || strlen($name) > 100) {
        return false;
    }
    
    // Allow letters, numbers, spaces, and basic punctuation
    if (preg_match('/^[a-zA-Z0-9\s\.\-\']+$/u', $name)) {
        return $name;
    }
    
    return false;
}

/**
 * Validate payment method
 * @param string $method Payment method to validate
 * @return string|false Validated payment method or false
 */
function validatePaymentMethod($method) {
    $allowed = ['QRIS', 'Tunai', 'Transfer'];
    
    return in_array($method, $allowed) ? $method : false;
}

/**
 * Sanitize notes/additional message
 * @param string $notes Notes to sanitize
 * @return string Sanitized notes
 */
function sanitizeNotes($notes) {
    // Limit length to 500 characters
    $notes = substr(trim($notes), 0, 500);
    
    // Remove potentially dangerous characters but keep newlines
    $notes = preg_replace('/[<>"`%$&;|\\]/', '', $notes);
    
    return $notes;
}

/**
 * Validate form data
 * @param array $data Form data to validate
 * @return array Validation result ['valid' => bool, 'errors' => array]
 */
function validateOrderForm($data) {
    $errors = [];
    
    // Validate table number
    if (empty($data['table_no'])) {
        $errors['table_no'] = 'Nomor meja harus diisi';
    } else if ($data['table_no'] = validateTableNumber($data['table_no'])) {
        // Table number is valid
    } else {
        $errors['table_no'] = 'Format nomor meja tidak valid';
    }
    
    // Validate customer name
    if (empty($data['customer_name'])) {
        $errors['customer_name'] = 'Nama pemesan harus diisi';
    } else if (!($data['customer_name'] = validateCustomerName($data['customer_name']))) {
        $errors['customer_name'] = 'Format nama tidak valid (min 3 karakter, hanya huruf, angka, spasi)';
    }
    
    // Validate WhatsApp number
    if (empty($data['customer_whatsapp'])) {
        $errors['customer_whatsapp'] = 'Nomor WhatsApp harus diisi';
    } else if (!($data['customer_whatsapp'] = validatePhoneNumber($data['customer_whatsapp']))) {
        $errors['customer_whatsapp'] = 'Format nomor WhatsApp tidak valid (gunakan format 62...)';
    }
    
    // Validate payment method
    if (empty($data['payment_method'])) {
        $errors['payment_method'] = 'Metode pembayaran harus dipilih';
    } else if (!($data['payment_method'] = validatePaymentMethod($data['payment_method']))) {
        $errors['payment_method'] = 'Metode pembayaran tidak valid';
    }
    
    // Sanitize notes (optional field)
    if (!empty($data['notes'])) {
        $data['notes'] = sanitizeNotes($data['notes']);
    }
    
    return [
        'valid' => count($errors) === 0,
        'errors' => $errors,
        'data' => $data
    ];
}

/**
 * Secure file path
 * Prevent directory traversal attacks
 * @param string $filename Filename to validate
 * @return string|false Safe filename or false
 */
function validateFileName($filename) {
    // Remove any path components
    $filename = basename($filename);
    
    // Only allow alphanumeric, underscore, hyphen, and dot
    if (preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
        return $filename;
    }
    
    return false;
}

/**
 * Log security event
 * @param string $event Event description
 * @param string $ip Client IP address
 */
function logSecurityEvent($event, $ip = null) {
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] IP: {$ip} | {$event}\n";
    
    error_log($logMessage, 3, __DIR__ . '/logs/security.log');
}

?>
