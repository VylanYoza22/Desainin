<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field password harus diisi!';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Password saat ini salah!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Password berhasil diubah!';
        } else {
            $error = 'Gagal mengubah password!';
        }
        $stmt->close();
    }
}

// Handle notification preferences
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $whatsapp_notifications = isset($_POST['whatsapp_notifications']) ? 1 : 0;
    
    // Add notification columns if they don't exist
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_notifications TINYINT(1) DEFAULT 1");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_notifications TINYINT(1) DEFAULT 1");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(15) DEFAULT NULL");
    
    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, whatsapp_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $email_notifications, $whatsapp_notifications, $user_id);
    
    if ($stmt->execute()) {
        $success = 'Pengaturan notifikasi berhasil diperbarui!';
    } else {
        $error = 'Gagal memperbarui pengaturan notifikasi!';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/Style-Desainin-dark.css">
</head>

<body class="bg-black text-white font-sans min-h-screen">
    <!-- Animated Background -->
    <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
    <div class="particles fixed inset-0 -z-10 pointer-events-none" id="particles"></div>

    <!-- Navigation -->
    <nav class="bg-gray-900/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                        <i class="fas fa-palette mr-2"></i>Desainin
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-home mr-2"></i>Beranda
                    </a>
                    <a href="my-orders.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-list mr-2"></i>Pesanan Saya
                    </a>
                    <a href="pages/auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 py-8 section-spacing">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-cog mr-3"></i>Pengaturan
            </h1>
            <p class="text-gray-400">Kelola preferensi dan keamanan akun Anda</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Password Change Section -->
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                    <i class="fas fa-lock mr-2"></i>Ubah Password
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password Saat Ini</label>
                        <input type="password" name="current_password" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password Baru</label>
                        <input type="password" name="new_password" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>
                    
                    <button type="submit" name="change_password" class="w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                        <i class="fas fa-key mr-2"></i>Ubah Password
                    </button>
                </form>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                    <i class="fas fa-bell mr-2"></i>Pengaturan Notifikasi
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <h4 class="font-medium text-white">Notifikasi Email</h4>
                            <p class="text-sm text-gray-400">Terima update pesanan via email</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_notifications" class="sr-only peer" 
                                   <?php echo (isset($user['email_notifications']) && $user['email_notifications']) ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                        <div>
                            <h4 class="font-medium text-white">Notifikasi WhatsApp</h4>
                            <p class="text-sm text-gray-400">Terima update pesanan via WhatsApp</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="whatsapp_notifications" class="sr-only peer" 
                                   <?php echo (isset($user['whatsapp_notifications']) && $user['whatsapp_notifications']) ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>
                    
                    <button type="submit" name="update_notifications" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400/30">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                    </button>
                </form>
            </div>

            <!-- Account Information -->
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Informasi Akun
                </h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-400">Username</span>
                        <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-400">Email</span>
                        <span class="font-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-400">Bergabung Sejak</span>
                        <span class="font-medium"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="edit-profile.php" class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center block focus:outline-none focus:ring-2 focus:ring-green-400/30">
                        <i class="fas fa-edit mr-2"></i>Edit Profil
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                    <i class="fas fa-bolt mr-2"></i>Aksi Cepat
                </h3>
                
                <div class="space-y-3">
                    <a href="my-orders.php" class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center block focus:outline-none focus:ring-2 focus:ring-purple-400/30">
                        <i class="fas fa-list mr-2"></i>Lihat Pesanan Saya
                    </a>
                    
                    <a href="order.php" class="w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center block focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                        <i class="fas fa-plus mr-2"></i>Buat Pesanan Baru
                    </a>
                    
                    <a href="index.php" class="w-full bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center block focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/Desainin.js"></script>
</body>
</html>
