<?php
session_start();
require_once '../../config/database.php';
include "../../config/status_functions.php";

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: ../../my-orders.php");
    exit();
}

// Get timeline steps and current progress
$timeline = getTimelineSteps($order['status']);
$currentStatusInfo = getStatusInfo($order['status']);
$progressPercentage = $currentStatusInfo['percentage']; // Always use status-based percentage
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Pesanan - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .progress-bar {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.8s ease-in-out;
        }
        .stage-line {
            background: linear-gradient(to bottom, #374151, #6b7280);
        }
        .stage-line.completed {
            background: linear-gradient(to bottom, #10b981, #059669);
        }
        .stage-dot {
            transition: all 0.3s ease;
        }
        .stage-dot.completed {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
        }
        .stage-dot.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .estimated-time {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
    </style>
</head>
<body class="min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                        <i class="fas fa-tasks text-amber-400 mr-3"></i>Progress Pesanan
                    </h1>
                    <p class="text-gray-300">Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?> - <?php echo htmlspecialchars($order['title']); ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="../../my-orders.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-white font-semibold">Progress Keseluruhan</span>
                        <span class="text-amber-400 font-bold text-lg"><?php echo $progressPercentage; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-3 mb-4">
                        <div class="progress-bar h-3 rounded-full" style="width: <?php echo $progressPercentage; ?>%"></div>
                    </div>
                    <p class="text-gray-300 text-sm">
                        <?php echo htmlspecialchars($order['status_description'] ?? $currentStatusInfo['description']); ?>
                    </p>
                </div>
                
                <!-- Estimated Time -->
                <?php if ($order['status'] != 'completed' && $order['deadline']): ?>
                <div class="estimated-time rounded-xl p-4 text-center text-white min-w-48">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p class="text-sm opacity-90">Estimasi Selesai</p>
                    <p class="font-bold text-lg"><?php echo date('d M Y', strtotime($order['deadline'])); ?></p>
                    <?php
                    $days_left = ceil((strtotime($order['deadline']) - time()) / (60 * 60 * 24));
                    if ($days_left > 0) {
                        echo "<p class='text-xs opacity-75'>$days_left hari lagi</p>";
                    } elseif ($days_left == 0) {
                        echo "<p class='text-xs opacity-75'>Hari ini</p>";
                    } else {
                        echo "<p class='text-xs opacity-75 text-red-300'>Terlambat " . abs($days_left) . " hari</p>";
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline Progress -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-route text-amber-400 mr-3"></i>
                Timeline Pengerjaan
            </h2>
            
            <div class="relative">
                <?php $stageIndex = 0; foreach ($timeline as $step): ?>
                <div class="flex items-start mb-8 last:mb-0">
                    <!-- Stage Dot -->
                    <div class="relative flex-shrink-0">
                        <div class="stage-dot w-12 h-12 rounded-full border-4 border-gray-600 flex items-center justify-center text-white font-bold
                                    <?php echo $step['is_completed'] ? 'completed' : ($step['is_active'] ? 'active' : ''); ?>">
                            <?php if ($step['is_completed']): ?>
                                <i class="fas fa-check"></i>
                            <?php elseif ($step['is_active']): ?>
                                <i class="<?php echo $step['icon']; ?> <?php echo $step['status'] == 'in_progress' ? 'fa-spin' : ''; ?>"></i>
                            <?php else: ?>
                                <?php echo $step['percentage']; ?>%
                            <?php endif; ?>
                        </div>
                        
                        <!-- Connecting Line -->
                        <?php if ($stageIndex < count($timeline) - 1): ?>
                        <div class="stage-line w-1 h-16 mx-auto mt-2 rounded-full
                                    <?php echo $step['is_completed'] ? 'completed' : ''; ?>"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stage Content -->
                    <div class="ml-6 flex-1">
                        <div class="bg-white/5 rounded-xl p-4 <?php echo $step['is_active'] ? 'ring-2 ring-amber-400' : ''; ?>">
                            <h3 class="text-lg font-semibold text-white mb-2 flex items-center">
                                <?php echo $step['label']; ?>
                                <span class="ml-2 text-sm text-gray-400">(<?php echo $step['percentage']; ?>%)</span>
                                <?php if ($step['is_active']): ?>
                                    <span class="ml-2 px-2 py-1 bg-<?php echo $step['color']; ?>-500 text-white text-xs rounded-full font-bold"><?php echo $step['badge']; ?></span>
                                <?php elseif ($step['is_completed']): ?>
                                    <span class="ml-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full font-bold">SELESAI</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-gray-300 text-sm"><?php echo $step['description']; ?></p>
                            
                            <?php if ($step['is_active']): ?>
                            <div class="mt-3 flex items-center text-<?php echo $step['color']; ?>-400 text-sm">
                                <div class="w-2 h-2 bg-<?php echo $step['color']; ?>-400 rounded-full mr-2 animate-pulse"></div>
                                Sedang dalam proses...
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $stageIndex++; endforeach; ?>
            </div>
        </div>

        <!-- Order Details -->
        <div class="glass-effect rounded-2xl p-6 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-info-circle text-amber-400 mr-3"></i>
                Detail Pesanan
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Layanan</span>
                        <p class="text-white font-medium"><?php echo ucfirst(str_replace('_', ' ', $order['service_type'])); ?></p>
                    </div>
                    
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Paket</span>
                        <p class="text-white font-medium"><?php echo ucfirst($order['package_type']); ?></p>
                    </div>
                    
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Budget</span>
                        <p class="text-white font-medium">Rp <?php echo number_format($order['budget'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Tanggal Pesan</span>
                        <p class="text-white font-medium"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    
                    <?php if ($order['deadline']): ?>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Deadline</span>
                        <p class="text-white font-medium"><?php echo date('d M Y', strtotime($order['deadline'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bg-white/5 p-4 rounded-lg">
                        <span class="text-gray-400 text-sm">Terakhir Update</span>
                        <p class="text-white font-medium"><?php echo date('d M Y, H:i', strtotime($order['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($order['description']): ?>
            <div class="mt-6 bg-white/5 p-4 rounded-lg">
                <span class="text-gray-400 text-sm">Deskripsi Proyek</span>
                <p class="text-white mt-2"><?php echo nl2br(htmlspecialchars($order['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($order['notes']): ?>
            <div class="mt-4 bg-amber-500/10 border border-amber-500/20 p-4 rounded-lg">
                <span class="text-amber-400 text-sm font-medium">Catatan dari Tim</span>
                <p class="text-amber-100 mt-2"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto refresh setiap 30 detik untuk update real-time
        setInterval(() => {
            // Hanya refresh jika status belum completed
            <?php if ($order['status'] != 'completed'): ?>
            location.reload();
            <?php endif; ?>
        }, 30000);
        
        // Animasi progress bar saat load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = '<?php echo $progress; ?>%';
            }, 500);
        });
    </script>
</body>
</html>
