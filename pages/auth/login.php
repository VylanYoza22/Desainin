<?php
/**
 * Login Page
 * User authentication with username/email and password
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

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_email = sanitizeInput($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username_email) || empty($password)) {
        $error = "Username/Email dan Password harus diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        try {
            // Check user in database (can login with username or email)
            $stmt = $conn->prepare("SELECT id, username, email, password, full_name FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Login successful - create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['login_time'] = time();
                    
                    // Redirect to dashboard or requested page
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../user/dashboard.php';
                    header("Location: " . $redirect);
                    exit();
                } else {
                    $error = "Password salah!";
                }
            } else {
                $error = "Username/Email tidak ditemukan atau akun tidak aktif!";
            }
            $stmt->close();
        } catch (Exception $e) {
            logError("Login error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>

<?php
$pageTitle = 'Login';
$pageDescription = 'Masuk ke akun Desainin Anda';
$cssPath = '../../assets/css/Style-Desainin-dark.css';
$rootPath = '../../';
$additionalHead = '
<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
    .login-container { background: radial-gradient(1200px 600px at -10% -10%, rgba(245,158,11,.18), transparent 60%), radial-gradient(1000px 500px at 110% 10%, rgba(251,191,36,.12), transparent 55%), #0e1625; min-height: 100vh; }
    .card { background: #111827; border: 1px solid #1f2937; }
    .input { background:#0b1020; border:1px solid #1f2937; }
    .input:focus { outline:none; border-color:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,.25); }
    .input-icon { color:#9ca3af; }
    .divider { position: relative; text-align:center; }
    .divider:before { content:""; position:absolute; left:0; right:0; top:50%; height:1px; background:#1f2937; }
    .divider span { position:relative; background:#111827; padding:0 .75rem; color:#9ca3af; font-size:.875rem; }
</style>';
include '../../includes/header.php';
?>

<div class="login-container p-8 flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Masuk ke Desainin</h1>
            <div class="flex items-center justify-center gap-2 mb-4">
                <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-palette text-white text-lg"></i>
                </div>
                <span class="text-xl font-semibold text-white">Desainin</span>
            </div>
            <p class="text-gray-300">Masuk untuk melanjutkan aktivitas Anda</p>
        </div>

        <!-- Login Form -->
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

                <!-- Username/Email Input -->
                <div>
                    <label for="username_email" class="block text-sm font-medium text-gray-300 mb-2">Username atau Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center input-icon">
                            <i class="fas fa-user"></i>
                        </span>
                        <input 
                            type="text" 
                            id="username_email" 
                            name="username_email" 
                            required
                            class="input w-full pl-10 pr-4 py-3 rounded-lg text-white placeholder-gray-400 transition"
                            placeholder="Masukkan username atau email"
                            value="<?php echo isset($_POST['username_email']) ? htmlspecialchars($_POST['username_email']) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
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
                            placeholder="Masukkan password"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
                        >
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-gray-300">
                        <input type="checkbox" name="remember" class="mr-2 rounded bg-white/10 border-white/20">
                        Ingat saya
                    </label>
                    <a href="#" class="text-sm text-amber-400 hover:text-amber-300 transition-colors">
                        Lupa password?
                    </a>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="w-full h-12 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-600 hover:to-amber-500 text-black font-semibold rounded-lg transition"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Masuk
                </button>
            </form>

            <!-- Divider -->
            <div class="my-6 divider"><span>atau</span></div>

            <!-- Secondary Action: Register -->
            <a href="register.php" class="block w-full h-12 border border-amber-500/60 hover:border-amber-500 text-amber-400 hover:text-black rounded-lg text-center leading-[3rem] transition bg-transparent hover:bg-amber-500/20">
                Buat akun baru
            </a>

            <!-- Back to Home -->
            <div class="mt-4 text-center">
                <a href="../../index.php" class="text-gray-400 hover:text-white transition-colors text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke beranda
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$additionalScripts = '
<script>
function togglePassword() {
    const passwordInput = document.getElementById("password");
    const passwordIcon = document.getElementById("password-icon");
    
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

// Auto focus and form enhancements
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("username_email").focus();
    
    // Add loading state to submit button
    const form = document.querySelector("form");
    const submitBtn = document.querySelector("button[type=submit]");
    
    form.addEventListener("submit", function() {
        submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin mr-2\"></i>Memproses...";
        submitBtn.disabled = true;
    });
});
</script>';
include '../../includes/footer.php';
?>
