
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
