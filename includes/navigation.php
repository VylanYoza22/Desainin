<?php
/**
 * Navigation Component
 * Reusable navigation bar for all pages
 */

// Determine current page for active states
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Set root path based on current location
if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $rootPath = '../../';
} else {
    $rootPath = '';
}

// Get user data for navigation if logged in
$navUser = null;
if (isset($_SESSION['user_id'])) {
    require_once $rootPath . 'config/database.php';
    $stmt = $conn->prepare("SELECT id, username, full_name, email, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $navUser = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!-- Navigation -->
<nav class="bg-gray-900/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Brand -->
            <div class="flex items-center">
                <a href="<?php echo $rootPath; ?>index.php" class="text-2xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                    <i class="fas fa-palette mr-2"></i>Desainin
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $rootPath; ?>pages/user/dashboard.php" 
                       class="text-gray-300 hover:text-white transition-colors <?php echo $currentPage == 'dashboard.php' ? 'text-amber-400' : ''; ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="<?php echo $rootPath; ?>order.php" 
                       class="text-gray-300 hover:text-white transition-colors <?php echo $currentPage == 'order.php' ? 'text-amber-400' : ''; ?>">
                        <i class="fas fa-plus mr-2"></i>Pesanan Baru
                    </a>
                    <a href="<?php echo $rootPath; ?>my-orders.php" 
                       class="text-gray-300 hover:text-white transition-colors <?php echo $currentPage == 'my-orders.php' ? 'text-amber-400' : ''; ?>">
                        <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center text-gray-300 hover:text-white transition-colors">
                            <?php 
                            // Force fresh profile picture data from database
                            $navProfilePic = '';
                            if (isset($_SESSION['user_id'])) {
                                $nav_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $nav_stmt->bind_param("i", $_SESSION['user_id']);
                                $nav_stmt->execute();
                                $nav_result = $nav_stmt->get_result();
                                if ($nav_result->num_rows > 0) {
                                    $nav_data = $nav_result->fetch_assoc();
                                    $navProfilePic = $nav_data['profile_picture'];
                                }
                                $nav_stmt->close();
                            }
                            ?>
                            <?php if ($navProfilePic): ?>
                                <img src="<?php echo htmlspecialchars($navProfilePic); ?>" 
                                     alt="Profile" 
                                     class="w-8 h-8 rounded-full object-cover mr-2"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     onload="this.style.display='block'; this.nextElementSibling.style.display='none';">
                            <?php endif; ?>
                            <div class="w-8 h-8 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-2" style="<?php echo $navProfilePic ? 'display: none;' : ''; ?>">
                                <?php echo strtoupper(substr($navUser['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($navUser['username'] ?? $_SESSION['username'] ?? 'User'); ?>
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        
                        <div class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="<?php echo $rootPath; ?>pages/user/show-profile.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-t-lg">
                                <i class="fas fa-eye mr-2"></i>Lihat Profil
                            </a>
                            <a href="<?php echo $rootPath; ?>settings.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                <i class="fas fa-cog mr-2"></i>Pengaturan
                            </a>
                            <hr class="border-gray-700">
                            <a href="<?php echo $rootPath; ?>pages/auth/logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700 hover:text-red-300 rounded-b-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $rootPath; ?>pages/auth/login.php" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="<?php echo $rootPath; ?>pages/auth/register.php" class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Daftar
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="text-gray-300 hover:text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div id="mobile-menu" class="md:hidden bg-gray-800 border-t border-gray-700 hidden">
        <div class="px-4 py-2 space-y-2">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $rootPath; ?>pages/user/dashboard.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="<?php echo $rootPath; ?>order.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-plus mr-2"></i>Pesanan Baru
                </a>
                <a href="<?php echo $rootPath; ?>my-orders.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                </a>
                <a href="<?php echo $rootPath; ?>pages/user/profile.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-user mr-2"></i>Profil
                </a>
                <a href="<?php echo $rootPath; ?>settings.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-cog mr-2"></i>Pengaturan
                </a>
                <a href="<?php echo $rootPath; ?>pages/auth/logout.php" class="block py-2 text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php else: ?>
                <a href="<?php echo $rootPath; ?>pages/auth/login.php" class="block py-2 text-gray-300 hover:text-white">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <a href="<?php echo $rootPath; ?>pages/auth/register.php" class="block py-2 text-amber-400 hover:text-amber-300">
                    <i class="fas fa-user-plus mr-2"></i>Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
});
</script>
