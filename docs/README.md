# PKK2 -# 🎨 Desainin - Platform Jasa Desain Grafis & Video Editing Profesional

**Desainin** adalah website platform bisnis kreatif yang menyediakan layanan desain grafis dan video editing profesional. Dibangun dengan teknologi modern dan user experience yang optimal, website ini menghubungkan klien dengan layanan kreatif berkualitas tinggi melalui sistem order management yang terintegrasi dan user-friendly.

## 🌟 Deskripsi Lengkap

Desainin hadir sebagai solusi komprehensif untuk kebutuhan desain grafis dan video editing di era digital. Platform ini tidak hanya menampilkan portofolio karya-karya terbaik, tetapi juga menyediakan sistem pemesanan yang streamlined, tracking proyek real-time, dan manajemen klien yang efisien.

Website ini dirancang dengan pendekatan business-first, dimana setiap fitur dikembangkan untuk mendukung pertumbuhan bisnis kreatif. Dari landing page yang engaging dengan popup promo otomatis, hingga dashboard analytics yang memberikan insight mendalam tentang performa bisnis.

### 🎯 Target Audience
- **Bisnis & Startup** yang membutuhkan branding visual
- **Content Creator** yang memerlukan editing video profesional
- **E-commerce** yang butuh desain produk dan marketing material
- **Event Organizer** untuk kebutuhan desain promosi
- **Individual** yang ingin meningkatkan personal branding

## ✨ Fitur Utama & Keunggulan

### 🏠 **Landing Page Premium**
- **Hero Section** dengan animasi background gradient yang menarik
- **Portfolio Showcase** dengan filtering interaktif berdasarkan kategori
- **Testimonial Carousel** dengan sistem rating bintang dan foto profil
- **Pricing Packages** dengan detail layanan yang komprehensif
- **Popup Promo** otomatis dengan glassmorphism design dan smart redirect
- **Responsive Design** yang optimal di semua device

### 👤 **Sistem Autentikasi Lengkap**
- **User Registration** dengan validasi email dan password hashing
- **Secure Login** dengan session management dan remember me
- **Profile Management** dengan upload foto profil dan edit data personal
- **Password Security** menggunakan PHP password_hash() dan verification
- **Session Timeout** untuk keamanan maksimal

### 📋 **Order Management System**
- **Smart Order Form** dengan auto-calculation budget berdasarkan package
- **Service Categories**: Logo Design, Social Media Design, Video Editing, Web Design
- **Package Tiers**: Basic, Standard, Premium dengan pricing dinamis
- **Project Details** dengan title, description, deadline, dan notes
- **File Upload** untuk brief dan referensi (future enhancement)

### 📊 **Dashboard Analytics**
- **Real-time Statistics** dari database:
  - Total Pesanan (semua status)
  - Proyek Completed (revenue tracking)
  - Proyek Pending (workload management)
  - Feedback Diberikan (customer satisfaction)
- **Quick Actions** untuk akses cepat ke fitur utama
- **Recent Activity** dan notification system
- **Performance Metrics** dan growth indicators

### 🔄 **Project Tracking System**
- **Status Management**: Pending → In Progress → Completed → Cancelled
- **Timeline Tracking** dengan created_at dan updated_at timestamps
- **Deadline Monitoring** dengan visual indicators
- **Client Communication** melalui notes dan status updates
- **History Log** untuk audit trail

### ⭐ **Advanced Feedback System**
- **FIFO Management** (maksimal 3 testimoni untuk performa optimal)
- **Star Rating** dengan visual feedback
- **User Integration** dengan foto profil dan nama lengkap
- **Moderation System** untuk quality control
- **SEO Optimization** untuk social proof

### 🎨 **Premium UI/UX Design**
- **Golden Theme** konsisten di seluruh website
- **Glassmorphism Effects** pada popup dan cards
- **Smooth Animations** dengan CSS transitions dan transforms
- **Micro-interactions** untuk enhanced user experience
- **Dark Mode** optimized untuk modern aesthetics
- **Typography Hierarchy** yang professional

## 🛠 Teknologi & Arsitektur

### **Backend Architecture**
- **PHP 7.4+** dengan OOP principles dan best practices
- **MySQLi** dengan prepared statements untuk SQL injection prevention
- **Session Management** dengan secure cookie handling
- **File Upload System** dengan validation dan security measures
- **Error Handling** dengan logging dan user-friendly messages
- **Database Optimization** dengan proper indexing dan query optimization

### **Frontend Technology Stack**
- **HTML5** dengan semantic markup dan accessibility
- **CSS3** dengan modern features (Grid, Flexbox, Custom Properties)
- **JavaScript ES6+** dengan modular architecture
- **Tailwind CSS** untuk rapid UI development
- **Font Awesome 6.4.0** untuk consistent iconography
- **Responsive Design** dengan mobile-first approach

### **Database Design**
```sql
-- Optimized database schema dengan foreign keys dan constraints
Users Table: Authentication dan profile management
Orders Table: Complete order lifecycle dengan status tracking  
Feedback Table: Testimoni system dengan user integration
```

### 2. Konfigurasi Database
Edit file `config.php`:
```php
$host = "localhost";
$user = "root";         
$pass = "";             
$db   = "desainin_db";
```

### 3. Setup XAMPP
1. Copy folder `PKK2` ke `C:\xampp\htdocs\`
2. Start Apache dan MySQL di XAMPP Control Panel
3. Buka browser: `http://localhost/PKK2`

## 👤 Akun Demo

**Username:** admin  
**Password:** admin123

## 🎨 Fitur Teknis

### Sistem Login/Register
- Password hashing dengan `password_hash()`
- Session management untuk keamanan
- Validasi form client & server side
- Redirect otomatis setelah login

### Promotional Popup
- Muncul 2 detik setelah page load
- Session storage untuk mencegah spam
- WhatsApp integration langsung
- Dark theme yang konsisten

### Responsive Design
- Mobile-first approach
- Sidebar navigation yang adaptive
- Optimized untuk semua screen size

## 📱 Kontak

**WhatsApp:** +62 882-9915-4725  
**Email:** admin@desainin.com

## 🔧 Teknologi

- **Frontend:** HTML5, CSS3, JavaScript, TailwindCSS
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Server:** Apache (XAMPP)

## 📝 Changelog

### v1.0.0
- ✅ Sistem login/register lengkap
- ✅ Dashboard user dengan statistik
- ✅ Promotional popup dengan session management
- ✅ WhatsApp integration
- ✅ Dark theme responsive design
- ✅ Database integration dengan MySQL

---

**Dibuat oleh:** Vylan Yoza Sinaga  
**Tahun:** 2025
