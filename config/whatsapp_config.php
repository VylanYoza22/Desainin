<?php
/**
 * WhatsApp Configuration
 * Configuration settings for WhatsApp API integration
 */

// WhatsApp API Configuration
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_BUSINESS_NUMBER', '6288299154725'); // Default business number

// WhatsApp API Settings (if using third-party service)
define('WHATSAPP_API_TOKEN', ''); // Add your API token here if using service
define('WHATSAPP_API_ENDPOINT', ''); // Add your API endpoint here if using service
define('WHATSAPP_TOKEN', ''); // Alias for compatibility
define('ADMIN_WHATSAPP', '6288299154725'); // Admin WhatsApp number

// Message Templates
define('ORDER_MESSAGE_TEMPLATE', "🔔 *PESANAN BARU MASUK!*\n\n" .
       "👤 *Nama:* {nama}\n" .
       "📧 *Email:* {email}\n" .
       "📱 *WhatsApp:* {whatsapp}\n" .
       "🎨 *Layanan:* {layanan}\n" .
       "📦 *Paket:* {paket}\n" .
       "💰 *Budget:* Rp {budget}\n" .
       "📅 *Deadline:* {deadline}\n" .
       "📝 *Judul:* {judul}\n" .
       "📋 *Deskripsi:* {deskripsi}\n" .
       "📌 *Catatan:* {catatan}\n" .
       "🆔 *Order ID:* #{order_id}\n" .
       "⏰ *Waktu:* {waktu}\n\n" .
       "Segera hubungi customer untuk konfirmasi!");

define('FEEDBACK_MESSAGE_TEMPLATE', "⭐ *FEEDBACK BARU!*\n\n" .
       "👤 *Nama:* {nama}\n" .
       "⭐ *Rating:* {rating}/5\n" .
       "💬 *Pesan:* {pesan}\n" .
       "⏰ *Waktu:* {waktu}");

define('WHATSAPP_ORDER_CREATED_TEMPLATE', 'Halo {name}! Pesanan Anda #{order_id} telah diterima. Kami akan segera memproses pesanan Anda. Terima kasih!');
define('WHATSAPP_ORDER_CONFIRMED_TEMPLATE', 'Pesanan #{order_id} telah dikonfirmasi! Kami akan mulai mengerjakan proyek Anda. Status dapat dilihat di: {status_url}');
define('WHATSAPP_ORDER_COMPLETED_TEMPLATE', 'Selamat! Pesanan #{order_id} telah selesai. Silakan cek hasil pekerjaan Anda di dashboard. Terima kasih telah mempercayai Desainin!');

// WhatsApp Features Configuration
define('WHATSAPP_NOTIFICATIONS_ENABLED', true);
define('WHATSAPP_AUTO_REPLY_ENABLED', false);

// Business Information
define('BUSINESS_NAME', 'Desainin');
define('BUSINESS_WEBSITE', 'https://desainin.com');
define('SUPPORT_HOURS', '09:00 - 18:00 WIB');

?>
