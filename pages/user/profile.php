<?php
session_start();
require_once '../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data user dari database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($full_name) || empty($username) || empty($email)) {
        $error = "Nama lengkap, username, dan email harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Cek apakah username atau email sudah digunakan user lain
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username atau email sudah digunakan user lain!";
        } else {
            // Handle profile picture upload
            $profile_picture = $user['profile_picture'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_picture']['type'];
                $file_size = $_FILES['profile_picture']['size'];
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Format file harus JPG, JPEG, PNG, atau GIF!";
                } elseif ($file_size > 5 * 1024 * 1024) { // 5MB
                    $error = "Ukuran file maksimal 5MB!";
                } else {
                    // Create uploads directory if not exists
                    $upload_dir = '../../uploads/profiles/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        // Delete old profile picture if exists
                        if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                            unlink($user['profile_picture']);
                        }
                        $profile_picture = $upload_path;
                    } else {
                        $error = "Gagal mengupload foto profil!";
                    }
                }
            }
            
            if (!$error) {
                // Update password jika diisi
                $update_password = false;
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = "Password saat ini harus diisi untuk mengubah password!";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error = "Password saat ini salah!";
                    } elseif (strlen($new_password) < 6) {
                        $error = "Password baru minimal 6 karakter!";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "Konfirmasi password baru tidak cocok!";
                    } else {
                        $update_password = true;
                    }
                }
                
                if (!$error) {
                    // Update data user
                    if ($update_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, password = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $hashed_password, $profile_picture, $user_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $profile_picture, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        // Update session data including profile picture
                        $_SESSION['username'] = $username;
                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['email'] = $email;
                        $_SESSION['profile_picture'] = $profile_picture;
                        
                        $success = "Profil berhasil diperbarui!";
                        
                        // Close the update statement first
                        $stmt->close();
                        
                        // Refresh user data with new statement
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    } else {
                        $error = "Terjadi kesalahan saat memperbarui profil!";
                        $stmt->close();
                    }
                }
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
        .input-glow:focus {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">
                <i class="fas fa-user-edit text-amber-400"></i> Edit Profil
            </h1>
            <p class="text-gray-300">Perbarui informasi profil Anda</p>
        </div>

        <!-- Edit Profile Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-500/20 border border-green-500/50 text-green-200 px-4 py-3 rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Picture Section -->
                <div class="text-center">
                    <div class="mb-4">
                        <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-preview mx-auto" id="profilePreview">
                        <?php else: ?>
                            <div class="profile-preview mx-auto bg-gradient-to-r from-amber-500 to-yellow-600 flex items-center justify-center text-white text-4xl font-bold" id="profilePreview">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="profile_picture" class="cursor-pointer bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center">
                            <i class="fas fa-camera mr-2"></i>
                            Ubah Foto Profil
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <p class="text-gray-400 text-xs mt-2">JPG, JPEG, PNG, GIF (Max: 5MB)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name Input -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-id-card mr-2"></i>Nama Lengkap *
                        </label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                            placeholder="Masukkan nama lengkap"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>"
                        >
                    </div>

                    <!-- Username Input -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-user mr-2"></i>Username *
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                            placeholder="Minimal 3 karakter"
                            value="<?php echo htmlspecialchars($user['username']); ?>"
                        >
                    </div>

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email *
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                            placeholder="nama@email.com"
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                        >
                    </div>

                    <!-- Phone Input -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-phone mr-2"></i>No. Telepon
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                            placeholder="08xxxxxxxxxx"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <!-- Password Section -->
                <div class="border-t border-white/20 pt-6">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-key mr-2"></i>Ubah Password (Opsional)
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">
                                Password Saat Ini
                            </label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                                placeholder="Password lama"
                            >
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">
                                Password Baru
                            </label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                                placeholder="Minimal 6 karakter"
                            >
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">
                                Konfirmasi Password
                            </label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 input-glow transition-all duration-300"
                                placeholder="Ulangi password baru"
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button 
                        type="submit" 
                        class="flex-1 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                    
                    <a 
                        href="dashboard.php" 
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 text-center"
                    >
                        <i class="fas fa-times mr-2"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('profilePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Create new img element or update existing one
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace div with img
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Picture';
                        img.className = 'profile-preview mx-auto';
                        img.id = 'profilePreview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '';
            }
        });
    </script>
</body>
</html>
