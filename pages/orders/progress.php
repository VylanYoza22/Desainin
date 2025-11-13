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
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        body {
            background: linear-gradient(135deg, #0a0a1a 0%, #16213e 50%, #0f3460 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .glass-effect:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 30px rgba(245, 158, 11, 0.1);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .stage-line {
            background: linear-gradient(to bottom, #374151, #6b7280);
            transition: all 0.5s ease;
        }
        
        .stage-line.completed {
            background: linear-gradient(to bottom, #10b981, #059669);
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }
        
        .stage-dot {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .stage-dot::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: inherit;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .stage-dot:hover::before {
            opacity: 0.3;
        }
        
        .stage-dot.completed {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.6), 0 0 60px rgba(16, 185, 129, 0.3);
            transform: scale(1.1);
        }
        
        .stage-dot.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.6), 0 0 60px rgba(245, 158, 11, 0.3);
            animation: pulse 2s infinite, float 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(245, 158, 11, 0.6); }
            50% { transform: scale(1.15); box-shadow: 0 0 50px rgba(245, 158, 11, 0.8); }
        }
        
        .estimated-time {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            transition: all 0.3s;
        }
        
        .estimated-time:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
        }
        
        .detail-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .detail-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateX(5px);
        }
        
        .section-header {
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
    </style>
</head>
<body class="min-h-screen p-4">
    <div class="max-w-4xl mx-auto section-spacing">
        <!-- Header -->
        <div class="glass-effect rounded-3xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="section-header text-2xl lg:text-3xl font-bold text-white mb-3">
                        <i class="fas fa-tasks text-amber-400"></i>
                        <span>Progress Pesanan</span>
                    </h1>
                    <p class="text-gray-300 text-lg mt-2">Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?> - <span class="text-white font-semibold"><?php echo htmlspecialchars($order['title']); ?></span></p>
                </div>
                <div class="flex gap-3">
                    <a href="../../my-orders.php" class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-8 py-3 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-amber-400/20 flex items-center gap-2 font-semibold shadow-lg hover:shadow-xl hover:-translate-y-1">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="glass-effect rounded-3xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-white font-bold text-lg flex items-center gap-2">
                            <i class="fas fa-chart-line text-amber-400"></i>
                            Progress Keseluruhan
                        </span>
                        <span class="bg-amber-500/20 text-amber-400 font-bold text-2xl px-4 py-2 rounded-xl border border-amber-500/30"><?php echo $progressPercentage; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-700/50 rounded-full h-4 mb-4 shadow-inner overflow-hidden">
                        <div class="progress-bar h-4 rounded-full relative" style="width: <?php echo $progressPercentage; ?>%">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                        </div>
                    </div>
                    <p class="text-gray-300 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-400"></i>
                        <span><?php echo htmlspecialchars($order['status_description'] ?? $currentStatusInfo['description']); ?></span>
                    </p>
                </div>
                
                <!-- Estimated Time -->
                <?php if ($order['status'] != 'completed' && $order['deadline']): ?>
                <div class="estimated-time rounded-2xl p-6 text-center text-white min-w-56">
                    <i class="fas fa-clock text-3xl mb-3 animate-pulse"></i>
                    <p class="text-sm opacity-90 font-semibold">Estimasi Selesai</p>
                    <p class="font-bold text-xl mt-2"><?php echo date('d M Y', strtotime($order['deadline'])); ?></p>
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
        <div class="glass-effect rounded-3xl p-8 mb-8 shadow-2xl">
            <h2 class="section-header text-xl font-bold text-white mb-8">
                <i class="fas fa-route text-amber-400"></i>
                <span>Timeline Pengerjaan</span>
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
                        <div class="detail-card rounded-2xl p-5 <?php echo $step['is_active'] ? 'ring-2 ring-amber-400 bg-amber-500/5' : ''; ?>">
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
        <div class="glass-effect rounded-3xl p-8 shadow-2xl">
            <h2 class="section-header text-xl font-bold text-white mb-8">
                <i class="fas fa-info-circle text-amber-400"></i>
                <span>Detail Pesanan</span>
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-palette text-amber-400"></i>
                            Layanan
                        </span>
                        <p class="text-white font-bold text-lg"><?php echo ucfirst(str_replace('_', ' ', $order['service_type'])); ?></p>
                    </div>
                    
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-box text-purple-400"></i>
                            Paket
                        </span>
                        <p class="text-white font-bold text-lg"><?php echo ucfirst($order['package_type']); ?></p>
                    </div>
                    
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-money-bill text-green-400"></i>
                            Budget
                        </span>
                        <p class="text-white font-bold text-lg">Rp <?php echo number_format($order['budget'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-calendar-plus text-blue-400"></i>
                            Tanggal Pesan
                        </span>
                        <p class="text-white font-bold text-lg"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    
                    <?php if ($order['deadline']): ?>
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-flag-checkered text-red-400"></i>
                            Deadline
                        </span>
                        <p class="text-white font-bold text-lg"><?php echo date('d M Y', strtotime($order['deadline'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-card p-5 rounded-xl">
                        <span class="text-gray-400 text-sm flex items-center gap-2 mb-2">
                            <i class="fas fa-clock text-yellow-400"></i>
                            Terakhir Update
                        </span>
                        <p class="text-white font-bold text-lg"><?php echo date('d M Y, H:i', strtotime($order['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($order['description']): ?>
            <div class="mt-6 detail-card p-5 rounded-xl">
                <span class="text-gray-400 text-sm flex items-center gap-2 mb-3">
                    <i class="fas fa-file-alt text-blue-400"></i>
                    Deskripsi Proyek
                </span>
                <p class="text-white leading-relaxed"><?php echo nl2br(htmlspecialchars($order['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order['design_reference'])): ?>
            <div class="mt-4 detail-card p-5 rounded-xl">
                <span class="text-gray-400 text-sm mb-3 flex items-center gap-2">
                    <i class="fas fa-image text-purple-400"></i>
                    Referensi Desain
                </span>
                <div class="mt-3">
                    <img src="../../<?php echo htmlspecialchars($order['design_reference']); ?>" 
                         alt="Referensi Desain" 
                         class="rounded-xl border-2 border-white/20 max-w-full h-auto cursor-pointer hover:scale-105 hover:border-amber-400/50 transition-all duration-300 shadow-lg"
                         onclick="openImageModal(this.src)">
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($order['notes']): ?>
            <div class="mt-4 bg-amber-500/10 border-2 border-amber-500/30 p-5 rounded-xl shadow-lg">
                <span class="text-amber-400 font-bold flex items-center gap-2 mb-3">
                    <i class="fas fa-sticky-note"></i>
                    Catatan dari Tim
                </span>
                <p class="text-amber-100 leading-relaxed"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 backdrop-blur-sm" onclick="closeImageModal()">
        <div class="relative max-w-7xl max-h-screen p-4">
            <button onclick="closeImageModal()" class="absolute top-2 right-2 bg-white/10 hover:bg-white/20 text-white rounded-full p-3 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
            <img id="modalImage" src="" alt="Referensi Desain" class="max-w-full max-h-screen rounded-lg">
        </div>
    </div>

    <script>
        // Image modal functions
        function openImageModal(src) {
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
            document.getElementById('modalImage').src = src;
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.getElementById('imageModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
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
                progressBar.style.width = '<?php echo $progressPercentage; ?>%';
            }, 500);
        });
    </script>
</body>
</html>
