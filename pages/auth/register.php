<?php
/**
 * Register Page
 * User registration with validation and security features
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/helpers.php';
require_once '../../config/error_handler.php';

$error = '';
$success = '';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // Comprehensive validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Semua field wajib harus diisi!";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username harus 3-20 karakter!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username hanya boleh huruf, angka, dan underscore!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif ($phone && !validateWhatsAppNumber($phone)) {
        $error = "Format nomor telepon tidak valid!";
    } elseif (!isset($_POST['terms'])) {
        $error = "Anda harus menyetujui syarat dan ketentuan!";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username atau email sudah terdaftar!";
            } else {
                // Hash password with strong options
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

                // Determine role: if no admin exists yet, this user becomes admin, else user
                $role = 'user';
                $roleCheck = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
                if ($roleCheck && ($rowC = $roleCheck->fetch_assoc())) {
                    if ((int)$rowC['c'] === 0) { $role = 'admin'; }
                }
                
                // Insert new user with role
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $role);
                
                if ($stmt->execute()) {
                    if ($role === 'admin') {
                        // Auto-login and redirect admin to admin dashboard
                        $_SESSION['user_id'] = $conn->insert_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['role'] = 'admin';
                        $_SESSION['login_time'] = time();
                        header('Location: ../admin/index.php');
                        exit();
                    }
                    $success = "Registrasi berhasil! Silakan login dengan akun baru Anda.";
                    // Redirect to login after 3 seconds
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            logError("Registration error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>

<?php
$pageTitle = 'Register';
$pageDescription = 'Buat akun Desainin baru';
$cssPath = '../../assets/css/Style-Desainin-dark.css';
$rootPath = '../../';
$additionalHead = '
<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
    .register-container { background: radial-gradient(1200px 600px at -10% -10%, rgba(245,158,11,.18), transparent 60%), radial-gradient(1000px 500px at 110% 10%, rgba(251,191,36,.12), transparent 55%), #0e1625; min-height: 100vh; }
    .card { background: #111827; border: 1px solid #1f2937; }
    .input { background:#0b1020; border:1px solid #1f2937; }
    .input:focus { outline:none; border-color:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,.25); }
    .password-strength {
        height: 4px;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .brand-gradient { background: #f59e0b; }
    .input-icon { color:#9ca3af; }
</style>';
include '../../includes/header.php';
?>

<div class="register-container p-8 flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Join Desainin</h1>
            <div class="flex items-center justify-center gap-2 mb-4">
                <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-palette text-white text-lg"></i>
                </div>
                <span class="text-xl font-semibold text-white">Desainin</span>
            </div>
            <p class="text-gray-300">Buat akun Desainin baru</p>
        </div>

        <!-- Register Form -->
        <div class="card rounded-xl p-8 shadow-lg">
            <form method="POST" action="" class="space-y-6">
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

                <!-- Full Name Input -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-300 mb-2">Nama Lengkap *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            required
                            class="input w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="Masukkan nama lengkap"
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Username Input -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-user"></i>
                        </span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="input w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="Minimal 3 karakter"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="input w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="nama@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Phone Input -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">WhatsApp (Opsional)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fab fa-whatsapp"></i>
                        </span>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="input w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="08xxxxxxxxxx atau 62xxxxxxxxxx"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Untuk notifikasi pesanan via WhatsApp</p>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input w-full pl-10 pr-12 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="Minimal 6 karakter"
                        >
                        <!-- Password Strength Indicator -->
                        <div class="mt-2">
                            <div id="password-strength" class="password-strength bg-gray-600"></div>
                            <p id="password-text" class="text-xs text-gray-400 mt-1">Kekuatan password</p>
                        </div>
                        <button 
                            type="button" 
                            onclick="togglePassword('password')" 
                            class="absolute right-3 top-3 text-gray-400 hover:text-white transition-colors"
                        >
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            class="input w-full pl-10 pr-12 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="Ulangi password"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword('confirm_password')" 
                            class="absolute right-3 top-3 text-gray-400 hover:text-white transition-colors"
                        >
                            <i id="confirm-password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Terms Agreement -->
                <div class="flex items-start">
                    <input 
                        type="checkbox" 
                        id="terms" 
                        name="terms" 
                        required
                        class="mt-1 mr-3 rounded bg-white/10 border-white/20"
                    >
                    <label for="terms" class="text-sm text-gray-300">
                        Saya setuju dengan 
                        <a href="#" class="text-amber-400 hover:text-amber-300 transition-colors">Syarat & Ketentuan</a> 
                        dan 
                        <a href="#" class="text-amber-400 hover:text-amber-300 transition-colors">Kebijakan Privasi</a>
                    </label>
                </div>

                <!-- Register Button -->
                <button 
                    type="submit" 
                    class="w-full h-12 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-600 hover:to-amber-500 text-black font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-400/40"
                >
                    <i class="fas fa-user-plus mr-2"></i>
                    Daftar Sekarang
                </button>
            </form>

        </div>
        <!-- Back to Home & Login -->
        <div class="mt-6 text-center">
            <p class="text-gray-300">
                Sudah punya akun?
                <a href="login.php" aria-label="Masuk ke akun" class="text-blue-400 hover:text-blue-300 font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400/30 rounded">Masuk di sini</a>
            </p>
            <a href="../../index.php" aria-label="Kembali ke beranda" class="inline-flex items-center mt-3 text-gray-400 hover:text-white transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/20 rounded">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke beranda
            </a>
        </div>
    </div>
</div>

<?php
$additionalScripts = '
<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const iconId = fieldId === "password" ? "password-icon" : "confirm-password-icon";
    const passwordIcon = document.getElementById(iconId);
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        passwordIcon.classList.remove("fa-eye");
        passwordIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        passwordIcon.classList.remove("fa-eye-slash");
        passwordIcon.classList.add("fa-eye");
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    let text = "";
    let color = "";
    
    if (password.length >= 6) strength += 1;
    if (password.length >= 8) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    switch(strength) {
        case 0:
        case 1:
            text = "Sangat lemah";
            color = "bg-red-500";
            break;
        case 2:
        case 3:
            text = "Lemah";
            color = "bg-orange-500";
            break;
        case 4:
            text = "Sedang";
            color = "bg-yellow-500";
            break;
        case 5:
        case 6:
            text = "Kuat";
            color = "bg-green-500";
            break;
    }
    
    return { strength, text, color };
}

// Real-time validation
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("full_name").focus();
    
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm_password");
    const strengthBar = document.getElementById("password-strength");
    const strengthText = document.getElementById("password-text");
    const form = document.querySelector("form");
    const submitBtn = document.querySelector("button[type=submit]");
    
    // Password strength indicator
    passwordInput.addEventListener("input", function() {
        const result = checkPasswordStrength(this.value);
        strengthBar.className = `password-strength ${result.color}`;
        strengthBar.style.width = `${(result.strength / 6) * 100}%`;
        strengthText.textContent = result.text;
    });
    
    // Confirm password validation
    confirmPasswordInput.addEventListener("input", function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.style.borderColor = "#ef4444";
        } else {
            this.style.borderColor = "";
        }
    });
    
    // Form submission with loading state
    form.addEventListener("submit", function() {
        submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin mr-2\"></i>Mendaftar...";
        submitBtn.disabled = true;
    });
});
</script>';
include '../../includes/footer.php';
?>
