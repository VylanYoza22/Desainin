<?php
/**
 * Admin Orders Management
 * Handles order listing, status updates, and admin dashboard functionality
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/status_functions.php';

// Admin authentication check
// Note: This is a demo implementation - use proper authentication in production
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    $_SESSION['admin'] = true; // Demo mode - remove in production
} else {
    if (isset($_GET['admin']) && $_GET['admin'] === 'demo') {
        $_SESSION['is_admin'] = true;
    } else {
        die('Access denied. Add ?admin=demo to URL for demo access.');
    }
}

// Handle status updates
if ($_POST && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $custom_description = $_POST['status_description'] ?? null;
    
    // Update order status with progress percentage
    updateOrderStatus($conn, $order_id, $new_status, $custom_description);
    
    header("Location: admin-orders.php?admin=demo&updated=1");
    exit();
}

// Get all orders
$orders = [];
$result = $conn->query("SELECT o.*, u.full_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

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
                <div class="flex gap-3">
                    <span class="bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-shield mr-2"></i>Admin Mode
                    </span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
        <div class="bg-green-500/20 border border-green-500/50 text-green-400 p-4 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>Status pesanan berhasil diupdate!
        </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="glass-effect rounded-2xl p-6 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-list text-amber-400 mr-3"></i>
                Daftar Pesanan (<?php echo count($orders); ?>)
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="text-left text-gray-300 p-3">Order ID</th>
                            <th class="text-left text-gray-300 p-3">Pelanggan</th>
                            <th class="text-left text-gray-300 p-3">Layanan</th>
                            <th class="text-left text-gray-300 p-3">Status</th>
                            <th class="text-left text-gray-300 p-3">Deadline</th>
                            <th class="text-left text-gray-300 p-3">Budget</th>
                            <th class="text-left text-gray-300 p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr class="border-b border-gray-700 hover:bg-white/5">
                            <td class="p-3 text-white font-mono">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="p-3">
                                <div class="text-white font-medium"><?php echo htmlspecialchars($order['full_name']); ?></div>
                                <div class="text-gray-400 text-xs"><?php echo htmlspecialchars($order['email']); ?></div>
                            </td>
                            <td class="p-3">
                                <div class="text-white"><?php echo htmlspecialchars($order['title']); ?></div>
                                <div class="text-gray-400 text-xs"><?php echo ucfirst(str_replace('_', ' ', $order['service_type'])); ?> - <?php echo ucfirst($order['package_type']); ?></div>
                            </td>
                            <td class="p-3">
                                <span class="px-3 py-1 rounded-full text-white text-xs font-medium <?php echo getStatusColor($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="p-3 text-gray-300">
                                <?php echo $order['deadline'] ? date('d M Y', strtotime($order['deadline'])) : '-'; ?>
                            </td>
                            <td class="p-3 text-white font-medium">
                                Rp <?php echo number_format($order['budget'], 0, ',', '.'); ?>
                            </td>
                            <td class="p-3">
                                <button onclick="openUpdateModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo htmlspecialchars($order['notes'] ?? '', ENT_QUOTES); ?>')" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                    <i class="fas fa-edit mr-1"></i>Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="glass-effect rounded-2xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-white mb-4">Update Status Pesanan</h3>
            
            <form method="POST">
                <input type="hidden" id="orderId" name="order_id">
                
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Status Baru</label>
                    <select id="newStatus" name="new_status" class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="review">Review</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Catatan untuk Pelanggan</label>
                    <textarea id="notes" name="notes" rows="3" 
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
