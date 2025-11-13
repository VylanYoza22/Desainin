<?php
/**
 * My Orders - User Order Management
 * Shows all orders for the logged-in user with filtering and actions
 */
session_start();
require_once 'config/database.php';
require_once 'config/status_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT id, username, full_name, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$where_conditions = ["user_id = ?"];
$params = [$user_id];
$param_types = "i";

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Get orders with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total orders
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE $where_clause");
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);
$count_stmt->close();

// Get orders
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE $where_clause 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$param_types .= "ii";
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Service and package type mappings
$serviceTypes = [
    'video_editing' => 'Video Editing',
    'graphic_design' => 'Graphic Design', 
    'social_media' => 'Social Media Content',
    'presentation' => 'Presentation Design'
];

$packageTypes = [
    'basic' => 'Basic',
    'standard' => 'Standard',
    'premium' => 'Premium'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/Style-Desainin-dark.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .order-card {
            animation: fadeInUp 0.6s ease-out;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .order-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(245, 158, 11, 0.15);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(245, 158, 11, 0.3);
        }
        .stat-card {
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
        }
        .btn-action {
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateX(3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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

<body class="bg-black text-white font-sans min-h-screen">
    <!-- Animated Background -->
    <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
    <div class="particles fixed inset-0 -z-10 pointer-events-none" id="particles"></div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 section-spacing">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-white via-amber-400 to-yellow-500 bg-clip-text text-transparent">
                <i class="fas fa-list mr-4 animate-pulse"></i>Pesanan Saya
            </h1>
            <p class="text-gray-400 text-lg">Kelola dan pantau semua pesanan Anda dengan mudah</p>
        </div>

        <!-- Filters and Search -->
        <div class="glass-card rounded-3xl p-8 mb-10 shadow-2xl">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Cari berdasarkan judul atau deskripsi..." 
                               class="w-full pl-12 pr-4 py-4 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-400 transition-all focus:transform focus:-translate-y-1">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-amber-400"></i>
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="md:w-56">
                    <select name="status" class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-400 transition-all" style="color: white;">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Semua Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Dikonfirmasi</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Sedang Dikerjakan</option>
                        <option value="review" <?php echo $status_filter === 'review' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Review</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Selesai</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">Dibatalkan</option>
                    </select>
                </div>
                
                <!-- Filter Button -->
                <button type="submit" class="px-8 py-4 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                
                <!-- Reset Button -->
                <a href="my-orders.php" class="px-8 py-4 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-bold transition-all text-center shadow-lg hover:shadow-xl hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                    <i class="fas fa-undo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="glass-card rounded-3xl text-center py-20 shadow-2xl">
            <div class="animate-float">
                <i class="fas fa-inbox text-8xl text-gray-600 mb-6"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-400 mb-4">Tidak ada pesanan ditemukan</h3>
            <p class="text-gray-500 mb-6">
                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    Coba ubah filter pencarian Anda atau <a href="my-orders.php" class="text-amber-400 hover:text-amber-300">reset filter</a>
                <?php else: ?>
                    Mulai dengan membuat pesanan pertama Anda
                <?php endif; ?>
            </p>
            <a href="order.php" class="inline-flex items-center gap-3 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-10 py-4 rounded-xl font-bold transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl shadow-lg text-lg">
                <i class="fas fa-plus text-xl"></i>
                <span>Buat Pesanan Baru</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php else: ?>
        
        <div class="space-y-8">
            <?php foreach ($orders as $order): 
                $statusInfo = getStatusInfo($order['status']);
            ?>
            <div class="order-card glass-card rounded-3xl p-8 shadow-xl">
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    
                    <!-- Order Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-white mb-3 flex items-center gap-3">
                                    <i class="fas fa-file-alt text-amber-400"></i>
                                    <?php echo htmlspecialchars($order['title']); ?>
                                </h3>
                                <div class="flex flex-wrap gap-3 mb-3">
                                    <span class="px-4 py-2 bg-blue-500/20 border border-blue-500/30 text-blue-300 rounded-xl text-sm font-semibold flex items-center gap-2">
                                        <i class="fas fa-palette"></i>
                                        <?php echo $serviceTypes[$order['service_type']] ?? $order['service_type']; ?>
                                    </span>
                                    <span class="px-4 py-2 bg-purple-500/20 border border-purple-500/30 text-purple-300 rounded-xl text-sm font-semibold flex items-center gap-2">
                                        <i class="fas fa-box"></i>
                                        <?php echo $packageTypes[$order['package_type']] ?? $order['package_type']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="flex items-center gap-2">
                                <div class="px-5 py-3 bg-<?php echo $statusInfo['color']; ?>-500/20 border-2 border-<?php echo $statusInfo['color']; ?>-500/40 text-<?php echo $statusInfo['color']; ?>-300 rounded-xl text-sm font-bold flex items-center gap-2 shadow-lg">
                                    <i class="<?php echo $statusInfo['icon']; ?> text-lg"></i>
                                    <?php echo $statusInfo['label']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-gray-300 mb-5 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($order['description']); ?></p>
                        
                        <!-- Order Details -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div class="bg-white/5 p-4 rounded-xl border border-white/10">
                                <span class="text-gray-400 text-xs flex items-center gap-1 mb-1">
                                    <i class="fas fa-hashtag"></i>
                                    Order ID:
                                </span>
                                <div class="font-mono text-amber-400 font-bold text-lg">#<?php echo $order['id']; ?></div>
                            </div>
                            <div class="bg-white/5 p-4 rounded-xl border border-white/10">
                                <span class="text-gray-400 text-xs flex items-center gap-1 mb-1">
                                    <i class="fas fa-money-bill"></i>
                                    Budget:
                                </span>
                                <div class="text-green-400 font-bold text-lg">
                                    <?php echo $order['budget'] ? 'Rp ' . number_format($order['budget'], 0, ',', '.') : '-'; ?>
                                </div>
                            </div>
                            <div class="bg-white/5 p-4 rounded-xl border border-white/10">
                                <span class="text-gray-400 text-xs flex items-center gap-1 mb-1">
                                    <i class="fas fa-calendar"></i>
                                    Deadline:
                                </span>
                                <div class="text-white font-bold"><?php echo $order['deadline'] ? date('d M Y', strtotime($order['deadline'])) : '-'; ?></div>
                            </div>
                            <div class="bg-white/5 p-4 rounded-xl border border-white/10">
                                <span class="text-gray-400 text-xs flex items-center gap-1 mb-1">
                                    <i class="fas fa-chart-line"></i>
                                    Progress:
                                </span>
                                <div class="text-<?php echo $statusInfo['color']; ?>-400 font-bold text-lg"><?php echo $statusInfo['percentage']; ?>%</div>
                            </div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                        <div class="mt-5 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <span class="text-amber-400 text-sm font-bold flex items-center gap-2 mb-2">
                                <i class="fas fa-sticky-note"></i>
                                Catatan:
                            </span>
                            <p class="text-amber-100 text-sm leading-relaxed"><?php echo htmlspecialchars($order['notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row lg:flex-col gap-3 lg:w-52">
                        <?php if ($order['status'] == 'pending'): ?>
                        <a href="edit-order.php?id=<?php echo $order['id']; ?>" class="btn-action bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-5 py-3 rounded-xl text-sm font-semibold shadow-lg text-center flex items-center justify-center gap-2">
                            <i class="fas fa-edit"></i>
                            <span>Edit Pesanan</span>
                        </a>
                        <?php endif; ?>
                        
                        <a href="order-progress.php?id=<?php echo $order['id']; ?>" class="btn-action bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-lg text-center flex items-center justify-center gap-2">
                            <i class="fas fa-tasks"></i>
                            <span>Lihat Progress</span>
                        </a>
                        
                        <a href="pages/orders/detail.php?id=<?php echo $order['id']; ?>" class="btn-action bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-5 py-3 rounded-xl text-sm font-semibold shadow-lg text-center flex items-center justify-center gap-2">
                            <i class="fas fa-eye"></i>
                            <span>Detail Lengkap</span>
                        </a>
                        
                        
                        <?php if ($order['status'] == 'completed'): ?>
                        <a href="index.php#feedback" class="btn-action bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-5 py-3 rounded-xl text-sm font-semibold shadow-lg text-center flex items-center justify-center gap-2">
                            <i class="fas fa-star"></i>
                            <span>Beri Testimoni</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-8">
            <nav class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-3 py-2 <?php echo $i === $page ? 'bg-amber-600 text-white' : 'bg-gray-600 hover:bg-gray-700 text-white'; ?> rounded-lg transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
            <?php
            $stats_stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM orders WHERE user_id = ?
            ");
            $stats_stmt->bind_param("i", $user_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->get_result()->fetch_assoc();
            $stats_stmt->close();
            ?>
            
            <div class="stat-card glass-card rounded-2xl p-6 text-center shadow-lg">
                <i class="fas fa-shopping-cart text-3xl text-amber-400 mb-3"></i>
                <div class="text-3xl font-bold text-white mb-2"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-400 font-semibold">Total Pesanan</div>
            </div>
            
            <div class="stat-card glass-card rounded-2xl p-6 text-center shadow-lg">
                <i class="fas fa-clock text-3xl text-yellow-400 mb-3"></i>
                <div class="text-3xl font-bold text-yellow-400 mb-2"><?php echo $stats['pending']; ?></div>
                <div class="text-sm text-gray-400 font-semibold">Pending</div>
            </div>
            
            <div class="stat-card glass-card rounded-2xl p-6 text-center shadow-lg">
                <i class="fas fa-spinner text-3xl text-blue-400 mb-3"></i>
                <div class="text-3xl font-bold text-blue-400 mb-2"><?php echo $stats['in_progress']; ?></div>
                <div class="text-sm text-gray-400 font-semibold">Sedang Dikerjakan</div>
            </div>
            
            <div class="stat-card glass-card rounded-2xl p-6 text-center shadow-lg">
                <i class="fas fa-check-circle text-3xl text-green-400 mb-3"></i>
                <div class="text-3xl font-bold text-green-400 mb-2"><?php echo $stats['completed']; ?></div>
                <div class="text-sm text-gray-400 font-semibold">Selesai</div>
            </div>
        </div>
    </div>

    <script src="assets/js/Desainin.js"></script>
</body>
</html>
