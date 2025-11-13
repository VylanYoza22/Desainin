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
            $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
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
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $_SESSION['login_time'] = time();
                    
                    // Update login tracking
                    try {
                        $upd = $conn->prepare("UPDATE users SET last_login = NOW(), is_online = 1 WHERE id = ?");
                        $upd->bind_param("i", $user['id']);
                        $upd->execute();
                        $upd->close();
                    } catch (Exception $ie) { /* ignore tracking errors */ }
                    
                    // Redirect: if admin, go to admin dashboard; else respect redirect or go to user dashboard
                    if (isset($user['role']) && strtolower($user['role']) === 'admin') {
                        $redirect = '../admin/index.php';
                    } else {
                        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../user/dashboard.php';
                    }
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
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap");
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }
    
    body { 
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
    }
    
    .login-container { 
        background: linear-gradient(135deg, #0a0a1a 0%, #16213e 50%, #0f3460 100%);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        min-height: 100vh;
        position: relative;
    }
    
    .login-container::before {
        content: "";
        position: absolute;
        inset: 0;
        background: 
            radial-gradient(1200px 600px at 20% 20%, rgba(245,158,11,.15), transparent 60%),
            radial-gradient(1000px 500px at 80% 80%, rgba(251,191,36,.1), transparent 55%);
        pointer-events: none;
    }
    
    .login-card {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .card { 
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 30px rgba(245, 158, 11, 0.1);
        transition: all 0.3s;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4), 0 0 40px rgba(245, 158, 11, 0.15);
        border-color: rgba(245, 158, 11, 0.2);
    }
    
    .input { 
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .input:focus { 
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245,158,11,.25), 0 8px 20px rgba(245, 158, 11, 0.15);
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.08);
    }
    
    .input-icon { 
        color: #fbbf24;
        transition: all 0.3s;
    }
    
    .input:focus + .input-icon {
        color: #f59e0b;
    }
    
    .divider { 
        position: relative;
        text-align: center;
    }
    
    .divider:before { 
        content: \"\";
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(245,158,11,0.3), transparent);
    }
    
    .divider span { 
        position: relative;
        background: rgba(255, 255, 255, 0.03);
        padding: 0 1rem;
        color: #9ca3af;
        font-size: .875rem;
        font-weight: 600;
    }
    
    .btn-primary {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-primary::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .btn-primary:hover::before {
        left: 100%;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(245, 158, 11, 0.4);
    }
    
    .btn-primary:active {
        transform: translateY(0);
    }
    
    .logo-container {
        animation: float 3s ease-in-out infinite;
    }
    
    .alert {
        animation: fadeInUp 0.4s ease-out;
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
</style>';
include '../../includes/header.php';
?>

<div class="login-container p-8 flex items-center justify-center relative">
    <div class="w-full max-w-md login-card">
        <!-- Logo/Header -->
        <div class="text-center mb-10">
            <div class="logo-container flex items-center justify-center gap-3 mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-yellow-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <i class="fas fa-palette text-white text-2xl"></i>
                </div>
                <span class="text-3xl font-bold text-white">Desainin</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-white via-amber-400 to-yellow-500 bg-clip-text text-transparent">Selamat Datang</h1>
            <p class="text-gray-400 text-lg">Masuk untuk melanjutkan aktivitas Anda</p>
        </div>

        <!-- Login Form -->
        <div class="card rounded-3xl p-10 shadow-2xl">
            <form method="POST" action="" class="space-y-6">
                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="alert bg-red-500/20 border-2 border-red-500/50 text-red-200 px-5 py-4 rounded-xl flex items-center gap-3 shadow-lg">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold">Error</p>
                            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert bg-green-500/20 border-2 border-green-500/50 text-green-200 px-5 py-4 rounded-xl flex items-center gap-3 shadow-lg">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold">Berhasil</p>
                            <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Username/Email Input -->
                <div>
                    <label for="username_email" class="block text-sm font-bold text-gray-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-user text-amber-400"></i>
                        Username atau Email
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="username_email" 
                            name="username_email" 
                            required
                            class="input w-full pl-12 pr-4 py-4 rounded-xl text-white placeholder-gray-400 transition font-medium"
                            placeholder="Masukkan username atau email"
                            value="<?php echo isset($_POST['username_email']) ? htmlspecialchars($_POST['username_email']) : ''; ?>"
                        >
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center input-icon">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-lock text-amber-400"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input w-full pl-12 pr-14 py-4 rounded-xl text-white placeholder-gray-400 transition font-medium"
                            placeholder="Masukkan password"
                        >
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center input-icon">
                            <i class="fas fa-lock"></i>
                        </span>
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-amber-400 transition-colors"
                        >
                            <i id="password-icon" class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-gray-300 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" name="remember" class="mr-2 w-4 h-4 rounded bg-white/10 border-white/20 text-amber-500 focus:ring-2 focus:ring-amber-400/30">
                        <span class="font-semibold">Ingat saya</span>
                    </label>
                    <a href="#" class="text-sm text-amber-400 hover:text-amber-300 transition-colors font-semibold">
                        <i class="fas fa-question-circle mr-1"></i>
                        Lupa password?
                    </a>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="btn-primary w-full py-4 bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 hover:from-amber-600 hover:via-yellow-600 hover:to-amber-700 text-black font-bold rounded-xl transition focus:outline-none focus:ring-2 focus:ring-amber-400/40 shadow-lg text-lg flex items-center justify-center gap-3"
                >
                    <i class="fas fa-sign-in-alt text-xl"></i>
                    <span>Masuk Sekarang</span>
                    <i class="fas fa-arrow-right text-xl"></i>
                </button>
            </form>

            <!-- Divider -->
            <div class="my-8 divider"><span>ATAU</span></div>

            <!-- Secondary Action: Register -->
            <a href="register.php" aria-label="Buat akun baru" class="block w-full py-4 border-2 border-amber-500/40 hover:border-amber-500 text-amber-400 hover:text-black rounded-xl text-center transition bg-transparent hover:bg-gradient-to-r hover:from-amber-500/20 hover:to-yellow-500/20 focus:outline-none focus:ring-2 focus:ring-amber-400/30 font-bold text-lg flex items-center justify-center gap-2 shadow-lg">
                <i class="fas fa-user-plus"></i>
                <span>Buat Akun Baru</span>
            </a>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="../../index.php" aria-label="Kembali ke beranda" class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400/20 rounded-lg px-4 py-2 hover:bg-white/5">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke beranda</span>
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
