<?php
/**
 * Edit Order System
 * Allows users to edit their pending orders
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/whatsapp_functions.php';
require_once '../../config/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';
$error = '';
$order = null;
$user = null;

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../../my-orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: ../../my-orders.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch current user (to get profile phone)
$stmtU = $conn->prepare("SELECT id, full_name, email, phone FROM users WHERE id = ?");
$stmtU->bind_param("i", $_SESSION['user_id']);
$stmtU->execute();
$resU = $stmtU->get_result();
$user = $resU->fetch_assoc();
$stmtU->close();

// Check if order can be edited (only pending orders)
if ($order['status'] !== 'pending') {
    $error = "Pesanan ini tidak dapat diedit karena sudah diproses.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $order['status'] === 'pending') {
    $service_type = $_POST['service_type'];
    $package_type = $_POST['package_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = $_POST['budget'];
    $deadline = $_POST['deadline'];
    $notes = trim($_POST['notes']);
    // Use phone from profile
    $profilePhone = trim($user['phone'] ?? '');
    $formattedPhone = '';
    
    // Validate input
    if (empty($title) || empty($description)) {
        $error = "Semua field wajib harus diisi.";
    } else if (empty($profilePhone)) {
        $error = "Nomor WhatsApp pada profil Anda belum diisi. Silakan lengkapi nomor di profil terlebih dahulu.";
    } else {
        $formattedPhone = validateWhatsAppNumber($profilePhone);
        $digits = preg_replace('/[^0-9]/', '', $formattedPhone);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            $error = "Format nomor WhatsApp pada profil tidak valid. Perbarui nomor di profil Anda.";
        }
    }
    
    if (empty($error)) {
        // Update order
        $stmt = $conn->prepare("UPDATE orders SET service_type = ?, package_type = ?, title = ?, description = ?, budget = ?, deadline = ?, notes = ?, whatsapp_number = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssdsssii", $service_type, $package_type, $title, $description, $budget, $deadline, $notes, $formattedPhone, $order_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Pesanan berhasil diperbarui!";
            
            // Refresh order data
            $stmt2 = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt2->bind_param("ii", $order_id, $_SESSION['user_id']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $order = $result2->fetch_assoc();
            $stmt2->close();
            
        } else {
            $error = "Terjadi kesalahan saat memperbarui pesanan.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pesanan #<?php echo $order['id']; ?> - Desainin</title>
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
                    <a href="../../my-orders.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Pesanan
                    </a>
                    <a href="../../order-progress.php?id=<?php echo $order['id']; ?>" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-tasks mr-2"></i>Lihat Progress
                    </a>
                    <a href="../auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-edit mr-3"></i>Edit Pesanan #<?php echo $order['id']; ?>
            </h1>
            <p class="text-gray-400">Perbarui detail pesanan Anda (hanya pesanan dengan status pending yang dapat diedit)</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="bg-green-600/20 border border-green-600/50 text-green-400 px-6 py-4 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-600/20 border border-red-600/50 text-red-400 px-6 py-4 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-3xl p-8">
            <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="space-y-6">
                    <!-- Service and Package Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-cogs mr-2"></i>Jenis Layanan
                            </label>
                            <select name="service_type" id="service_type" required class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors" style="color: white;">
                                <option value="video_editing" <?php echo $order['service_type'] == 'video_editing' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">🎬 Video Editing</option>
                                <option value="graphic_design" <?php echo $order['service_type'] == 'graphic_design' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">🎨 Graphic Design</option>
                                <option value="social_media" <?php echo $order['service_type'] == 'social_media' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">📱 Social Media Content</option>
                                <option value="presentation" <?php echo $order['service_type'] == 'presentation' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">📊 Presentation Design</option>
                            </select>
                        </div>

                        <div>
                            <label for="package_type" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-box mr-2"></i>Paket
                            </label>
                            <select name="package_type" id="package_type" required class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors" style="color: white;">
                                <option value="basic" <?php echo $order['package_type'] == 'basic' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">💼 Basic</option>
                                <option value="standard" <?php echo $order['package_type'] == 'standard' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">⭐ Standard</option>
                                <option value="premium" <?php echo $order['package_type'] == 'premium' ? 'selected' : ''; ?> style="color: #f59e0b; background: #1a1a2e;">👑 Premium</option>
                            </select>
                        </div>
                    </div>

                    <!-- Project Details -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-heading mr-2"></i>Judul Project *
                        </label>
                        <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars($order['title']); ?>" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-align-left mr-2"></i>Deskripsi Project *
                        </label>
                        <textarea name="description" id="description" rows="4" required class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors resize-y"><?php echo htmlspecialchars($order['description']); ?></textarea>
                    </div>

                    <!-- Budget and Deadline -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="budget" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-money-bill mr-2"></i>Budget (Rp)
                            </label>
                            <input type="number" name="budget" id="budget" min="0" step="1000" value="<?php echo $order['budget']; ?>" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors">
                        </div>

                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Deadline
                            </label>
                            <input type="date" name="deadline" id="deadline" value="<?php echo $order['deadline']; ?>" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors">
                        </div>
                    </div>

                    <!-- WhatsApp info from profile -->
                    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                        <p class="text-sm text-gray-300">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Nomor WhatsApp menggunakan nomor pada profil Anda:
                            <span class="font-semibold text-white">
                                <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Belum diisi'; ?>
                            </span>
                        </p>
                        <p class="text-xs text-gray-400 mt-2">
                            Untuk mengubah nomor, silakan perbarui di <a class="text-amber-400 hover:text-amber-300 underline" href="../../edit-profile.php">Edit Profil</a>.
                        </p>
                    </div>

                    <!-- Additional Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-sticky-note mr-2"></i>Catatan Tambahan
                        </label>
                        <textarea name="notes" id="notes" rows="3" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:border-amber-400 transition-colors resize-y"><?php echo htmlspecialchars($order['notes']); ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4">
                        <button type="submit" class="bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-600 hover:to-amber-700 text-black font-bold py-3 px-8 rounded-full transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/30 flex items-center gap-3">
                            <i class="fas fa-save"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-lock text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">Pesanan Tidak Dapat Diedit</h3>
                    <p class="text-gray-400 mb-6">Pesanan dengan status "<?php echo ucfirst($order['status']); ?>" tidak dapat diedit.</p>
                    <a href="../../my-orders.php" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Pesanan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/Desainin.js"></script>
</body>
</html>
