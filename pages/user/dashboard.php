<?php
session_start();
require_once '../../config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Hitung statistik orders dari tabel orders
$total_orders = 0;
$completed_projects = 0;
$pending_projects = 0;

// Cek apakah tabel orders ada
$check_orders = $conn->query("SHOW TABLES LIKE 'orders'");
if ($check_orders && $check_orders->num_rows > 0) {
    // Hitung total orders
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_orders = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Hitung completed projects
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $completed_projects = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Hitung pending projects
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_projects = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

// Statistik user
$stats = [
    'total_orders' => $total_orders,
    'completed_projects' => $completed_projects,
    'pending_projects' => $pending_projects,
    'member_since' => date('d M Y', strtotime($user['created_at']))
];

// Hitung feedback yang diberikan user
$feedback_count = 0;

// Cek struktur tabel feedback terlebih dahulu
$check_table = $conn->query("DESCRIBE feedback");
if ($check_table && $check_table->num_rows > 0) {
    $columns = [];
    while ($row = $check_table->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Cek kolom yang tersedia dan gunakan yang sesuai
    if (in_array('user_id', $columns)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } elseif (in_array('email', $columns)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
    } elseif (in_array('nama', $columns)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM feedback WHERE nama = ?");
        $stmt->bind_param("s", $user['full_name']);
    } else {
        // Jika tidak ada kolom yang cocok, set ke 0
        $feedback_count = 0;
        $stmt = null;
    }
    
    if ($stmt) {
        $stmt->execute();
        $feedback_count = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../../assets/js/Desainin.js"></script>
    
    <script>
        // WhatsApp support function for dashboard
        function openWhatsAppSupport() {
            const phoneNumber = '6288299154725';
            const message = 'Halo Admin Desainin! Saya membutuhkan bantuan support. Mohon bantuannya. Terima kasih!';
            const whatsappURL = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
            window.open(whatsappURL, '_blank');
        }
    </script>
    <link rel="stylesheet" href="../../assets/css/Style-Desainin-dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="min-h-screen p-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-16 h-16 rounded-full object-cover mr-4">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            Selamat datang, <?php echo htmlspecialchars($user['full_name']); ?>!
                        </h1>
                        <p class="text-gray-300">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="text-sm text-gray-400">Member sejak <?php echo $stats['member_since']; ?></p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="../../index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-home mr-2"></i>Beranda
                    </a>
                    <a href="../auth/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-effect rounded-xl p-6 text-center card-hover transition-all duration-300">
                <div class="text-3xl text-blue-400 mb-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-1"><?php echo $stats['total_orders']; ?></h3>
                <p class="text-gray-300">Total Pesanan</p>
            </div>

            <div class="glass-effect rounded-xl p-6 text-center card-hover transition-all duration-300">
                <div class="text-3xl text-amber-400 mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-1"><?php echo $stats['completed_projects']; ?></h3>
                <p class="text-gray-300">Proyek Selesai</p>
            </div>

            <div class="glass-effect rounded-xl p-6 text-center card-hover transition-all duration-300">
                <div class="text-3xl text-amber-400 mb-3">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-1"><?php echo $stats['pending_projects']; ?></h3>
                <p class="text-gray-300">Proyek Pending</p>
            </div>

            <div class="glass-effect rounded-xl p-6 text-center card-hover transition-all duration-300">
                <div class="text-3xl text-amber-400 mb-3">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-1"><?php echo $feedback_count; ?></h3>
                <p class="text-gray-300">Feedback Diberikan</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Info -->
            <div class="lg:col-span-2">
                <div class="glass-effect rounded-2xl p-6 shadow-2xl">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-user-circle mr-3 text-blue-400"></i>
                        Informasi Profil
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-id-card text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Nama Lengkap</span>
                            </div>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-user text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Username</span>
                            </div>
                            <span class="text-white font-medium">@<?php echo htmlspecialchars($user['username']); ?></span>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Email</span>
                            </div>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>

                        <?php if ($user['phone']): ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-phone text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Telepon</span>
                            </div>
                            <span class="text-white font-medium"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-calendar text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Bergabung</span>
                            </div>
                            <span class="text-white font-medium"><?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?></span>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-shield-alt text-gray-400 mr-3"></i>
                                <span class="text-gray-300">Status Akun</span>
                            </div>
                            <span class="text-amber-400 font-medium flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>Aktif
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-white/10">
                        <a href="../../edit-profile.php" class="bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-6 py-3 rounded-lg transition-all duration-300 transform hover:scale-105 inline-block">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Profil
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-6">
                <div class="glass-effect rounded-2xl p-6 shadow-2xl">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-bolt mr-3 text-amber-400"></i>
                        Aksi Cepat
                    </h2>
                    
                    <div class="space-y-3">
                        <a href="../../order.php" class="block w-full bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white p-4 rounded-lg transition-all duration-300 transform hover:scale-105 text-center">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Pesan Desain Baru
                        </a>

                        <a href="../../my-orders.php" class="block w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-800 hover:to-gray-900 text-white p-4 rounded-lg transition-all duration-300 transform hover:scale-105 text-center">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Pesanan Saya
                        </a>

                        <button onclick="openWhatsAppSupport()" class="block w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-800 hover:to-gray-900 text-white p-4 rounded-lg transition-all duration-300 transform hover:scale-105 text-center">
                            <i class="fas fa-headset mr-2"></i>
                            Hubungi Support
                        </button>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-effect rounded-2xl p-6 shadow-2xl">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-history mr-3 text-amber-400"></i>
                        Aktivitas Terbaru
                    </h2>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center p-3 bg-white/5 rounded-lg">
                            <i class="fas fa-user-plus text-blue-400 mr-3"></i>
                            <div>
                                <p class="text-white">Akun berhasil dibuat</p>
                                <p class="text-gray-400"><?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>

                        <?php if ($feedback_count > 0): ?>
                        <div class="flex items-center p-3 bg-white/5 rounded-lg">
                            <i class="fas fa-comment text-amber-400 mr-3"></i>
                            <div>
                                <p class="text-white">Memberikan feedback</p>
                                <p class="text-gray-400">Total <?php echo $feedback_count; ?> feedback</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animasi loading untuk stats
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.glass-effect h3');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = currentValue;
                }, 50);
            });
        });
    </script>
</body>
</html>
