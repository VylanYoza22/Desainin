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
$stmt = $conn->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
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
    $whatsapp = trim($_POST['whatsapp']);
    
    // =====================================================
    // INPUT VALIDATION
    // =====================================================
    if (empty($title) || empty($description) || empty($whatsapp)) {
        $error = "Semua field wajib harus diisi.";
    } else if (!preg_match('/^[0-9]{10,13}$/', $whatsapp)) {
        $error = "Format nomor WhatsApp tidak valid. Gunakan 10-13 digit angka.";
    } else {
        
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
                'status_description' => "ALTER TABLE orders ADD COLUMN status_description VARCHAR(255) DEFAULT 'Pesanan sedang diproses' AFTER progress_percentage"
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
            $stmt = $conn->prepare("INSERT INTO orders (user_id, service_type, package_type, title, description, budget, deadline, notes, whatsapp_number, status, progress_percentage, status_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 10, 'Pesanan baru diterima')");
            $stmt->bind_param("issssdsss", $user_id, $service_type, $package_type, $title, $description, $budget, $deadline, $notes, $whatsapp);
            
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
                        'whatsapp' => $whatsapp,
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
                    $customerPhone = validateWhatsAppNumber($whatsapp);
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
        .form-section {
            margin-bottom: 2rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .label-required::after {
            content: " *";
            color: #ef4444;
        }
        
        select option {
            padding: 0.75rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            transform: translateY(-2px);
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
             ALERT MESSAGES
             ===================================================== -->
        <?php if ($success): ?>
            <div class="bg-green-600/20 border border-green-600/50 text-green-400 px-6 py-4 rounded-xl mb-6 backdrop-blur-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-xl mr-3"></i>
                    <div class="flex-1">
                        <h4 class="font-semibold">Pesanan Berhasil Dibuat!</h4>
                        <p class="text-sm text-green-300 mt-1"><?php echo $success; ?></p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-green-600/30">
                    <a href="../../my-orders.php" class="inline-flex items-center text-green-300 hover:text-green-200 underline transition-colors">
                        <i class="fas fa-arrow-right mr-2"></i>Lihat pesanan saya
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-600/20 border border-red-600/50 text-red-400 px-6 py-4 rounded-xl mb-6 backdrop-blur-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-xl mr-3"></i>
                    <div>
                        <h4 class="font-semibold">Terjadi Kesalahan</h4>
                        <p class="text-sm text-red-300 mt-1"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             ORDER FORM
             ===================================================== -->
        <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-3xl p-8 shadow-2xl">
            <form method="POST" class="space-y-8">
                
                <!-- Service & Package Selection Section -->
                <div class="form-section">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-cogs mr-2"></i>Pilih Layanan & Paket
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
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-project-diagram mr-2"></i>Detail Project
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
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-calculator mr-2"></i>Budget & Timeline
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Budget -->
                        <div class="input-group">
                            <label for="budget" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-money-bill mr-2"></i>Budget (Rp)
                            </label>
                            <input type="number" name="budget" id="budget" min="0" step="1000" 
                                   class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300" 
                                   placeholder="50000">
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
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-phone mr-2"></i>Informasi Kontak
                    </h3>
                    
                    <!-- WhatsApp Number -->
                    <div class="input-group">
                        <label for="whatsapp" class="block text-sm font-medium text-gray-300 mb-2 label-required">
                            <i class="fab fa-whatsapp mr-2"></i>Nomor WhatsApp
                        </label>
                        <input type="tel" name="whatsapp" id="whatsapp" required pattern="[0-9]{10,13}" 
                               class="w-full p-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-300" 
                               placeholder="081234567890">
                        <p class="text-xs text-gray-400 mt-2 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Format: 10-13 digit angka tanpa spasi atau tanda hubung
                        </p>
                    </div>
                </div>

                <!-- Additional Notes Section -->
                <div class="form-section">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-sticky-note mr-2"></i>Catatan Tambahan
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
                                class="btn-primary bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-600 hover:to-amber-700 text-black font-bold py-4 px-12 rounded-full transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/30 flex items-center gap-3 border border-amber-300/50 text-lg">
                            <i class="fas fa-paper-plane text-xl"></i>
                            Buat Pesanan Sekarang
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Pesanan Anda akan diproses dalam 1x24 jam dan Anda akan mendapat notifikasi WhatsApp
                        </p>
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
        // Auto-resize textarea
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
        
        // Phone number formatting
        document.getElementById('whatsapp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 13) {
                value = value.slice(0, 13);
            }
            e.target.value = value;
        });
        
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
