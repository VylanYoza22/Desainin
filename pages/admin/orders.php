<?php
/**
 * Admin Orders Management
 * Handles order listing, status updates, and admin dashboard functionality
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/status_functions.php';
require_once '../../config/admin_config.php';
require_once '../../config/whatsapp_functions.php';

// Require proper admin access
requireAdmin($conn);

// CSRF for update form
$csrf = csrf_token();

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
// Sorting
$allowedSort = [
  'created_at' => 'o.created_at',
  'deadline' => 'o.deadline',
  'budget' => 'o.budget',
  'status' => 'o.status'
];
$sortKey = $_GET['sort'] ?? 'created_at';
$sortCol = $allowedSort[$sortKey] ?? 'o.created_at';
$dir = strtolower($_GET['dir'] ?? 'desc');
$dir = $dir === 'asc' ? 'ASC' : 'DESC';
// Pagination
$perPage = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Build WHERE
$where = [];
$params = [];
$types = '';
if ($statusFilter !== 'all') { $where[] = 'o.status = ?'; $params[] = $statusFilter; $types .= 's'; }
if ($search !== '') { $where[] = '(o.title LIKE CONCAT("%", ?, "%") OR u.full_name LIKE CONCAT("%", ?, "%"))'; $params[] = $search; $params[] = $search; $types .= 'ss'; }
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

// Total count
$countSql = 'SELECT COUNT(*) AS c FROM orders o LEFT JOIN users u ON o.user_id = u.id' . $whereSql;
$stmtC = $conn->prepare($countSql);
if ($params) { $stmtC->bind_param($types, ...$params); }
$stmtC->execute();
$total = (int)($stmtC->get_result()->fetch_assoc()['c'] ?? 0);
$stmtC->close();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Data query
$sql = 'SELECT o.*, u.full_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id' . $whereSql;
$sql .= ' ORDER BY ' . $sortCol . ' ' . $dir . ' LIMIT ? OFFSET ?';

$orders = [];
$stmt = $conn->prepare($sql);
if ($params) {
  $bindTypes = $types . 'ii';
  $bindParams = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($bindTypes, ...$bindParams);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res) { while ($row = $res->fetch_assoc()) { $orders[] = $row; } }
$stmt->close();

function getStatusColor($status) {
    $statusInfo = getStatusInfo($status);
    switch($statusInfo['color']) {
        case 'amber': return 'bg-amber-500';
        case 'green': return 'bg-green-500';
        case 'blue': return 'bg-blue-500';
        case 'purple': return 'bg-purple-500';
        case 'red': return 'bg-red-500';
        default: return 'bg-gray-500';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Pesanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
        }
        /* Consistent glass style with dashboard */
        .glass, .glass-effect {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        thead.sticky {
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(6px);
            background: rgba(0,0,0,0.35);
        }
        .list-item {
            transition: background .2s ease, border-color .2s ease;
            border-left: 4px solid transparent;
        }
        .list-item:hover {
            background: rgba(255,255,255,0.06);
            border-left-color: #f59e0b; /* amber */
        }
        /* Header controls - pill style */
        .btn-pill { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; border:1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.08); color:#e5e7eb; }
        .btn-pill:hover { background: rgba(255,255,255,.12); }
        .btn-amber { background:#f59e0b; color:#0b0b0b; border-color: rgba(245,158,11,.6); }
        .btn-amber:hover { background:#d97706; color:#0b0b0b; }
        .btn-gray { background: rgba(255,255,255,.08); color:#e5e7eb; }
        .input-pill, .select-pill { background: rgba(17,24,39,.85); color:#e5e7eb; border:1px solid rgba(255,255,255,.18); padding:10px 12px; border-radius:12px; }
        .select-pill { appearance: none; }
        /* Card layout for orders */
        .order-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 14px;
        }
        .order-card:hover { border-color: rgba(245,158,11,0.45); }
        .badge {
            display: inline-flex; align-items: center; gap: .35rem;
            border-radius: 9999px; padding: 2px 8px; font-size: 11px; border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.08); color: #e5e7eb;
        }
        .chip { padding: 6px 10px; border-radius: 9999px; font-size: 12px; border:1px solid rgba(255,255,255,.16); background: rgba(255,255,255,.08); color: #e5e7eb; }
        .chip.active { background: rgba(245,158,11,.18); border-color: rgba(245,158,11,.45); color: #fde68a; }
        /* Animated background behind content (same as dashboard) */
        .bg-gradient-animated {
            background:
                radial-gradient(1200px circle at 0% 0%, rgba(245, 158, 11, 0.12), transparent 40%),
                radial-gradient(1000px circle at 100% 0%, rgba(59, 130, 246, 0.12), transparent 40%),
                radial-gradient(1200px circle at 100% 100%, rgba(34, 197, 94, 0.12), transparent 45%),
                radial-gradient(900px circle at 0% 100%, rgba(147, 51, 234, 0.12), transparent 45%);
            animation: floaty 14s ease-in-out infinite alternate;
        }
        @keyframes floaty {
            0% { transform: translateY(0px) translateX(0px) scale(1); opacity: .9; }
            100% { transform: translateY(-10px) translateX(6px) scale(1.02); opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
    <?php include '../../includes/admin_header.php'; ?>
    <div class="p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="glass-effect rounded-2xl p-6 mb-8 shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                        <i class="fas fa-cogs text-amber-400 mr-3"></i>Admin - Kelola Pesanan
                    </h1>
                    <p class="text-gray-300">Kelola status dan progress semua pesanan pelanggan</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 items-center">
                    <a href="index.php" class="btn-pill">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Dashboard
                    </a>
                    <form method="GET" class="flex flex-col sm:flex-row gap-3 items-center">
                        <div>
                            <select name="status" class="select-pill pr-8">
                                <option value="all" <?= $statusFilter==='all'?'selected':''; ?>>Semua Status</option>
                                <?php foreach (array_keys(getStatusDefinitions()) as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= $statusFilter===$s?'selected':''; ?>><?= htmlspecialchars(str_replace('_',' ',$s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <input name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul/nama" class="input-pill" />
                        </div>
                        <button class="btn-pill btn-amber"><i class="fas fa-search"></i>Filter</button>
                        <a href="orders.php" class="btn-pill btn-gray"><i class="fas fa-rotate"></i>Reset</a>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
        <div class="bg-green-500/20 border border-green-500/50 text-green-400 p-4 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>Status pesanan berhasil diupdate!
        </div>
        <?php endif; ?>

        <!-- Orders Cards -->
        <div class="glass-effect rounded-2xl p-6 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-list text-amber-400 mr-3"></i>
                    Daftar Pesanan (<?php echo count($orders); ?>)
                </h2>
                <?php $defs = getStatusDefinitions(); ?>
                <div class="hidden sm:flex items-center gap-2">
                    <?php $sf = $statusFilter; ?>
                    <a class="chip <?php echo $sf==='all'?'active':''; ?>" href="orders.php">All</a>
                    <?php foreach(array_keys($defs) as $s): ?>
                        <a class="chip <?php echo $sf===$s?'active':''; ?>" href="orders.php?status=<?php echo urlencode($s); ?>"><?php echo ucfirst(str_replace('_',' ',$s)); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="order-card text-gray-300">Tidak ada pesanan untuk filter saat ini.</div>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($orders as $order): ?>
                        <?php $rawPhone = (string)($order['phone'] ?? ''); $wa = $rawPhone ? validateWhatsAppNumber($rawPhone) : ''; ?>
                        <li class="order-card">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <!-- Left: ID + customer -->
                                <div class="flex items-start gap-3 min-w-[180px]">
                                    <div class="font-mono text-sm text-gray-300">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <div>
                                        <div class="text-white font-medium leading-tight"><?php echo htmlspecialchars($order['full_name']); ?></div>
                                        <div class="text-gray-400 text-xs leading-tight"><?php echo htmlspecialchars($order['email']); ?></div>
                                        <div class="mt-1 flex items-center gap-2 text-xs text-gray-300">
                                            <i class="fab fa-whatsapp text-green-400"></i>
                                            <span><?php echo $rawPhone ? htmlspecialchars($rawPhone) : '-'; ?></span>
                                        </div>
                                        <?php if (!empty($wa)): ?>
                                        <div class="mt-2 flex gap-2">
                                            <?php $msg = urlencode("Halo ".$order['full_name'].", terkait pesanan #".$order['id']." - ".$order['title']); ?>
                                            <a target="_blank" href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $msg; ?>" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs"><i class="fab fa-whatsapp"></i>WA</a>
                                            <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$rawPhone); ?>" class="inline-flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-phone"></i>Call</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Middle: service + status + progress -->
                                <div class="flex-1">
                                    <div class="text-white font-medium"><?php echo htmlspecialchars($order['title']); ?></div>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <span class="badge"><?php echo ucfirst(str_replace('_', ' ', $order['service_type'])); ?></span>
                                        <span class="badge"><?php echo ucfirst($order['package_type']); ?></span>
                                        <?php if (!empty($order['design_reference'])): ?>
                                            <button onclick="viewDesignReference('<?php echo htmlspecialchars($order['design_reference'], ENT_QUOTES); ?>')" 
                                                    class="badge bg-purple-500/20 border-purple-500/40 text-purple-300 hover:bg-purple-500/30 cursor-pointer transition-all">
                                                <i class="fas fa-image"></i> Lihat Referensi
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <span class="px-3 py-1 rounded-full text-white text-xs font-medium <?php echo getStatusColor($order['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                        <div class="w-48 max-w-full">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-700/80 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 bg-amber-500 rounded-full" style="width: <?php echo (int)($order['progress_percentage'] ?? 0); ?>%"></div>
                                                </div>
                                                <div class="text-[11px] text-gray-300 min-w-[34px] text-right"><?php echo (int)($order['progress_percentage'] ?? 0); ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: deadline + budget + actions -->
                                <div class="md:text-right min-w-[160px]">
                                    <div class="text-gray-300 text-sm">
                                        <?php echo $order['deadline'] ? date('d M Y', strtotime($order['deadline'])) : '-'; ?>
                                    </div>
                                    <div class="text-white font-semibold">Rp <?php echo number_format($order['budget'], 0, ',', '.'); ?></div>
                                    <button onclick="openUpdateModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo htmlspecialchars($order['notes'] ?? '', ENT_QUOTES); ?>')" 
                                            class="mt-2 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs">
                                        <i class="fas fa-edit"></i>Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if (($total ?? 0) > 0): ?>
    <?php 
      $qs = function($pageNum) use ($statusFilter,$search,$sortKey,$dir,$perPage){
        $params = [
          'status'=>$statusFilter,
          'q'=>$search,
          'sort'=>$sortKey,
          'dir'=>strtolower($dir),
          'per_page'=>$perPage,
          'page'=>$pageNum
        ];
        return 'orders.php?'.http_build_query($params);
      };
      $from = ($page-1)*$perPage + 1;
      $to = min($total, $page*$perPage);
    ?>
    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 px-4 max-w-7xl mx-auto">
      <div class="text-sm text-gray-300">Menampilkan <?php echo $from; ?>–<?php echo $to; ?> dari <?php echo $total; ?> pesanan</div>
      <div class="flex items-center gap-2">
        <a class="btn-pill btn-gray <?php echo $page<=1?'pointer-events-none opacity-50':''; ?>" href="<?php echo $qs(max(1,$page-1)); ?>"><i class="fas fa-chevron-left"></i>Prev</a>
        <span class="text-gray-300 text-sm">Hal <?php echo $page; ?> / <?php echo $totalPages; ?></span>
        <a class="btn-pill btn-gray <?php echo $page>=$totalPages?'pointer-events-none opacity-50':''; ?>" href="<?php echo $qs(min($totalPages,$page+1)); ?>">Next<i class="fas fa-chevron-right"></i></a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Image Viewer Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden items-center justify-center z-50" onclick="closeImageModal()">
        <div class="relative max-w-7xl max-h-screen p-4">
            <button onclick="closeImageModal()" class="absolute top-2 right-2 bg-white/10 hover:bg-white/20 text-white rounded-full p-3 transition-all z-10">
                <i class="fas fa-times text-xl"></i>
            </button>
            <img id="modalImage" src="" alt="Referensi Desain" class="max-w-full max-h-screen rounded-lg">
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="glass-effect rounded-2xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-white mb-4">Update Status Pesanan</h3>
            <form method="POST" action="update-order.php">
                <input type="hidden" id="orderId" name="order_id">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Status Baru</label>
                    <select id="newStatus" name="new_status" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="payment_pending">Menunggu Pembayaran</option>
                        <option value="payment_confirmed">Pembayaran Dikonfirmasi</option>
                        <option value="in_progress">In Progress</option>
                        <option value="review">Review</option>
                        <option value="final_review">Final Review</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Catatan untuk Pelanggan</label>
                    <textarea id="notes" name="description" rows="3" 
                              class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2"
                              placeholder="Tambahkan catatan atau update untuk pelanggan..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                    <button type="button" onclick="closeUpdateModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg font-medium">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewDesignReference(imagePath) {
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
            document.getElementById('modalImage').src = '../../' + imagePath;
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.getElementById('imageModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
        function openUpdateModal(orderId, currentStatus, currentNotes) {
            document.getElementById('orderId').value = orderId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('notes').value = currentNotes;
            document.getElementById('updateModal').classList.remove('hidden');
            document.getElementById('updateModal').classList.add('flex');
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
            document.getElementById('updateModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('updateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUpdateModal();
            }
        });
    </script>
</body>
</html>
