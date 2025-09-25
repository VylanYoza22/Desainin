# 🎨 Desainin - Platform Kreatif

Platform web untuk layanan video editing dan graphic design dengan sistem manajemen pesanan yang lengkap.

## ✨ Fitur Utama

### 🔐 Sistem Autentikasi
- Login/Register dengan validasi keamanan
- Session management dengan timeout
- Profile management dengan upload foto

### 📦 Manajemen Pesanan
- **Status Tracking**: 8 tahap progress dengan timeline visual
- **Real-time Updates**: Progress otomatis berdasarkan status
- **WhatsApp Integration**: Notifikasi otomatis ke admin dan customer
- **File Management**: Upload dan manajemen file pesanan

### 🎯 Layanan yang Tersedia
- 🎬 **Video Editing**: Basic, Standard, Premium
- 🎨 **Graphic Design**: Logo, banner, poster
- 📱 **Social Media Content**: Instagram, Facebook, TikTok
- 📊 **Presentation Design**: PowerPoint, Keynote

### 📊 Dashboard & Analytics
- User dashboard dengan statistik pesanan
- Progress tracking dengan visual timeline
- Order history dan filtering
- Real-time status updates

## 🏗️ Struktur Project

```
PKK2/
├── 📁 config/           # Konfigurasi database & sistem
│   ├── database.php     # Koneksi database
│   ├── helpers.php      # Helper functions
│   ├── constants.php    # Application constants
│   ├── status_functions.php  # Status management
│   ├── whatsapp_functions.php # WhatsApp integration
│   └── error_handler.php     # Error handling
├── 📁 pages/            # Halaman aplikasi
│   ├── auth/           # Login, register, logout
│   ├── user/           # Dashboard, profile
│   ├── orders/         # Order management
│   ├── admin/          # Admin panel
│   └── errors/         # Error pages (404, 500)
├── 📁 includes/         # Komponen reusable
│   ├── header.php      # HTML header
│   ├── footer.php      # Footer component
│   └── navigation.php  # Navigation bar
├── 📁 assets/           # CSS, JS, images
├── 📁 uploads/          # User uploaded files
├── 📁 logs/            # Application logs
└── 📁 docs/            # Dokumentasi
```

## 🚀 Instalasi

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- XAMPP/WAMP (untuk development)

### Setup Database
```sql
-- Import file SQL dari folder sql/
-- Atau jalankan create_orders_table.sql
```

### Konfigurasi
1. Copy `config/database.php` dan sesuaikan kredensial database
2. Set WhatsApp API credentials di `config/whatsapp_config.php`
3. Pastikan folder `uploads/` dan `logs/` writable

## 📱 Status System

| Status | Progress | Deskripsi |
|--------|----------|-----------|
| `pending` | 10% | Pesanan diterima |
| `confirmed` | 20% | Dikonfirmasi admin |
| `payment_pending` | 30% | Menunggu pembayaran |
| `payment_confirmed` | 40% | Pembayaran diterima |
| `in_progress` | 50% | Sedang dikerjakan |
| `review` | 70% | Review & revisi |
| `final_review` | 85% | Review final |
| `completed` | 100% | Selesai |

## 🔧 Fitur Teknis

### Security
- ✅ SQL Injection protection (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ Session security dengan timeout
- ✅ File upload validation
- ✅ Error logging dan handling

### Performance
- ✅ Database indexing untuk query optimization
- ✅ Lazy loading untuk images
- ✅ CSS/JS minification ready
- ✅ Responsive design untuk mobile

### Integration
- ✅ WhatsApp API untuk notifikasi
- ✅ Email notification system (optional)
- ✅ File upload dengan validation
- ✅ Real-time progress updates

## 🎨 UI/UX Features

### Design System
- **Dark Theme**: Modern dark UI dengan accent amber/gold
- **Glass Morphism**: Backdrop blur effects
- **Animated Backgrounds**: Gradient animations
- **Responsive**: Mobile-first design
- **Icons**: Font Awesome 6.4.0
- **Framework**: Tailwind CSS

### Components
- Progress bars dengan animasi
- Status badges dengan warna dinamis
- Modal dialogs
- Toast notifications
- Loading states
- Empty states

## 📝 API Endpoints

### WhatsApp Integration
```php
// Send order notification
sendOrderNotification($orderData);

// Send customer confirmation
sendOrderConfirmationToCustomer($customerPhone, $orderData);

// Send feedback notification
sendFeedbackNotification($feedbackData);
```

### Helper Functions
```php
// Format currency
formatCurrency($amount); // Returns: "Rp 100.000"

// Format date
formatDate($date, $includeTime); // Returns: "28 Agu 2025"

// Validate WhatsApp
validateWhatsAppNumber($number); // Returns: boolean
```

## 🔍 Debugging

### Debug Tools
- `debug_order_status.php` - Debug progress percentage issues
- Error logging ke `logs/error.log`
- SQL query logging (optional)

### Common Issues
1. **Progress tidak update**: Check status_functions.php
2. **WhatsApp tidak terkirim**: Verify API credentials
3. **File upload gagal**: Check folder permissions

## 📈 Future Enhancements

### Planned Features
- [ ] Payment gateway integration
- [ ] Advanced file management
- [ ] Customer rating system
- [ ] Admin analytics dashboard
- [ ] Multi-language support
- [ ] API documentation
- [ ] Mobile app

### Performance Improvements
- [ ] Redis caching
- [ ] CDN integration
- [ ] Database optimization
- [ ] Image compression

## 🤝 Contributing

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

- 📧 Email: support@desainin.com
- 💬 WhatsApp: +62 812-3456-7890
- 🌐 Website: https://desainin.com

---

**Made with ❤️ for creators by Desainin Team**

## 📄 Manual Pengguna (Urutan Cara Kerja)

Di bawah ini adalah panduan langkah demi langkah alur penggunaan platform Desainin, lengkap dengan rujukan file agar memudahkan pemahaman dan troubleshooting.

### 1) Akses Beranda & Navigasi
- Buka `index.php`.
- Sidebar dan top navigation menyesuaikan status login.
  - Jika belum login: tombol menuju `pages/auth/login.php` dan `pages/auth/register.php` tersedia.
  - Jika sudah login: menu cepat ke `order.php`, `my-orders.php`, `settings.php`, dan `pages/user/show-profile.php`.
- Komponen navigasi reusable: `includes/navigation.php`.

### 2) Registrasi Akun Baru
- Halaman: `pages/auth/register.php`.
- Validasi kuat: username/email unik, password minimal 6 karakter, opsional WhatsApp (`validateWhatsAppNumber()` di `config/helpers.php`).
- Password disimpan menggunakan `PASSWORD_ARGON2ID`.
- Setelah sukses, diarahkan otomatis ke `pages/auth/login.php` (delay 3 detik).

### 3) Login
- Halaman: `pages/auth/login.php`.
- Bisa login dengan username ATAU email + password (prepared statements, protection anti SQLi).
- Jika sukses: dibawa ke `pages/user/dashboard.php` (atau ke halaman redirect yang diminta via query `redirect`).

### 4) Dashboard Pengguna
- Halaman: `pages/user/dashboard.php`.
- Menampilkan ringkasan profil, statistik pesanan (total, selesai, pending), dan aktivitas.
- Aksi cepat:
  - Buat pesanan: `../../order.php` → redirect ke `pages/orders/create.php`.
  - Lihat pesanan: `../../my-orders.php`.
  - Edit profil: `../../edit-profile.php` (atau halaman profil di user).

### 5) Kelola Profil
- Lihat profil: `pages/user/show-profile.php` (hanya tampilan + ringkasan pesanan aktif/terbaru).
- Edit profil: `pages/user/profile.php`.
  - Ubah nama, username, email, telepon.
  - Upload foto profil ke `uploads/profiles/` (cek tipe file dan ukuran maks 5MB).
  - Ubah password opsional (wajib isi current password dan validasi kecocokan).

### 6) Membuat Pesanan
- Entry point: `order.php` (cek login lalu redirect ke `pages/orders/create.php`).
- Form utama: `pages/orders/create.php`.
  - Field: `service_type`, `package_type`, `title`, `description`, `budget`, `deadline`, `notes`, `whatsapp`.
  - Validasi nomor WhatsApp 10–13 digit.
  - Otomatis membuat tabel/kolom `orders` bila belum ada (schema guard di file ini).
  - Status awal `pending` dengan progress default via sistem status.
  - Integrasi WhatsApp: notifikasi admin dan konfirmasi ke pelanggan via `config/whatsapp_functions.php`.

### 7) Melihat Daftar Pesanan
- Opsi A (versi komprehensif + filter/paginasi): `my-orders.php` (root).
  - Filter by status, pencarian judul/deskripsi, dan paginasi.
  - Tautan aksi:
    - Edit (hanya `pending`): `edit-order.php?id=...` (root level).
    - Progress: `order-progress.php?id=...` → redirect ke `pages/orders/progress.php`.
    - Detail lengkap: `pages/orders/detail.php?id=...`.
- Opsi B (versi ringkas): `pages/orders/list.php` (tetap mensyaratkan login) untuk daftar cepat.

### 8) Detail Pesanan
- Halaman: `pages/orders/detail.php`.
- Menampilkan ringkas hingga deskripsi mendetail, catatan, kontak, budget, deadline, waktu pembuatan, dan update.
- Menarik informasi status dari `config/status_functions.php`:
  - `getStatusInfo($status)` untuk label, ikon, warna, dan persentase.
  - `getTimelineSteps($status)` untuk daftar tahapan dan indikator selesai/aktif.
- Aksi: Edit (jika `pending`), lihat progress (`pages/orders/progress.php`), kembali ke daftar (`my-orders.php`).

### 9) Edit Pesanan (Status Pending saja)
- Halaman: `pages/orders/edit.php`.
- Hanya bisa bila `status === 'pending'`.
- Field sesuai pembuatan pesanan, termasuk `whatsapp_number` dan `notes`.

### 10) Lacak Progress Pesanan (Timeline)
- Halaman: `pages/orders/progress.php`.
- Menampilkan progress keseluruhan dan timeline tahapan berdasarkan status saat ini (real-time percentage dari `getStatusInfo()`).
- Auto-refresh setiap 30 detik bila status belum `completed`.

### 11) Pengaturan Akun
- Halaman: `settings.php`.
- Fitur:
  - Ubah password (dengan verifikasi password saat ini).
  - Preferensi notifikasi: email/WhatsApp (kolom akan ditambahkan otomatis bila belum ada).
  - Ringkasan info akun + aksi cepat.

### 12) Logout
- Halaman: `pages/auth/logout.php`.
- Menghapus session + cookie, lalu redirect ke `index.php?message=logout_success`.

### 13) Alur Admin (Demo)
- Halaman: `pages/admin/orders.php`.
- Mode demo: tambahkan `?admin=demo` untuk akses UI (implementasi auth admin sebenarnya disarankan di produksi).
- Dapat mengubah status pesanan → memicu perubahan progress (via `updateOrderStatus()` di `config/status_functions.php`).
