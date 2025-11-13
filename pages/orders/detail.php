<?php
/**
 * Order Detail View
 * Shows complete order information in a modal-like detailed view
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/status_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$order = null;

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../../my-orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Fetch order details with user information
$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: ../../my-orders.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Get status information
$statusInfo = getStatusInfo($order['status']);
$timeline = getTimelineSteps($order['status']);

// Service type mapping
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
    <title>Detail Pesanan #<?php echo $order['id']; ?> - Desainin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/Style-Desainin-dark.css">
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
                    <a href="../../index.php" class="text-2xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                        <i class="fas fa-palette mr-2"></i>Desainin
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../../my-orders.php" class="text-gray-300 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/20 rounded">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Pesanan
                    </a>
                    <a href="../../order-progress.php?id=<?php echo $order['id']; ?>" class="text-gray-300 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/20 rounded">
                        <i class="fas fa-tasks mr-2"></i>Lihat Progress
                    </a>
                    <a href="../auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-400/30">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-8 section-spacing">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-file-alt mr-3"></i>Detail Pesanan #<?php echo $order['id']; ?>
            </h1>
            <p class="text-gray-400">Informasi lengkap tentang pesanan Anda</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Order Details -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Order Status Card -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>Status Pesanan
                    </h3>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-<?php echo $statusInfo['color']; ?>-600/20 flex items-center justify-center">
                                <i class="<?php echo $statusInfo['icon']; ?> text-<?php echo $statusInfo['color']; ?>-400 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold"><?php echo $statusInfo['label']; ?></h4>
                                <p class="text-sm text-gray-400"><?php echo $statusInfo['description']; ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-<?php echo $statusInfo['color']; ?>-400">
                                <?php echo $statusInfo['percentage']; ?>%
                            </div>
                            <div class="text-xs text-gray-400">Progress</div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-700 rounded-full h-3 mb-4">
                        <div class="bg-gradient-to-r from-<?php echo $statusInfo['color']; ?>-500 to-<?php echo $statusInfo['color']; ?>-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?php echo $statusInfo['percentage']; ?>%"></div>
                    </div>
                </div>

                <!-- Project Information -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-project-diagram mr-2"></i>Informasi Project
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-400">Jenis Layanan</label>
                                <div class="text-white font-medium">
                                    <?php echo $serviceTypes[$order['service_type']] ?? $order['service_type']; ?>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-400">Paket</label>
                                <div class="text-white font-medium">
                                    <?php echo $packageTypes[$order['package_type']] ?? $order['package_type']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-sm text-gray-400">Judul Project</label>
                            <div class="text-white font-medium"><?php echo htmlspecialchars($order['title']); ?></div>
                        </div>
                        
                        <div>
                            <label class="text-sm text-gray-400">Deskripsi</label>
                            <div class="text-white bg-white/5 p-4 rounded-lg mt-2">
                                <?php echo nl2br(htmlspecialchars($order['description'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                        <div>
                            <label class="text-sm text-gray-400">Catatan Tambahan</label>
                            <div class="text-white bg-white/5 p-4 rounded-lg mt-2">
                                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-xl font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-history mr-2"></i>Timeline Progress
                    </h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($timeline as $step): ?>
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $step['is_completed'] ? 'bg-green-600' : 'bg-gray-600'; ?>">
                                <?php if ($step['is_completed']): ?>
                                    <i class="fas fa-check text-white text-sm"></i>
                                <?php else: ?>
                                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium <?php echo $step['is_completed'] ? 'text-green-400' : 'text-gray-400'; ?>">
                                    <?php echo $step['label']; ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo $step['description']; ?></div>
                            </div>
                            <div class="text-sm text-gray-400">
                                <?php echo $step['percentage']; ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Order Summary -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-receipt mr-2"></i>Ringkasan Pesanan
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Order ID</span>
                            <span class="font-mono">#<?php echo $order['id']; ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-400">Budget</span>
                            <span class="font-semibold text-green-400">
                                <?php echo $order['budget'] ? 'Rp ' . number_format($order['budget'], 0, ',', '.') : 'Tidak ditentukan'; ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-400">Deadline</span>
                            <span><?php echo $order['deadline'] ? date('d M Y', strtotime($order['deadline'])) : 'Tidak ditentukan'; ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-400">Dibuat</span>
                            <span><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-400">Terakhir Update</span>
                            <span><?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-address-card mr-2"></i>Informasi Kontak
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm text-gray-400">Nama Lengkap</label>
                            <div class="text-white"><?php echo htmlspecialchars($order['full_name']); ?></div>
                        </div>
                        
                        <div>
                            <label class="text-sm text-gray-400">Email</label>
                            <div class="text-white"><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                        
                        <div>
                            <label class="text-sm text-gray-400">WhatsApp</label>
                            <div class="text-white flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-400"></i>
                                <?php echo htmlspecialchars($order['whatsapp_number']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold mb-4 text-amber-400 flex items-center">
                        <i class="fas fa-cogs mr-2"></i>Aksi
                    </h3>
                    
                    <div class="space-y-3">
                        <?php if ($order['status'] == 'pending'): ?>
                        <a href="../../edit-order.php?id=<?php echo $order['id']; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-edit"></i>Edit Pesanan
                        </a>
                        <?php endif; ?>
                        
                        <a href="../../order-progress.php?id=<?php echo $order['id']; ?>" class="w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-tasks"></i>Lihat Progress
                        </a>
                        
                        <a href="../../my-orders.php" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                            <i class="fas fa-arrow-left"></i>Kembali ke Daftar
                        </a>
                        
                        <?php if ($order['status'] == 'completed'): ?>
                        <a href="../../index.php#feedback" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg transition-colors flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-green-400/30">
                            <i class="fas fa-star"></i>Beri Testimoni
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/Desainin.js"></script>
</body>
</html>
