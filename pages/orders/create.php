<?php
/**
 * =====================================================
 * ORDER MANAGEMENT SYSTEM
 * =====================================================
 * 
 * Purpose: Handle order creation with WhatsApp integration
 * Features: User authentication, form validation, database operations, WhatsApp notifications
 * Author: Desainin Development Team
 * Version: 2.0
 * Last Updated: 2025-01-28
 */

// =====================================================
// PRICING STRUCTURE
// =====================================================
$pricing = [
    'video_editing' => [
        'basic' => 50000,
        'standard' => 100000,
        'premium' => 200000
    ],
    'graphic_design' => [
        'basic' => 35000,
        'standard' => 75000,
        'premium' => 150000
    ],
    'social_media' => [
        'basic' => 40000,
        'standard' => 80000,
        'premium' => 160000
    ],
    'presentation' => [
        'basic' => 45000,
        'standard' => 90000,
        'premium' => 180000
    ]
];

// =====================================================
// INITIALIZATION & SECURITY
// =====================================================
session_start();
require_once '../../config/database.php';
require_once '../../config/whatsapp_functions.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Initialize variables
$success = '';
$error = '';

// =====================================================
// USER DATA RETRIEVAL
// =====================================================
$stmt = $conn->prepare("SELECT id, username, full_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// =====================================================
// FORM PROCESSING
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Sanitize input data
    $user_id = $_SESSION['user_id'];
    $service_type = $_POST['service_type'];
    $package_type = $_POST['package_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = $_POST['budget'];
    $deadline = $_POST['deadline'];
    $notes = trim($_POST['notes']);
    // Use phone number from user profile
    $whatsapp = trim($user['phone'] ?? '');
    $formattedPhone = '';
    $design_reference = null;
    
    // =====================================================
    // INPUT VALIDATION
    // =====================================================
    if (empty($title) || empty($description)) {
        $error = "Semua field wajib harus diisi.";
    } else if (empty($whatsapp)) {
        $error = "Nomor WhatsApp pada profil Anda belum diisi. Silakan lengkapi nomor di profil terlebih dahulu.";
    } else {
        // Normalize number for WhatsApp and validate basic length
        $formattedPhone = validateWhatsAppNumber($whatsapp);
        $digits = preg_replace('/[^0-9]/', '', $formattedPhone);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            $error = "Format nomor WhatsApp pada profil tidak valid. Perbarui nomor di profil Anda.";
        }
        
        // Validate budget matches pricing structure
        if (isset($pricing[$service_type][$package_type])) {
            $expected_price = $pricing[$service_type][$package_type];
            if ($budget != $expected_price) {
                $error = "Budget tidak sesuai dengan harga paket yang dipilih. Harga seharusnya Rp " . number_format($expected_price, 0, ',', '.');
            }
        } else {
            $error = "Kombinasi layanan dan paket tidak valid.";
        }
    }
    
    // =====================================================
    // FILE UPLOAD HANDLING
    // =====================================================
    if (empty($error) && isset($_FILES['design_reference']) && $_FILES['design_reference']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['design_reference']['type'];
        $file_size = $_FILES['design_reference']['size'];
        $file_tmp = $_FILES['design_reference']['tmp_name'];
        $file_name = $_FILES['design_reference']['name'];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $error = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
        }
        // Validate file size
        else if ($file_size > $max_size) {
            $error = "Ukuran file terlalu besar. Maksimal 5MB.";
        }
        // Upload file
        else {
            // Create uploads directory if not exists
            $upload_dir = '../../uploads/design_references/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = 'design_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $design_reference = 'uploads/design_references/' . $unique_filename;
            } else {
                $error = "Gagal mengupload file. Silakan coba lagi.";
            }
        }
    }

    if (empty($error)) {
        
        // =====================================================
        // DATABASE SCHEMA VALIDATION
        // =====================================================
        
        // Check if orders table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'orders'");
        if (!$check_table || $check_table->num_rows == 0) {
            // Create orders table with complete schema
            $create_table = "
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                service_type ENUM('video_editing', 'graphic_design', 'social_media', 'presentation') NOT NULL,
                package_type ENUM('basic', 'standard', 'premium') NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                budget DECIMAL(10,2) NOT NULL,
                deadline DATE,
                notes TEXT,
                design_reference VARCHAR(255),
                whatsapp_number VARCHAR(15),
                status ENUM('pending', 'confirmed', 'payment_pending', 'payment_confirmed', 'in_progress', 'review', 'final_review', 'completed', 'cancelled') DEFAULT 'pending',
                progress_percentage INT DEFAULT 10,
                status_description VARCHAR(255) DEFAULT 'Pesanan sedang diproses',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($create_table)) {
                $error = "Error creating orders table: " . $conn->error;
            }
        } else {
            // Ensure all required columns exist
            $columns_to_check = [
                'whatsapp_number' => "ALTER TABLE orders ADD COLUMN whatsapp_number VARCHAR(15) AFTER notes",
                'progress_percentage' => "ALTER TABLE orders ADD COLUMN progress_percentage INT DEFAULT 10 AFTER status",
                'status_description' => "ALTER TABLE orders ADD COLUMN status_description VARCHAR(255) DEFAULT 'Pesanan sedang diproses' AFTER progress_percentage",
                'design_reference' => "ALTER TABLE orders ADD COLUMN design_reference VARCHAR(255) AFTER notes"
            ];
            
            foreach ($columns_to_check as $column => $alter_sql) {
                $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE '$column'");
                if ($check_column->num_rows == 0) {
                    $conn->query($alter_sql);
                }
            }
        }
        
        // =====================================================
        // ORDER INSERTION
        // =====================================================
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO orders (user_id, service_type, package_type, title, description, budget, deadline, notes, design_reference, whatsapp_number, status, progress_percentage, status_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 10, 'Pesanan baru diterima')");
            $stmt->bind_param("issssdssss", $user_id, $service_type, $package_type, $title, $description, $budget, $deadline, $notes, $design_reference, $formattedPhone);
            
            if ($stmt->execute()) {
                $order_id = $conn->insert_id;
                $success = "Pesanan berhasil dibuat dengan ID: #" . $order_id;
                
                // =====================================================
                // WHATSAPP NOTIFICATIONS
                // =====================================================
                try {
                    // Prepare order data for WhatsApp
                    $orderData = [
                        'order_id' => $order_id,
                        'nama' => $user['full_name'],
                        'email' => $user['email'],
                        'whatsapp' => $formattedPhone,
                        'service_type' => $service_type,
                        'package_type' => $package_type,
                        'title' => $title,
                        'description' => $description,
                        'budget' => $budget,
                        'deadline' => $deadline,
                        'notes' => $notes
                    ];
                    
                    // Send notification to admin
                    $whatsappResult = sendOrderNotification($orderData);
                    logWhatsAppActivity(
                        ADMIN_WHATSAPP, 
                        "Order notification #" . $order_id, 
                        $whatsappResult['success'] ? 'SUCCESS' : 'FAILED',
                        $whatsappResult['response']
                    );
                    
                    // Send confirmation to customer
                    $customerPhone = $formattedPhone;
                    $confirmResult = sendOrderConfirmationToCustomer($customerPhone, $orderData);
                    logWhatsAppActivity(
                        $customerPhone, 
                        "Order confirmation #" . $order_id, 
                        $confirmResult['success'] ? 'SUCCESS' : 'FAILED',
                        $confirmResult['response']
                    );
                    
                } catch (Exception $e) {
                    // Log error but don't fail the order process
                    error_log("WhatsApp Error: " . $e->getMessage());
                }
                
            } else {
                $error = "Terjadi kesalahan saat membuat pesanan.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <!-- =====================================================
         META TAGS & TITLE
         ===================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pesanan - Desainin | Jasa Edit Video & Desain Online</title>
    <meta name="description" content="Buat pesanan layanan desain dan video editing profesional dengan sistem tracking real-time dan notifikasi WhatsApp">
    
    <!-- =====================================================
         EXTERNAL RESOURCES
         ===================================================== -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/Style-Desainin-dark.css">
    
    <!-- =====================================================
         CUSTOM STYLES
         ===================================================== -->
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .form-section {
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .label-required::after {
            content: " *";
            color: #ef4444;
            animation: pulse 2s infinite;
        }
        
        /* Modern Input Styles */
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        textarea {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.15),
                        0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        /* Select Styling */
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23f59e0b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }
        
        select option {
            padding: 0.75rem;
            background: #1a1a2e;
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3),
                        0 0 30px rgba(245, 158, 11, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            box-shadow: 0 12px 35px rgba(245, 158, 11, 0.5),
                        0 0 50px rgba(245, 158, 11, 0.2);
            transform: translateY(-3px) scale(1.02);
        }
        
        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }
        
        /* Glass Card Enhancement */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3),
                        0 0 30px rgba(245, 158, 11, 0.1);
        }
        
        /* Section Headers */
        .section-header {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.05));
            border-radius: 1rem;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .section-header i {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Pricing Card Styles */
        .pricing-card {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .pricing-card:hover::before {
            opacity: 1;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px) scale(1.02);
            border-color: rgba(245, 158, 11, 0.5);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
        }
        
        /* Breakdown Card */
        .breakdown-card {
            animation: fadeInUp 0.5s ease-out;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.05));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(245, 158, 11, 0.3);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.15);
        }
        
        /* File Upload Style */
        input[type="file"]::file-selector-button {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        input[type="file"]::file-selector-button:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.3), rgba(245, 158, 11, 0.2));
            transform: translateY(-2px);
        }
        
        /* Image Preview */
        #image-preview img {
            transition: all 0.3s;
            animation: fadeInUp 0.5s ease-out;
        }
        
        #image-preview img:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #f59e0b, #d97706);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #fbbf24, #f59e0b);
        }
        
        /* Alert Messages */
        .alert-success, .alert-error {
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Loading State */
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Success Glow */
        .success-glow {
            animation: successPulse 2s ease-in-out infinite;
        }
        
        @keyframes successPulse {
            0%, 100% {
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
            }
            50% {
                box-shadow: 0 0 40px rgba(16, 185, 129, 0.6);
            }
        }
    </style>
</head>

<body class="bg-black text-white font-sans min-h-screen">
    <!-- =====================================================
         ANIMATED BACKGROUND
         ===================================================== -->
    <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
    <div class="particles fixed inset-0 -z-10 pointer-events-none" id="particles"></div>

    <!-- =====================================================
         NAVIGATION BAR
         ===================================================== -->
    <nav class="bg-gray-900/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="../../index.php" class="text-2xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                        <i class="fas fa-palette mr-2"></i>Desainin
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../../my-orders.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-list mr-2"></i>Pesanan Saya
                    </a>
                    <a href="../auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- =====================================================
         MAIN CONTENT CONTAINER
         ===================================================== -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- =====================================================
             PAGE HEADER
             ===================================================== -->
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-plus-circle mr-3"></i>Buat Pesanan Baru
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Isi form di bawah untuk membuat pesanan layanan desain profesional dengan sistem tracking real-time
            </p>
        </div>

        <!-- =====================================================
             PRICING TABLE
             ===================================================== -->
        <div class="glass-card rounded-3xl p-8 shadow-2xl mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="section-header text-2xl font-bold text-white">
                    <i class="fas fa-tags text-amber-400"></i>
                    <span>Daftar Harga Paket</span>
                </h2>
                <button onclick="togglePricing()" 
                        class="text-amber-400 hover:text-amber-300 text-sm flex items-center gap-2 transition-all hover:gap-3 bg-amber-500/10 hover:bg-amber-500/20 px-4 py-2 rounded-xl border border-amber-500/20">
                    <i class="fas fa-info-circle"></i>
                    <span id="toggle-text">Lihat Detail</span>
                </button>
            </div>
            
            <div id="pricing-detail" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($pricing as $service => $packages): ?>
                        <div class="pricing-card bg-white/5 border border-white/10 rounded-2xl p-5 shadow-lg">
                            <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
                                <?php
                                $icons = [
                                    'video_editing' => '🎬',
                                    'graphic_design' => '🎨',
                                    'social_media' => '📱',
                                    'presentation' => '📊'
                                ];
                                echo $icons[$service] . ' ';
                                echo ucfirst(str_replace('_', ' ', $service));
                                ?>
                            </h3>
                            <div class="space-y-2 text-sm">
                                <?php foreach ($packages as $package => $price): ?>
                                    <div class="flex justify-between items-center text-gray-300 hover:text-white transition-colors">
                                        <span class="capitalize"><?php echo $package; ?></span>
                                        <span class="font-semibold text-amber-400">Rp <?php echo number_format($price, 0, ',', '.'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-500/30 text-blue-300 px-4 py-2 rounded-full text-sm">
                        <i class="fas fa-lightbulb animate-pulse"></i>
                        <span>Harga akan otomatis terisi sesuai paket yang Anda pilih di form</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =====================================================
             ALERT MESSAGES
             ===================================================== -->
        <?php if ($success): ?>
            <div class="alert-success bg-green-600/20 border border-green-600/50 text-green-400 px-6 py-5 rounded-2xl mb-8 backdrop-blur-sm success-glow shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="flex-1 ml-4">
                        <h4 class="font-bold text-lg">Pesanan Berhasil Dibuat!</h4>
                        <p class="text-sm text-green-300 mt-1"><?php echo $success; ?></p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-green-600/30">
                    <a href="../../my-orders.php" class="inline-flex items-center gap-2 bg-green-600/30 hover:bg-green-600/40 text-green-200 px-5 py-2.5 rounded-xl transition-all hover:gap-3 font-semibold">
                        <span>Lihat pesanan saya</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error bg-red-600/20 border border-red-600/50 text-red-400 px-6 py-5 rounded-2xl mb-8 backdrop-blur-sm shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="font-bold text-lg">Terjadi Kesalahan</h4>
                        <p class="text-sm text-red-300 mt-1"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             ORDER FORM
             ===================================================== -->
        <div class="glass-card rounded-3xl p-8 md:p-10 shadow-2xl">
            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <!-- Service & Package Selection Section -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-cogs text-amber-400"></i>
                        <span>Pilih Layanan & Paket</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Service Type -->
                        <div class="input-group">
                            <label for="service_type" class="block text-sm font-medium text-gray-300 mb-2 label-required">
                                <i class="fas fa-palette mr-2"></i>Jenis Layanan
                            </label>
                            <select name="service_type" id="service_type" required 
                                    class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300" 
                                    style="color: white;">
                                <option value="" disabled selected style="color: #9CA3AF;">Pilih Layanan</option>
                                <option value="video_editing" style="color: #f59e0b; background: #1a1a2e;">🎬 Video Editing</option>
                                <option value="graphic_design" style="color: #f59e0b; background: #1a1a2e;">🎨 Graphic Design</option>
                                <option value="social_media" style="color: #f59e0b; background: #1a1a2e;">📱 Social Media Content</option>
                                <option value="presentation" style="color: #f59e0b; background: #1a1a2e;">📊 Presentation Design</option>
                            </select>
                        </div>

                        <!-- Package Type -->
                        <div class="input-group">
                            <label for="package_type" class="block text-sm font-medium text-gray-300 mb-2 label-required">
                                <i class="fas fa-box mr-2"></i>Paket Layanan
                            </label>
                            <select name="package_type" id="package_type" required 
                                    class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300" 
                                    style="color: white;">
                                <option value="" disabled selected style="color: #9CA3AF;">Pilih Paket</option>
                                <option value="basic" style="color: #f59e0b; background: #1a1a2e;">💼 Basic</option>
                                <option value="standard" style="color: #f59e0b; background: #1a1a2e;">⭐ Standard</option>
                                <option value="premium" style="color: #f59e0b; background: #1a1a2e;">👑 Premium</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Project Details Section -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-project-diagram text-amber-400"></i>
                        <span>Detail Project</span>
                    </h3>
                    
                    <!-- Project Title -->
                    <div class="input-group">
                        <label for="title" class="block text-sm font-medium text-gray-300 mb-2 label-required">
                            <i class="fas fa-heading mr-2"></i>Judul Project
                        </label>
                        <input type="text" name="title" id="title" required 
                               class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300" 
                               placeholder="Masukkan judul project yang jelas dan deskriptif">
                    </div>

                    <!-- Project Description -->
                    <div class="input-group">
                        <label for="description" class="block text-sm font-medium text-gray-300 mb-2 label-required">
                            <i class="fas fa-align-left mr-2"></i>Deskripsi Project
                        </label>
                        <textarea name="description" id="description" rows="5" required 
                                  class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300 resize-y" 
                                  placeholder="Jelaskan detail project yang Anda inginkan, termasuk style, warna, konsep, dan requirement khusus lainnya..."></textarea>
                    </div>
                </div>

                <!-- Budget & Timeline Section -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-calculator text-amber-400"></i>
                        <span>Budget & Timeline</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Budget -->
                        <div class="input-group">
                            <label for="budget" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-money-bill mr-2"></i>Budget (Rp)
                            </label>
                            <input type="number" name="budget" id="budget" min="0" step="1000" readonly
                                   class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300 cursor-not-allowed" 
                                   placeholder="Pilih layanan & paket">
                            <p class="text-xs text-gray-400 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Harga otomatis sesuai paket yang dipilih
                            </p>
                        </div>

                        <!-- Deadline -->
                        <div class="input-group">
                            <label for="deadline" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Deadline
                            </label>
                            <input type="date" name="deadline" id="deadline" 
                                   class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300">
                        </div>
                    </div>
                    
                    <!-- Price Breakdown -->
                    <div id="price-breakdown" class="breakdown-card mt-6 rounded-2xl p-6 hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-bold text-amber-400 flex items-center gap-2">
                                <i class="fas fa-receipt"></i>
                                <span>Rincian Harga</span>
                            </h4>
                            <span class="text-xs bg-amber-500/30 text-amber-300 px-3 py-1.5 rounded-full font-semibold flex items-center gap-2">
                                <i class="fas fa-robot"></i>
                                Auto-calculated
                            </span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-300">
                                <span id="selected-service">-</span>
                                <span id="service-price">-</span>
                            </div>
                            <div class="flex justify-between text-gray-300">
                                <span id="selected-package">-</span>
                                <span id="package-indicator">-</span>
                            </div>
                            <div class="border-t border-amber-500/30 my-2 pt-2"></div>
                            <div class="flex justify-between text-white font-bold text-lg">
                                <span>Total Budget</span>
                                <span id="total-price" class="text-amber-400">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section (uses profile phone) -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-phone text-amber-400"></i>
                        <span>Informasi Kontak</span>
                    </h3>
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <p class="text-sm text-gray-300">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Nomor WhatsApp yang digunakan berasal dari profil Anda:
                            <span class="font-semibold text-white">
                                <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Belum diisi'; ?>
                            </span>
                        </p>
                        <p class="text-xs text-gray-400 mt-2">
                            Untuk mengubah nomor, silakan perbarui di <a href="../../edit-profile.php" class="text-amber-400 hover:text-amber-300 underline">Edit Profil</a>.
                        </p>
                    </div>
                </div>

                <!-- Design Reference Section -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-image text-amber-400"></i>
                        <span>Contoh Desain Referensi</span>
                    </h3>
                    
                    <div class="input-group">
                        <label for="design_reference" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-upload mr-2"></i>Upload Foto Contoh Desain (Opsional)
                        </label>
                        <div class="relative">
                            <input type="file" name="design_reference" id="design_reference" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                   class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-500/20 file:text-amber-400 file:cursor-pointer hover:file:bg-amber-500/30" 
                                   onchange="previewImage(this)">
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Format: JPG, PNG, GIF, WebP | Maksimal: 5MB
                        </p>
                        <!-- Image Preview -->
                        <div id="image-preview" class="mt-4 hidden">
                            <p class="text-sm text-gray-300 mb-2">Preview:</p>
                            <img id="preview-img" src="" alt="Preview" class="max-w-full h-auto rounded-xl border border-white/20 max-h-64 object-contain">
                        </div>
                    </div>
                </div>

                <!-- Additional Notes Section -->
                <div class="form-section">
                    <h3 class="section-header text-xl font-semibold mb-6">
                        <i class="fas fa-sticky-note text-amber-400"></i>
                        <span>Catatan Tambahan</span>
                    </h3>
                    
                    <div class="input-group">
                        <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-comment mr-2"></i>Catatan atau Permintaan Khusus
                        </label>
                        <textarea name="notes" id="notes" rows="4" 
                                  class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300 resize-y" 
                                  placeholder="Tambahkan catatan khusus, referensi, atau permintaan spesifik lainnya (opsional)"></textarea>
                    </div>
                </div>

                <!-- Submit Button Section -->
                <div class="form-section">
                    <div class="flex justify-center pt-6">
                        <button type="submit" 
                                class="btn-primary bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-600 hover:to-amber-700 text-black font-bold py-5 px-16 rounded-full transition-all duration-300 flex items-center gap-3 border border-amber-300/50 text-lg shadow-2xl">
                            <i class="fas fa-paper-plane text-xl"></i>
                            <span>Buat Pesanan Sekarang</span>
                            <i class="fas fa-arrow-right text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="text-center mt-6">
                        <div class="inline-flex items-center gap-2 bg-green-500/10 border border-green-500/30 text-green-300 px-6 py-3 rounded-full text-sm">
                            <i class="fas fa-shield-alt text-green-400"></i>
                            <span>Pesanan Anda akan diproses dalam 1x24 jam dan Anda akan mendapat notifikasi WhatsApp</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================
         FOOTER
         ===================================================== -->
    <footer class="bg-gray-900/50 border-t border-gray-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="text-center">
                <div class="flex justify-center items-center mb-4">
                    <div class="text-2xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                        Desainin
                    </div>
                </div>
                <p class="text-gray-400 text-sm">
                    &copy; 2024 Desainin. Semua hak dilindungi. | Jasa Edit Video & Desain Profesional
                </p>
            </div>
        </div>
    </footer>

    <!-- =====================================================
         JAVASCRIPT
         ===================================================== -->
    <script src="../../assets/js/Desainin.js"></script>
    
    <!-- Form Enhancement Script -->
    <script>
        // Pricing data from PHP
        const pricing = <?php echo json_encode($pricing); ?>;
        
        // Service and package name mappings
        const serviceNames = {
            'video_editing': '🎬 Video Editing',
            'graphic_design': '🎨 Graphic Design',
            'social_media': '📱 Social Media Content',
            'presentation': '📊 Presentation Design'
        };
        
        const packageNames = {
            'basic': '💼 Paket Basic',
            'standard': '⭐ Paket Standard',
            'premium': '👑 Paket Premium'
        };
        
        // Update budget based on selected service and package
        function updateBudget() {
            const serviceType = document.getElementById('service_type').value;
            const packageType = document.getElementById('package_type').value;
            const budgetInput = document.getElementById('budget');
            const breakdown = document.getElementById('price-breakdown');
            
            if (serviceType && packageType && pricing[serviceType] && pricing[serviceType][packageType]) {
                const price = pricing[serviceType][packageType];
                budgetInput.value = price;
                
                // Update breakdown display
                document.getElementById('selected-service').textContent = serviceNames[serviceType];
                document.getElementById('service-price').textContent = 'Rp ' + price.toLocaleString('id-ID');
                document.getElementById('selected-package').textContent = packageNames[packageType];
                document.getElementById('package-indicator').textContent = '✓ Terpilih';
                document.getElementById('total-price').textContent = 'Rp ' + price.toLocaleString('id-ID');
                
                // Show breakdown with animation
                breakdown.classList.remove('hidden');
                breakdown.classList.add('animate-fade-in');
            } else {
                budgetInput.value = '';
                budgetInput.placeholder = 'Pilih layanan & paket';
                breakdown.classList.add('hidden');
            }
        }
        
        // Add event listeners
        document.getElementById('service_type').addEventListener('change', updateBudget);
        document.getElementById('package_type').addEventListener('change', updateBudget);
        
        // Toggle pricing detail function
        function togglePricing() {
            const detail = document.getElementById('pricing-detail');
            const toggleText = document.getElementById('toggle-text');
            
            if (detail.classList.contains('hidden')) {
                detail.classList.remove('hidden');
                setTimeout(() => {
                    detail.style.opacity = '1';
                    detail.style.transform = 'translateY(0)';
                }, 10);
                toggleText.textContent = 'Sembunyikan Detail';
            } else {
                detail.style.opacity = '0';
                detail.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    detail.classList.add('hidden');
                }, 300);
                toggleText.textContent = 'Lihat Detail';
            }
        }
        
        // Initialize pricing detail styles
        document.addEventListener('DOMContentLoaded', function() {
            const detail = document.getElementById('pricing-detail');
            detail.style.transition = 'all 0.3s ease-out';
            detail.style.opacity = '0';
            detail.style.transform = 'translateY(-10px)';
        });
        
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.add('hidden');
            }
        }
        
        // Auto-resize textarea
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
        
        // Nomor WhatsApp mengikuti profil. Tidak ada input nomor pada form ini.
        
        // Form validation feedback
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi.');
            }
        });
    </script>
</body>
</html>
