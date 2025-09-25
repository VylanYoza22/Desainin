<?php
/**
 * Show Profile Page
 * Display user profile information with active orders
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/helpers.php';
require_once '../../config/error_handler.php';
require_once '../../config/status_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's active orders (not completed or cancelled)
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(*) OVER() as total_orders,
           SUM(CASE WHEN o.status IN ('completed', 'delivered') THEN 1 ELSE 0 END) OVER() as completed_orders
    FROM orders o 
    WHERE o.user_id = ? 
    AND o.status NOT IN ('cancelled', 'delivered') 
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get order statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status NOT IN ('completed', 'delivered', 'cancelled') THEN 1 ELSE 0 END) as active_orders,
        SUM(budget) as total_spent
    FROM orders 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent activity (last 5 orders)
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Profil Saya';
$pageDescription = 'Lihat profil dan pesanan aktif Anda';
$cssPath = '../../assets/css/Style-Desainin-dark.css';
$rootPath = '../../';
$additionalHead = '
<style>
    .profile-container {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        min-height: 100vh;
    }
    .glass-effect {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(251, 191, 36, 0.5);
    }
    .stat-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }
    .order-card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        transition: all 0.3s ease;
    }
    .order-card:hover {
        background: rgba(255, 255, 255, 0.12);
        transform: translateY(-1px);
    }
    .progress-ring {
        transform: rotate(-90deg);
    }
</style>';
include '../../includes/header.php';
?>

<div class="profile-container min-h-screen py-8">
    <?php include '../../includes/navigation.php'; ?>
    
    <div class="container mx-auto px-4 pt-20">
        <!-- Profile Header -->
        <div class="glass-effect rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="text-center mb-8">
                <!-- Profile Picture with Camera Icon -->
                <div class="relative inline-block mb-6">
                    <?php 
                    // Force refresh user data from database
                    $fresh_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                    $fresh_stmt->bind_param("i", $user_id);
                    $fresh_stmt->execute();
                    $fresh_result = $fresh_stmt->get_result();
                    $fresh_user = $fresh_result->fetch_assoc();
                    $fresh_stmt->close();
                    
                    $currentProfilePic = $fresh_user['profile_picture'];
                    ?>
                    <?php if ($currentProfilePic): ?>
                        <img src="<?php echo htmlspecialchars($currentProfilePic); ?>" 
                             alt="Profile Picture" 
                             class="profile-avatar mx-auto"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                             onload="this.style.display='block'; this.nextElementSibling.style.display='none';">
                    <?php endif; ?>
                    <div class="profile-avatar mx-auto bg-gradient-to-r from-amber-500 to-yellow-600 flex items-center justify-center text-white text-4xl font-bold" style="<?php echo $currentProfilePic ? 'display: none;' : ''; ?>">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    
                    <!-- Camera Icon -->
                    <a href="profile.php" class="absolute bottom-2 right-2 w-10 h-10 bg-amber-600 hover:bg-amber-700 rounded-full flex items-center justify-center text-white shadow-lg transition-colors">
                        <i class="fas fa-camera text-sm"></i>
                    </a>
                </div>
                
                <p class="text-gray-400 mb-8">Klik ikon kamera untuk mengubah foto profil</p>
            </div>
            
            <!-- Profile Form Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama Lengkap -->
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-300 mb-3">
                        <i class="fas fa-user mr-3 text-amber-400"></i>
                        Nama Lengkap
                    </label>
                    <div class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                </div>
                
                <!-- Username -->
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-300 mb-3">
                        <i class="fas fa-at mr-3 text-amber-400"></i>
                        Username
                    </label>
                    <div class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>
                
                <!-- Email -->
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-300 mb-3">
                        <i class="fas fa-envelope mr-3 text-amber-400"></i>
                        Email
                    </label>
                    <div class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>
                
                <!-- WhatsApp -->
                <div>
                    <label class="flex items-center text-sm font-medium text-gray-300 mb-3">
                        <i class="fab fa-whatsapp mr-3 text-green-400"></i>
                        Nomor WhatsApp
                    </label>
                    <div class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white">
                        <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Belum diatur'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Button -->
            <div class="text-center mt-8">
                <a href="profile.php" 
                   class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors font-medium">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Profil
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Orders -->
            <div class="stat-card rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Pesanan</p>
                        <p class="text-2xl font-bold text-white"><?php echo $stats['total_orders']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Active Orders -->
            <div class="stat-card rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Pesanan Aktif</p>
                        <p class="text-2xl font-bold text-amber-400"><?php echo $stats['active_orders']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-amber-400 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Completed Orders -->
            <div class="stat-card rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Selesai</p>
                        <p class="text-2xl font-bold text-green-400"><?php echo $stats['completed_orders']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Spent -->
            <div class="stat-card rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Belanja</p>
                        <p class="text-2xl font-bold text-purple-400"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-wallet text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Active Orders -->
            <div class="glass-effect rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-tasks mr-3 text-amber-400"></i>
                        Pesanan Aktif
                    </h2>
                    <a href="../../my-orders.php" class="text-amber-400 hover:text-amber-300 text-sm">
                        Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <?php if (empty($active_orders)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-500 text-4xl mb-4"></i>
                        <p class="text-gray-400">Tidak ada pesanan aktif</p>
                        <a href="../../order.php" class="inline-block mt-4 bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg transition-colors">
                            <i class="fas fa-plus mr-2"></i>Buat Pesanan Baru
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($active_orders as $order): 
                            $statusInfo = getStatusInfo($order['status']);
                        ?>
                            <div class="order-card rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="font-semibold text-white"><?php echo htmlspecialchars($order['title']); ?></h3>
                                        <p class="text-sm text-gray-400">Ref: <?php echo htmlspecialchars($order['order_reference']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium" 
                                              style="background-color: <?php echo $statusInfo['color']; ?>20; color: <?php echo $statusInfo['color']; ?>;">
                                            <?php echo $statusInfo['label']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-sm text-gray-400">
                                        <span><i class="fas fa-tag mr-1"></i><?php echo getServiceDisplayName($order['service_type']); ?></span>
                                        <span><i class="fas fa-money-bill-wave mr-1"></i><?php echo formatCurrency($order['budget']); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <!-- Progress Circle -->
                                        <div class="relative w-8 h-8">
                                            <svg class="w-8 h-8 progress-ring" viewBox="0 0 32 32">
                                                <circle cx="16" cy="16" r="14" stroke="rgba(255,255,255,0.2)" stroke-width="2" fill="none"/>
                                                <circle cx="16" cy="16" r="14" stroke="<?php echo $statusInfo['color']; ?>" stroke-width="2" fill="none"
                                                        stroke-dasharray="<?php echo $statusInfo['percentage']; ?> 100" stroke-linecap="round"/>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <span class="text-xs font-bold text-white"><?php echo $statusInfo['percentage']; ?>%</span>
                                            </div>
                                        </div>
                                        
                                        <a href="../orders/detail.php?id=<?php echo $order['id']; ?>" 
                                           class="text-amber-400 hover:text-amber-300 text-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="glass-effect rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-history mr-3 text-amber-400"></i>
                        Aktivitas Terbaru
                    </h2>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clock text-gray-500 text-4xl mb-4"></i>
                        <p class="text-gray-400">Belum ada aktivitas</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order): 
                            $statusInfo = getStatusInfo($order['status']);
                        ?>
                            <div class="flex items-center space-x-4 p-3 rounded-lg bg-white/5">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center" 
                                     style="background-color: <?php echo $statusInfo['color']; ?>20;">
                                    <i class="fas fa-<?php echo $statusInfo['icon']; ?>" style="color: <?php echo $statusInfo['color']; ?>;"></i>
                                </div>
                                
                                <div class="flex-1">
                                    <p class="text-white font-medium"><?php echo truncateText($order['title'], 30); ?></p>
                                    <p class="text-gray-400 text-sm"><?php echo formatTimeAgo($order['created_at']); ?></p>
                                </div>
                                
                                <div class="text-right">
                                    <span class="text-xs px-2 py-1 rounded-full" 
                                          style="background-color: <?php echo $statusInfo['color']; ?>20; color: <?php echo $statusInfo['color']; ?>;">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-effect rounded-2xl p-6 mt-8 shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-bolt mr-3 text-amber-400"></i>
                Aksi Cepat
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="../../order.php" 
                   class="flex items-center justify-center p-4 bg-amber-600/20 hover:bg-amber-600/30 rounded-lg transition-colors group">
                    <i class="fas fa-plus text-amber-400 text-xl mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="text-white font-medium">Buat Pesanan Baru</span>
                </a>
                
                <a href="../../my-orders.php" 
                   class="flex items-center justify-center p-4 bg-blue-600/20 hover:bg-blue-600/30 rounded-lg transition-colors group">
                    <i class="fas fa-list text-blue-400 text-xl mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="text-white font-medium">Lihat Semua Pesanan</span>
                </a>
                
                <a href="profile.php" 
                   class="flex items-center justify-center p-4 bg-green-600/20 hover:bg-green-600/30 rounded-lg transition-colors group">
                    <i class="fas fa-user-edit text-green-400 text-xl mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="text-white font-medium">Edit Profil</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
