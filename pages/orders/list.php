<?php
session_start();
require_once '../../config/database.php';

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil semua orders user
$orders = [];
$check_orders = $conn->query("SHOW TABLES LIKE 'orders'");
if ($check_orders && $check_orders->num_rows > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}

// Function untuk format status
function getStatusBadge($status) {
    switch($status) {
        case 'pending':
            return '<span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 rounded-full text-sm font-medium">Pending</span>';
        case 'in_progress':
            return '<span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-sm font-medium">In Progress</span>';
        case 'completed':
            return '<span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm font-medium">Completed</span>';
        case 'cancelled':
            return '<span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-sm font-medium">Cancelled</span>';
        default:
            return '<span class="px-3 py-1 bg-gray-500/20 text-gray-400 rounded-full text-sm font-medium">Unknown</span>';
    }
}

function getServiceName($service) {
    switch($service) {
        case 'video_editing': return 'Video Editing';
        case 'graphic_design': return 'Graphic Design';
        case 'social_media': return 'Social Media';
        case 'presentation': return 'Presentation';
        default: return ucfirst($service);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>
<body class="min-h-screen p-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                        <i class="fas fa-shopping-bag text-amber-400 mr-3"></i>Pesanan Saya
                    </h1>
                    <p class="text-gray-300 text-sm lg:text-base">Kelola dan pantau semua pesanan Anda</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="../../order.php" class="bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-4 lg:px-6 py-3 rounded-lg transition-all duration-300 transform hover:scale-105 text-center text-sm lg:text-base">
                        <i class="fas fa-plus mr-2"></i>Pesanan Baru
                    </a>
                    <a href="../user/dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 lg:px-6 py-3 rounded-lg transition-all duration-300 text-center text-sm lg:text-base">
                        <i class="fas fa-arrow-left mr-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="glass-effect rounded-2xl p-12 text-center">
                <div class="max-w-md mx-auto">
                    <i class="fas fa-shopping-cart text-6xl text-gray-400 mb-6 opacity-50"></i>
                    <h3 class="text-2xl font-bold text-white mb-4">Belum Ada Pesanan</h3>
                    <p class="text-gray-300 mb-8">Anda belum memiliki pesanan. Mulai dengan membuat pesanan pertama Anda!</p>
                    <a href="../../order.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus"></i>
                        Buat Pesanan Pertama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="glass-effect rounded-2xl p-6 hover:bg-white/15 transition-all duration-300">
                        <div class="flex flex-col gap-4">
                            <!-- Order Info -->
                            <div class="flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <div class="text-<?php echo getStatusInfo($order['status'])['color']; ?>-400 font-semibold"><?php echo getStatusInfo($order['status'])['percentage']; ?>%</div>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-300 mb-2">
                                            <span><i class="fas fa-cogs mr-1"></i><?php echo getServiceName($order['service_type']); ?></span>
                                            <span><i class="fas fa-box mr-1"></i><?php echo ucfirst($order['package_type']); ?></span>
                                            <span><i class="fas fa-money-bill mr-1"></i>Rp <?php echo number_format($order['budget'], 0, ',', '.'); ?></span>
                                        </div>
                                        <p class="text-gray-400 text-xs sm:text-sm"><?php echo htmlspecialchars(substr($order['description'], 0, 100)); ?>...</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <?php echo getStatusBadge($order['status']); ?>
                                    </div>
                                </div>
                                
                                <!-- Order Details -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 text-xs sm:text-sm">
                                    <div class="bg-white/5 p-3 rounded-lg">
                                        <span class="text-gray-400 block">Dibuat:</span>
                                        <p class="text-white font-medium"><?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <?php if ($order['deadline']): ?>
                                    <div class="bg-white/5 p-3 rounded-lg">
                                        <span class="text-gray-400 block">Deadline:</span>
                                        <p class="text-white font-medium"><?php echo date('d M Y', strtotime($order['deadline'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="bg-white/5 p-3 rounded-lg">
                                        <span class="text-gray-400 block">Terakhir Update:</span>
                                        <p class="text-white font-medium"><?php echo date('d M Y', strtotime($order['updated_at'])); ?></p>
                                    </div>
                                    <div class="bg-white/5 p-3 rounded-lg">
                                        <span class="text-gray-400 block">Order ID:</span>
                                        <p class="text-white font-medium">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($order['notes']): ?>
                                <div class="mt-4 p-3 bg-white/5 rounded-lg">
                                    <span class="text-gray-400 text-sm">Catatan:</span>
                                    <p class="text-gray-300 text-sm mt-1"><?php echo htmlspecialchars($order['notes']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row lg:flex-col gap-2 lg:w-48">
                                <?php if ($order['status'] == 'pending'): ?>
                                    <a href="../../edit-order.php?id=<?php echo $order['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                                        <i class="fas fa-edit mr-1"></i>Edit Pesanan
                                    </a>
                                <?php endif; ?>
                                
                                <a href="../../order-progress.php?id=<?php echo $order['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                                    <i class="fas fa-tasks mr-1"></i>Lihat Progress
                                </a>
                                
                                <a href="detail.php?id=<?php echo $order['id']; ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                                    <i class="fas fa-eye mr-1"></i>Detail Lengkap
                                </a>
                                
                                
                                <?php if ($order['status'] == 'completed'): ?>
                                    <a href="../../index.php#feedback" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                                        <i class="fas fa-star mr-1"></i>Beri Testimoni
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
