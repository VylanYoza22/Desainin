<?php
/**
 * WhatsApp Integration Functions
 * Handles WhatsApp messaging for order notifications and customer confirmations
 */
require_once 'whatsapp_config.php';

/**
 * Fungsi untuk mengirim pesan WhatsApp
 */
function sendWhatsAppMessage($phone, $message) {
    $curl = curl_init();
    
    // Data yang akan dikirim
    $data = array(
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62' // Indonesia
    );
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => WHATSAPP_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . WHATSAPP_TOKEN,
            'Content-Type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Log response untuk debugging
    error_log("WhatsApp API Response: " . $response);
    
    return array(
        'success' => $httpCode == 200,
        'response' => $response,
        'http_code' => $httpCode
    );
}

/**
 * Fungsi untuk mengirim notifikasi pesanan baru ke admin
 */
function sendOrderNotification($orderData) {
    // Format data layanan
    $serviceTypes = [
        'video_editing' => 'Video Editing',
        'graphic_design' => 'Graphic Design',
        'social_media' => 'Social Media Content',
        'presentation' => 'Presentation Design'
    ];
    
    $packageTypes = [
        'basic' => 'Basic',
        'standard' => 'Standard',
        'premium' => 'Premium'
    ];
    
    // Buat pesan dari template
    $message = ORDER_MESSAGE_TEMPLATE;
    $message = str_replace('{nama}', $orderData['nama'], $message);
    $message = str_replace('{email}', $orderData['email'], $message);
    $message = str_replace('{whatsapp}', $orderData['whatsapp'] ?? 'Tidak tersedia', $message);
    $message = str_replace('{layanan}', $serviceTypes[$orderData['service_type']] ?? $orderData['service_type'], $message);
    $message = str_replace('{paket}', $packageTypes[$orderData['package_type']] ?? $orderData['package_type'], $message);
    $message = str_replace('{budget}', number_format((float)($orderData['budget'] ?: 0), 0, ',', '.'), $message);
    $message = str_replace('{deadline}', $orderData['deadline'] ?: 'Tidak ditentukan', $message);
    $message = str_replace('{judul}', $orderData['title'], $message);
    $message = str_replace('{deskripsi}', $orderData['description'], $message);
    $message = str_replace('{catatan}', $orderData['notes'] ?: 'Tidak ada', $message);
    $message = str_replace('{waktu}', date('d/m/Y H:i:s'), $message);
    $message = str_replace('{order_id}', $orderData['order_id'], $message);
    
    // Kirim ke admin
    return sendWhatsAppMessage(ADMIN_WHATSAPP, $message);
}

/**
 * Fungsi untuk mengirim notifikasi feedback baru ke admin
 */
function sendFeedbackNotification($feedbackData) {
    $message = FEEDBACK_MESSAGE_TEMPLATE;
    $message = str_replace('{nama}', $feedbackData['nama'], $message);
    $message = str_replace('{rating}', $feedbackData['rating'], $message);
    $message = str_replace('{pesan}', $feedbackData['pesan'], $message);
    $message = str_replace('{waktu}', date('d/m/Y H:i:s'), $message);
    
    // Kirim ke admin
    return sendWhatsAppMessage(ADMIN_WHATSAPP, $message);
}

/**
 * Send order confirmation to customer via WhatsApp
 * @param string $phone Customer phone number
 * @param array $orderData Order information
 * @return array Response with success status and message
 */
function sendOrderConfirmationToCustomer($customerPhone, $orderData) {
    $message = "✅ *Pesanan Anda Telah Diterima!*\n\n" .
               "Terima kasih telah mempercayai layanan kami.\n\n" .
               "📋 *Detail Pesanan:*\n" .
               "🆔 Order ID: #{order_id}\n" .
               "📝 Judul: {title}\n" .
               "💰 Budget: Rp {budget}\n" .
               "📅 Deadline: {deadline}\n\n" .
               "Tim kami akan segera menghubungi Anda untuk membahas detail lebih lanjut.\n\n" .
               "🙏 Terima kasih!";
    
    $message = str_replace('{order_id}', $orderData['order_id'], $message);
    $message = str_replace('{title}', $orderData['title'], $message);
    $message = str_replace('{budget}', number_format((float)($orderData['budget'] ?: 0), 0, ',', '.'), $message);
    $message = str_replace('{deadline}', $orderData['deadline'] ?: 'Tidak ditentukan', $message);
    
    return sendWhatsAppMessage($customerPhone, $message);
}

/**
 * Validate WhatsApp phone number format
 * @param string $phone Phone number to validate
 * @return string Formatted phone number
 */
function validateWhatsAppNumber($phone) {
    // Hapus semua karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Jika dimulai dengan 0, ganti dengan 62
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Jika tidak dimulai dengan 62, tambahkan 62
    if (substr($phone, 0, 2) != '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Log WhatsApp activity for audit purposes
 * @param string $phone Phone number
 * @param string $message Message content
 * @param string $status Success or failed status
 * @param string $response API response
 */
function logWhatsAppActivity($phone, $message, $status, $response = '') {
    $logFile = __DIR__ . '/../logs/whatsapp.log';
    $logDir = dirname($logFile);
    
    // Buat direktori log jika belum ada
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " | " . $phone . " | " . $status . " | " . substr($message, 0, 100) . "...\n";
    
    if ($response) {
        $logEntry .= "Response: " . $response . "\n";
    }
    
    $logEntry .= str_repeat('-', 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
