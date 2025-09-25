<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($username)) {
        $error = 'Nama lengkap, email, dan username harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username atau email sudah digunakan!';
        } else {
            // Handle profile picture upload
            $profile_picture = $user['profile_picture']; // Keep existing if no new upload
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_picture']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    if ($_FILES['profile_picture']['size'] <= 5000000) { // 5MB max
                        $upload_dir = 'uploads/profiles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                            // Delete old profile picture if exists
                            if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                                unlink($user['profile_picture']);
                            }
                            $profile_picture = $upload_path;
                        } else {
                            $error = 'Gagal mengupload foto profil!';
                        }
                    } else {
                        $error = 'Ukuran file terlalu besar! Maksimal 5MB.';
                    }
                } else {
                    $error = 'Format file tidak didukung! Gunakan JPG, JPEG, PNG, atau GIF.';
                }
            }
            
            // Add whatsapp_number column if it doesn't exist
            $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(15) DEFAULT NULL");
            
            // Update user data if no errors
            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, whatsapp_number = ?, profile_picture = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $full_name, $email, $username, $whatsapp_number, $profile_picture, $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Profil berhasil diperbarui!';
                    // Refresh user data
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['username'] = $username;
                    $user['whatsapp_number'] = $whatsapp_number;
                    $user['profile_picture'] = $profile_picture;
                } else {
                    $error = 'Gagal memperbarui profil!';
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Desainin</title>
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
                    <a href="my-orders.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-list mr-2"></i>Pesanan Saya
                    </a>
                    <a href="pages/auth/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-white to-amber-400 bg-clip-text text-transparent">
                <i class="fas fa-user-edit mr-3"></i>Edit Profil
            </h1>
            <p class="text-gray-400">Perbarui informasi profil Anda</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
        <?php endif; ?>

        <!-- Profile Form -->
        <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-8">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Profile Picture Section -->
                <div class="text-center mb-8">
                    <div class="relative inline-block">
                        <img src="<?php echo $user['profile_picture'] ? $user['profile_picture'] : 'https://via.placeholder.com/150x150/374151/9CA3AF?text=No+Photo'; ?>" 
                             alt="Profile Picture" 
                             class="w-32 h-32 rounded-full object-cover border-4 border-amber-400/30 mx-auto mb-4"
                             id="profilePreview">
                        <label for="profile_picture" class="absolute bottom-0 right-0 bg-amber-600 hover:bg-amber-700 text-white p-2 rounded-full cursor-pointer transition-colors">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden" onchange="previewImage(this)">
                    <p class="text-sm text-gray-400 mt-2">Klik ikon kamera untuk mengubah foto profil</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-user mr-2"></i>Nama Lengkap
                        </label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-at mr-2"></i>Username
                        </label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" 
                               required>
                    </div>

                    <!-- WhatsApp Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fab fa-whatsapp mr-2"></i>Nomor WhatsApp
                        </label>
                        <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>" 
                               placeholder="628123456789" 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                    <button type="button" onclick="window.location.href='http://localhost/PKK2/index.php'" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors text-center">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/Desainin.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Phone number formatting
        document.querySelector('input[name="whatsapp_number"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 13) {
                value = value.slice(0, 13);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
