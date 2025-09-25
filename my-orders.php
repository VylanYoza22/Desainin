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
                    <a href="order.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-plus mr-2"></i>Buat Pesanan
                    </a>
                    <a href="pages/auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-list mr-3"></i>Pesanan Saya
            </h1>
            <p class="text-gray-400">Kelola dan pantau semua pesanan Anda</p>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6 mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Cari berdasarkan judul atau deskripsi..." 
                               class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="md:w-48">
                    <select name="status" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" style="color: white;">
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
                <button type="submit" class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                
                <!-- Reset Button -->
                <a href="my-orders.php" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors text-center">
                    <i class="fas fa-undo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="text-center py-16">
            <i class="fas fa-inbox text-6xl text-gray-600 mb-4"></i>
            <h3 class="text-2xl font-semibold text-gray-400 mb-2">Tidak ada pesanan ditemukan</h3>
            <p class="text-gray-500 mb-6">
                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    Coba ubah filter pencarian Anda atau <a href="my-orders.php" class="text-amber-400 hover:text-amber-300">reset filter</a>
                <?php else: ?>
                    Mulai dengan membuat pesanan pertama Anda
                <?php endif; ?>
            </p>
            <a href="order.php" class="inline-block bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <i class="fas fa-plus mr-2"></i>Buat Pesanan Baru
            </a>
        </div>
        <?php else: ?>
        
        <div class="space-y-6">
            <?php foreach ($orders as $order): 
                $statusInfo = getStatusInfo($order['status']);
            ?>
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all duration-300">
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    
                    <!-- Order Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-white mb-2"><?php echo htmlspecialchars($order['title']); ?></h3>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-full text-sm">
                                        <?php echo $serviceTypes[$order['service_type']] ?? $order['service_type']; ?>
                                    </span>
                                    <span class="px-3 py-1 bg-purple-600/20 text-purple-300 rounded-full text-sm">
                                        <?php echo $packageTypes[$order['package_type']] ?? $order['package_type']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="flex items-center gap-2">
                                <div class="px-4 py-2 bg-<?php echo $statusInfo['color']; ?>-600/20 text-<?php echo $statusInfo['color']; ?>-300 rounded-lg text-sm font-medium flex items-center gap-2">
                                    <i class="<?php echo $statusInfo['icon']; ?>"></i>
                                    <?php echo $statusInfo['label']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-gray-300 mb-4 line-clamp-2"><?php echo htmlspecialchars($order['description']); ?></p>
                        
                        <!-- Order Details -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Order ID:</span>
                                <div class="font-mono text-amber-400">#<?php echo $order['id']; ?></div>
                            </div>
                            <div>
                                <span class="text-gray-400">Budget:</span>
                                <div class="text-green-400 font-semibold">
                                    <?php echo $order['budget'] ? 'Rp ' . number_format($order['budget'], 0, ',', '.') : 'Tidak ditentukan'; ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-400">Deadline:</span>
                                <div><?php echo $order['deadline'] ? date('d M Y', strtotime($order['deadline'])) : 'Tidak ditentukan'; ?></div>
                            </div>
                            <div>
                                <span class="text-gray-400">Progress:</span>
                                <div class="text-<?php echo $statusInfo['color']; ?>-400 font-semibold"><?php echo $statusInfo['percentage']; ?>%</div>
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
                        <a href="edit-order.php?id=<?php echo $order['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                            <i class="fas fa-edit mr-1"></i>Edit Pesanan
                        </a>
                        <?php endif; ?>
                        
                        <a href="order-progress.php?id=<?php echo $order['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                            <i class="fas fa-tasks mr-1"></i>Lihat Progress
                        </a>
                        
                        <a href="pages/orders/detail.php?id=<?php echo $order['id']; ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                            <i class="fas fa-eye mr-1"></i>Detail Lengkap
                        </a>
                        
                        
                        <?php if ($order['status'] == 'completed'): ?>
                        <a href="index.php#feedback" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg text-xs sm:text-sm transition-colors text-center">
                            <i class="fas fa-star mr-1"></i>Beri Testimoni
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
            
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-white"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-400">Total Pesanan</div>
            </div>
            
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-yellow-400"><?php echo $stats['pending']; ?></div>
                <div class="text-sm text-gray-400">Pending</div>
            </div>
            
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-blue-400"><?php echo $stats['in_progress']; ?></div>
                <div class="text-sm text-gray-400">Sedang Dikerjakan</div>
            </div>
            
            <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-green-400"><?php echo $stats['completed']; ?></div>
                <div class="text-sm text-gray-400">Selesai</div>
            </div>
        </div>
    </div>

    <script src="assets/js/Desainin.js"></script>
</body>
</html>
