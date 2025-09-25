<?php
/**
 * WhatsApp API Configuration
 * Configure your WhatsApp API provider settings here
 * Supported providers: Fonnte, Wablas
 */

// Primary configuration for Fonnte (https://fonnte.com)
define('WHATSAPP_API_URL', 'https://api.fonnte.com/send');
define('WHATSAPP_TOKEN', 'YOUR_FONNTE_TOKEN_HERE');
define('ADMIN_WHATSAPP', '6281234567890'); // Admin WhatsApp number (with country code)


// Konfigurasi alternatif untuk Wablas
// define('WHATSAPP_API_URL', 'https://console.wablas.com/api/send-message');
// define('WHATSAPP_TOKEN', 'YOUR_WABLAS_TOKEN_HERE');

// Template pesan
define('ORDER_MESSAGE_TEMPLATE', "🔔 *PESANAN BARU MASUK!*\n\n" .
    "📋 *Detail Pesanan:*\n" .
    "👤 Nama: {nama}\n" .
    "📧 Email: {email}\n" .
    "📱 WhatsApp: {whatsapp}\n" .
    "🎯 Layanan: {layanan}\n" .
    "📦 Paket: {paket}\n" .
    "💰 Budget: Rp {budget}\n" .
    "📅 Deadline: {deadline}\n" .
    "📝 Judul: {judul}\n" .
    "📄 Deskripsi: {deskripsi}\n" .
    "📌 Catatan: {catatan}\n\n" .
    "🕒 Waktu: {waktu}\n" .
    "🆔 Order ID: #{order_id}\n\n" .
    "Silakan segera follow up dengan klien!");

define('FEEDBACK_MESSAGE_TEMPLATE', "⭐ *FEEDBACK BARU!*\n\n" .
    "👤 Nama: {nama}\n" .
    "⭐ Rating: {rating}/5\n" .
    "💬 Pesan: {pesan}\n" .
    "🕒 Waktu: {waktu}");
?>
