<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in - always fetch fresh data from database
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id, username, full_name, email, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Update session with fresh data
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desainin - Jasa Edit Video & Desain Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/css/Style-Desainin-dark.css">
    <link rel="stylesheet" href="assets/css/promo-popup.css">
</head>

<body class="bg-black text-white font-sans overflow-x-hidden">
    <!-- Animated Background -->
    <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
    <div class="particles fixed inset-0 -z-10 pointer-events-none" id="particles"></div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar fixed left-0 top-0 h-full w-64 bg-gray-900 border-r border-gray-800 z-50 -translate-x-full lg:translate-x-0 transition-all duration-300" id="sidebar">
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="sidebar-header flex items-center justify-between p-4 border-b border-gray-800">
                <?php if ($user): ?>
                    <!-- User Profile Section -->
                    <div class="flex items-center space-x-3 flex-1 cursor-pointer hover:bg-gray-800 rounded-lg p-2 transition-colors" onclick="toggleUserDropdown()">
                        <?php 
                        $sidebarImageSrc = '';
                        if ($user['profile_picture']) {
                            // Clean up the path - remove any ../ prefixes and ensure proper web path
                            $cleanPath = str_replace('../../', '', $user['profile_picture']);
                            $sidebarImageSrc = $cleanPath;
                        }
                        ?>
                        <?php if ($sidebarImageSrc): ?>
                            <img src="<?php echo htmlspecialchars($sidebarImageSrc); ?>" 
                                 alt="Profile Picture" 
                                 class="w-10 h-10 rounded-full object-cover user-avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 onload="this.style.display='block'; this.nextElementSibling.style.display='none';">
                        <?php endif; ?>
                        <div class="w-10 h-10 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-lg user-avatar" style="<?php echo $sidebarImageSrc ? 'display: none;' : ''; ?>">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <div class="flex-1 min-w-0 sidebar-text">
                            <div class="text-white font-semibold truncate"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                            <div class="text-gray-400 text-xs">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform sidebar-text" id="userDropdownIcon"></i>
                    </div>
                <?php else: ?>
                    <!-- Default Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-palette text-white text-sm"></i>
                        </div>
                        <span class="text-white font-semibold sidebar-text">Desainin</span>
                    </div>
                <?php endif; ?>
                
                <div class="flex items-center space-x-2">
                    <button class="hidden lg:block text-gray-400 hover:text-white transition-colors" id="toggleSidebar" title="Toggle Sidebar">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="lg:hidden text-gray-400 hover:text-white" id="closeSidebar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <?php if ($user): ?>
            <!-- User Dropdown Menu -->
            <div class="hidden bg-gray-800 border-b border-gray-700" id="userDropdown">
                <div class="p-3 space-y-2">
                    <a href="pages/user/show-profile.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        <i class="fas fa-eye w-5"></i>
                        <span>Lihat Profil</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        <i class="fas fa-cog w-5"></i>
                        <span>Pengaturan</span>
                    </a>
                    <a href="my-orders.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-all duration-200">
                        <i class="fas fa-history w-5"></i>
                        <span>Riwayat Pesanan</span>
                    </a>
                    <hr class="border-gray-700 my-2">
                    <a href="pages/auth/logout.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition-all duration-200">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Navigation Items -->
            <div class="flex-1 py-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">
                <div class="px-3 mb-6">
                    <a href="#home" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-home w-5"></i>
                        <span class="sidebar-text">Home</span>
                    </a>
                </div>

                <div class="px-3 space-y-1">
                    <a href="#services" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-cogs w-5"></i>
                        <span class="sidebar-text">Layanan</span>
                    </a>
                </div>

                <div class="px-3 space-y-1 mt-6">
                    <div class="sidebar-text text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">Portfolio</div>
                    <a href="#portfolio" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-folder w-5"></i>
                        <span class="sidebar-text">Our Work</span>
                    </a>
                    <a href="#pricing" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-tag w-5"></i>
                        <span class="sidebar-text">Pricing</span>
                    </a>
                </div>

                <?php if (!$user): ?>
                <div class="px-3 space-y-1 mt-6">
                    <div class="sidebar-text text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">Account</div>
                    <a href="pages/auth/login.php" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-sign-in-alt w-5"></i>
                        <span class="sidebar-text">Login</span>
                    </a>
                    <a href="pages/auth/register.php" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-user-plus w-5"></i>
                        <span class="sidebar-text">Register</span>
                    </a>
                </div>
                <?php endif; ?>

                <div class="px-3 space-y-1 mt-6">
                    <a href="#testimonials" class="nav-item flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-all duration-200">
                        <i class="fas fa-star w-5"></i>
                        <span class="sidebar-text">Pengalaman</span>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="sidebar-footer p-4 border-t border-gray-800">
                <?php if ($user): ?>
                    <a href="order.php" class="sidebar-footer-btn w-full bg-gradient-to-r from-amber-600 to-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:from-green-700 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i>
                        <span class="sidebar-text">Get Started</span>
                    </a>
                <?php else: ?>
                    <a href="register.php" class="sidebar-footer-btn w-full bg-gradient-to-r from-amber-600 to-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:from-green-700 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i>
                        <span class="sidebar-text">Get Started</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-gray-900 border-b border-gray-800 z-40">
        <div class="flex items-center justify-between px-4 py-3">
            <button class="text-gray-400 hover:text-white" id="openSidebar">
                <i class="fas fa-bars text-lg"></i>
            </button>
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-gradient-to-r from-amber-500 to-yellow-600 rounded flex items-center justify-center">
                    <i class="fas fa-palette text-white text-xs"></i>
                </div>
                <span class="text-white font-semibold">Desainin</span>
            </div>
            <div class="w-6"></div>
        </div>
    </header>

    <!-- Overlay -->
    <div class="sidebar-overlay fixed inset-0 bg-black/50 z-40 opacity-0 invisible lg:hidden transition-all duration-300" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Hero Section -->
        <section id="home" class="pt-24 pb-20 relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-6 text-center">
                <!-- Main Content -->
                <div class="relative z-10">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                        Creative Design &
                        <span class="bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">
                            Video Editing
                        </span>
                    </h1>
                    
                    <p class="text-lg md:text-xl text-gray-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                        Transform your ideas into stunning visuals. Professional design and video editing services 
                        that bring your vision to life.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                        <a href="#services" class="bg-gradient-to-r from-amber-600 to-yellow-600 text-white px-8 py-4 rounded-lg font-semibold hover:from-amber-700 hover:to-yellow-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                            View Services
                        </a>
                        <a href="#portfolio" class="border border-gray-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/5 hover:border-gray-500 transition-all duration-200">
                            See Portfolio
                        </a>
                    </div>
                </div>
                
                <!-- Feature Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-20">
                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300">
                        <div class="w-16 h-16 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-video text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Video Editing</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Professional video editing for social media, YouTube, and marketing content.</p>
                    </div>
                    
                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300">
                        <div class="w-16 h-16 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-palette text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Graphic Design</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Eye-catching designs for logos, posters, social media, and branding materials.</p>
                    </div>
                    
                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300">
                        <div class="w-16 h-16 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-mobile-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">Social Media</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">Complete social media content packages to boost your online presence.</p>
                    </div>
                </div>
            </div>
        </section>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-white/5">
        <div class="container mx-auto px-5">
            <h2 class="text-5xl font-bold text-center mb-4 bg-gradient-to-r from-white to-primary bg-clip-text text-transparent">
                Layanan Kami
            </h2>
            <p class="text-xl text-center mb-12 opacity-80 max-w-2xl mx-auto">
                Berbagai jasa kreatif untuk kebutuhan digital Anda
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mt-12">
                <div class="service-card bg-white/5 backdrop-blur-glass border border-white/10 rounded-3xl p-10 text-center transition-all duration-300 hover:-translate-y-2 hover:bg-white/10 hover:shadow-xl hover:shadow-primary/20 cursor-pointer" data-service="video">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-primary rounded-full flex items-center justify-center text-white text-3xl">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-6 text-white">Edit Video</h3>
                    <ul class="text-left mb-8 space-y-2">
                        <li class="py-2 opacity-80 border-b border-white/10">Video TikTok/Reels</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Video YouTube</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Video Presentasi</li>
                        <li class="py-2 opacity-80">Wedding Video</li>
                    </ul>
                    <p class="text-xl font-bold bg-gradient-primary bg-clip-text text-transparent">
                        Mulai dari Rp15.000
                    </p>
                </div>

                <div class="service-card bg-white/5 backdrop-blur-glass border border-white/10 rounded-3xl p-10 text-center transition-all duration-300 hover:-translate-y-2 hover:bg-white/10 hover:shadow-xl hover:shadow-primary/20 cursor-pointer" data-service="design">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-primary rounded-full flex items-center justify-center text-white text-3xl">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-6 text-white">Desain Grafis</h3>
                    <ul class="text-left mb-8 space-y-2">
                        <li class="py-2 opacity-80 border-b border-white/10">Logo & Branding</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Poster & Banner</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Feed Instagram</li>
                        <li class="py-2 opacity-80">Thumbnail YouTube</li>
                    </ul>
                    <p class="text-xl font-bold bg-gradient-primary bg-clip-text text-transparent">
                        Mulai dari Rp10.000
                    </p>
                </div>

                <div class="service-card bg-white/5 backdrop-blur-glass border border-white/10 rounded-3xl p-10 text-center transition-all duration-300 hover:-translate-y-2 hover:bg-white/10 hover:shadow-xl hover:shadow-primary/20 cursor-pointer" data-service="social">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-primary rounded-full flex items-center justify-center text-white text-3xl">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-6 text-white">Konten Sosmed</h3>
                    <ul class="text-left mb-8 space-y-2">
                        <li class="py-2 opacity-80 border-b border-white/10">Story Templates</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Carousel Post</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Highlight Covers</li>
                        <li class="py-2 opacity-80">Profile Aesthetics</li>
                    </ul>
                    <p class="text-xl font-bold bg-gradient-primary bg-clip-text text-transparent">
                        Mulai dari Rp8.000
                    </p>
                </div>

                <div class="service-card bg-white/5 backdrop-blur-glass border border-white/10 rounded-3xl p-10 text-center transition-all duration-300 hover:-translate-y-2 hover:bg-white/10 hover:shadow-xl hover:shadow-primary/20 cursor-pointer" data-service="presentation">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-primary rounded-full flex items-center justify-center text-white text-3xl">
                        <i class="fas fa-presentation-screen"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-6 text-white">Presentasi</h3>
                    <ul class="text-left mb-8 space-y-2">
                        <li class="py-2 opacity-80 border-b border-white/10">PowerPoint Design</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Google Slides</li>
                        <li class="py-2 opacity-80 border-b border-white/10">Infografis</li>
                        <li class="py-2 opacity-80">Laporan Visual</li>
                    </ul>
                    <p class="text-xl font-bold bg-gradient-primary bg-clip-text text-transparent">
                        Mulai dari Rp12.000
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <!-- Portfolio Section - FIXED VERSION -->
    <section id="portfolio" class="py-20">
        <div class="container mx-auto px-5">
            <h2 class="text-5xl font-bold text-center mb-4 bg-gradient-to-r from-white to-primary bg-clip-text text-transparent">
                Portfolio Kami
            </h2>
            <p class="text-xl text-center mb-12 opacity-80 max-w-2xl mx-auto">
                Beberapa karya terbaik yang telah kami buat
            </p>
            
            <!-- Filter Buttons -->
            <div class="portfolio-filter flex justify-center gap-4 mb-12 flex-wrap">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="video">Video</button>
                <button class="filter-btn" data-filter="design">Desain</button>
                <button class="filter-btn" data-filter="social">Sosmed</button>
            </div>

            <!-- Portfolio Grid -->
            <div class="portfolio-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8" id="portfolioGrid">
                <!-- Items will be populated by JavaScript -->
            </div>
        </div>
    </section>


    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white/5">
        <div class="container mx-auto px-5">
            <h2 class="text-5xl font-bold text-center mb-4 bg-gradient-to-r from-white to-primary bg-clip-text text-transparent">
                Harga Terjangkau
            </h2>
            <p class="text-xl text-center mb-12 opacity-80 max-w-2xl mx-auto">
                Pilih paket yang sesuai dengan kebutuhan Anda
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
                <div class="pricing-card bg-white/5 backdrop-blur-glass border-2 border-white/10 rounded-3xl p-10 text-center relative transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-primary/30">
                    <div class="pricing-header">
                        <h3 class="text-3xl font-semibold mb-4">Basic</h3>
                        <div class="price mb-8">
                            <span class="text-xl align-top">Rp</span>
                            <span class="text-6xl font-bold bg-gradient-primary bg-clip-text text-transparent">25.000</span>
                            <span class="text-lg opacity-70">/project</span>
                        </div>
                    </div>
                    <ul class="pricing-features mb-8 space-y-3 text-left">
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            1 Revisi
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Pengerjaan 1-2 hari
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            File HD Quality
                        </li>
                        <li class="flex items-center gap-3 py-3">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Konsultasi via WA
                        </li>
                    </ul>
                    <button class="w-full bg-gradient-primary text-white py-4 px-8 rounded-full text-lg font-semibold cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/40" onclick="orderWhatsApp('Basic')">
                        Pilih Paket
                    </button>
                </div>

                <div class="pricing-card featured bg-white/5 backdrop-blur-glass border-2 border-primary rounded-3xl p-10 text-center relative transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-primary/30 scale-105">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-gradient-primary text-white py-1 px-5 rounded-2xl text-sm font-bold">
                        Popular
                    </div>
                    <div class="pricing-header">
                        <h3 class="text-3xl font-semibold mb-4">Standard</h3>
                        <div class="price mb-8">
                            <span class="text-xl align-top">Rp</span>
                            <span class="text-6xl font-bold bg-gradient-primary bg-clip-text text-transparent">45.000</span>
                            <span class="text-lg opacity-70">/project</span>
                        </div>
                    </div>
                    <ul class="pricing-features mb-8 space-y-3 text-left">
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            3 Revisi
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Pengerjaan 6-12 jam
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Full HD + Source File
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Free Konsultasi
                        </li>
                        <li class="flex items-center gap-3 py-3">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Rush Order Available
                        </li>
                    </ul>
                    <button class="w-full bg-gradient-primary text-white py-4 px-8 rounded-full text-lg font-semibold cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/40" onclick="orderWhatsApp('Standard')">
                        Pilih Paket
                    </button>
                </div>

                <div class="pricing-card bg-white/5 backdrop-blur-glass border-2 border-white/10 rounded-3xl p-10 text-center relative transition-all duration-300 hover:-translate-y-2 hover:shadow-xl hover:shadow-primary/30">
                    <div class="pricing-header">
                        <h3 class="text-3xl font-semibold mb-4">Premium</h3>
                        <div class="price mb-8">
                            <span class="text-xl align-top">Rp</span>
                            <span class="text-6xl font-bold bg-gradient-primary bg-clip-text text-transparent">75.000</span>
                            <span class="text-lg opacity-70">/project</span>
                        </div>
                    </div>
                    <ul class="pricing-features mb-8 space-y-3 text-left">
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Unlimited Revisi
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Pengerjaan 3-6 jam
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            4K Quality + All Formats
                        </li>
                        <li class="flex items-center gap-3 py-3 border-b border-white/10">
                            <i class="fas fa-check text-primary text-lg"></i>
                            24/7 Support
                        </li>
                        <li class="flex items-center gap-3 py-3">
                            <i class="fas fa-check text-primary text-lg"></i>
                            Bonus Social Media Kit
                        </li>
                    </ul>
                    <button class="w-full bg-gradient-primary text-white py-4 px-8 rounded-full text-lg font-semibold cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/40" onclick="orderWhatsApp('Premium')">
                        Pilih Paket
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white/5">
        <div class="container mx-auto px-5">
            <h2 class="text-5xl font-bold text-center mb-4 bg-gradient-to-r from-white to-primary bg-clip-text text-transparent">
                Hubungi Kami
            </h2>
            <p class="text-xl text-center mb-12 opacity-80 max-w-2xl mx-auto">
                Siap untuk memulai project Anda? Hubungi kami sekarang!
            </p>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 mt-12">
                <div class="flex flex-col gap-6">
                    <div class="bg-white/5 backdrop-blur-glass border border-white/10 rounded-2xl p-6 text-center transition-all duration-300 hover:bg-white/10 hover:-translate-y-1">
                        <i class="fas fa-clock text-3xl bg-gradient-primary bg-clip-text text-transparent mb-3"></i>
                        <h4 class="text-lg font-semibold mb-2">Jam Operasional</h4>
                        <p class="opacity-80 text-sm">Senin - Minggu<br>08:00 - 22:00 WIB</p>
                    </div>

                    <div class="bg-white/5 backdrop-blur-glass border border-white/10 rounded-2xl p-6 text-center transition-all duration-300 hover:bg-white/10 hover:-translate-y-1">
                        <i class="fas fa-shipping-fast text-3xl bg-gradient-primary bg-clip-text text-transparent mb-3"></i>
                        <h4 class="text-lg font-semibold mb-2">Pengerjaan Cepat</h4>
                        <p class="opacity-80 text-sm">6-24 jam*<br>*tergantung kompleksitas</p>
                    </div>

                    <div class="bg-white/5 backdrop-blur-glass border border-white/10 rounded-2xl p-6 text-center transition-all duration-300 hover:bg-white/10 hover:-translate-y-1">
                        <i class="fas fa-redo text-3xl bg-gradient-primary bg-clip-text text-transparent mb-3"></i>
                        <h4 class="text-lg font-semibold mb-2">Revisi Gratis</h4>
                        <p class="opacity-80 text-sm">1-3x revisi gratis<br>sesuai paket yang dipilih</p>
                    </div>

                    <div class="bg-white/5 backdrop-blur-glass border border-white/10 rounded-2xl p-6 text-center transition-all duration-300 hover:bg-white/10 hover:-translate-y-1">
                        <i class="fas fa-shield-alt text-3xl bg-gradient-primary bg-clip-text text-transparent mb-3"></i>
                        <h4 class="text-lg font-semibold mb-2">Garansi Puas</h4>
                        <p class="opacity-80 text-sm">100% uang kembali<br>jika tidak puas</p>
                    </div>
                </div>

                <div class="bg-white/5 backdrop-blur-glass border border-white/10 rounded-3xl p-10 text-center">
                    <h3 class="text-3xl font-bold mb-6 bg-gradient-to-r from-white to-primary bg-clip-text text-transparent">
                        Mulai Project Anda
                    </h3>
                    <p class="text-lg opacity-80 mb-8">
                        Daftar atau login untuk mengakses form pemesanan lengkap dengan sistem tracking order real-time
                    </p>
                    
                    <?php if ($user): ?>
                        <a href="order.php" class="inline-block w-full bg-gradient-to-r from-primary to-secondary text-white py-4 px-8 rounded-full text-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/40 mb-4">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Buat Pesanan
                        </a>
                        <a href="my-orders.php" class="inline-block w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 px-8 rounded-full text-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-green-500/40">
                            <i class="fas fa-list mr-2"></i>
                            Lihat Pesanan Saya
                        </a>
                    <?php else: ?>
                        <a href="pages/auth/register.php" class="inline-block w-full bg-gradient-to-r from-primary to-secondary text-white py-4 px-8 rounded-full text-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/40 mb-4">
                            <i class="fas fa-user-plus mr-2"></i>
                            Daftar Sekarang
                        </a>
                        <a href="pages/auth/login.php" class="inline-block w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 px-8 rounded-full text-lg font-semibold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-green-500/40">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="py-20 bg-white/5">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        What Our <span class="bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">Clients Say</span>
                    </h2>
                    <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                        Testimoni dari klien yang telah mempercayai layanan kami
                    </p>
                </div>
                
                <div class="grid gap-8 md:grid-cols-1 lg:grid-cols-3">
                    <?php
                    include "config/database.php";
                    
                    // Cek apakah kolom user_id sudah ada di tabel feedback
                    $check_column = $conn->query("SHOW COLUMNS FROM feedback LIKE 'user_id'");
                    $has_user_id = $check_column->num_rows > 0;
                    
                    if ($has_user_id) {
                        // Jika kolom user_id sudah ada, gunakan query dengan JOIN
                        $result = $conn->query("
                            SELECT f.*, u.username, u.profile_picture, u.full_name 
                            FROM feedback f 
                            LEFT JOIN users u ON f.user_id = u.id 
                            ORDER BY f.created_at DESC 
                            LIMIT 3
                        ");
                    } else {
                        // Jika kolom user_id belum ada, gunakan query sederhana
                        $result = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 3");
                    }
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Tentukan nama yang akan ditampilkan
                            if ($has_user_id && isset($row['full_name']) && $row['full_name']) {
                                $display_name = $row['full_name'];
                            } else {
                                $display_name = $row['nama'];
                            }
                            
                            echo '
                            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2">
                                <div class="flex items-center mb-6">
                                    <div class="w-12 h-12 rounded-full mr-4 overflow-hidden">';
                            
                            // Tampilkan foto profil jika ada, atau avatar default
                            if ($has_user_id && isset($row['profile_picture']) && $row['profile_picture']) {
                                // Normalisasi path menjadi web path yang aman
                                $rawPath = $row['profile_picture'];
                                $cleanWebPath = str_replace(['../../', './', '\\'], ['', '', '/'], $rawPath);
                                // Jika path dimulai dengan 'uploads/', asumsikan relatif terhadap root project
                                // Bangun path filesystem untuk pengecekan aman
                                $fsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanWebPath);
                                if (file_exists($fsPath)) {
                                    echo '<img src="'.htmlspecialchars($cleanWebPath).'" alt="Profile" class="w-full h-full object-cover">';
                                } else {
                                    // Jika cek gagal, tetap coba render web path dan biarkan fallback onerror di CSS/UI
                                    echo '<img src="'.htmlspecialchars($cleanWebPath).'" alt="Profile" class="w-full h-full object-cover" onerror="this.remove();">';
                                }
                            } else {
                                $initial = strtoupper(substr($display_name, 0, 1));
                                echo '<div class="w-full h-full bg-gradient-to-r from-amber-500 to-yellow-600 flex items-center justify-center text-white font-bold text-lg">'.$initial.'</div>';
                            }
                            
                            echo '    </div>
                                    <div>
                                        <h4 class="font-semibold text-white text-lg">'.$display_name.'</h4>';
                            
                            // Tampilkan username jika user terdaftar dan kolom ada
                            if ($has_user_id && isset($row['username']) && $row['username']) {
                                echo '<p class="text-gray-400 text-sm">@'.$row['username'].'</p>';
                            }
                            
                            echo '        <div class="flex text-amber-400 mt-1">';
                                            for ($i=0; $i<$row['rating']; $i++) {
                                                echo '<i class="fas fa-star text-sm"></i>';
                                            }
                            echo '      </div>
                                    </div>
                                </div>
                                <p class="text-gray-300 leading-relaxed italic">
                                    "'.$row['pesan'].'"
                                </p>
                            </div>';
                        }
                    } else {
                        echo "<div class='col-span-full text-center text-gray-400 py-16'>
                                <div class='max-w-md mx-auto'>
                                    <i class='fas fa-comments text-5xl mb-6 opacity-30'></i>
                                    <h3 class='text-xl font-semibold text-gray-300 mb-2'>Belum Ada Testimoni</h3>
                                    <p class='text-gray-400 mb-6'>Jadilah yang pertama memberikan testimoni untuk layanan kami</p>
                                    <a href='#feedback' class='inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-yellow-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-700 transition-all duration-300 hover:-translate-y-1'>
                                        <i class='fas fa-star'></i>
                                        Tulis Testimoni
                                    </a>
                                </div>
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Feedback Form Section -->
        <section id="feedback" class="py-20 bg-black/20">
            <div class="max-w-2xl mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-white mb-4">
                        Share Your <span class="bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent">Experience</span>
                    </h2>
                    <p class="text-xl text-gray-300">
                        Bagikan pengalaman Anda menggunakan layanan kami
                    </p>
                </div>
                
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id']): ?>
                        <!-- User Profile Preview -->
                        <div class="flex items-center mb-6 p-4 bg-white/5 rounded-xl border border-white/10">
                            <?php 
                            // Cek foto profil dari database untuk user yang login
                            $profile_picture = null;
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $profile_data = $result->fetch_assoc();
                                    $profile_picture = $profile_data['profile_picture'];
                                }
                                $stmt->close();
                            }
                            
                            $testimoniImageSrc = '';
                            if ($profile_picture) {
                                // Clean up the path - remove any ../ prefixes and ensure proper web path
                                $cleanPath = str_replace('../../', '', $profile_picture);
                                $testimoniImageSrc = $cleanPath;
                            }
                            ?>
                            <?php if ($testimoniImageSrc): ?>
                                <img src="<?php echo htmlspecialchars($testimoniImageSrc); ?>" 
                                     alt="Profile Picture" 
                                     class="w-12 h-12 rounded-full object-cover mr-4"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     onload="this.style.display='block'; this.nextElementSibling.style.display='none';">
                            <?php endif; ?>
                            <div class="w-12 h-12 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-lg mr-4" style="<?php echo $testimoniImageSrc ? 'display: none;' : ''; ?>">
                                <?php 
                                $display_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
                                echo strtoupper(substr($display_name, 0, 1)); 
                                ?>
                            </div>
                            <div>
                                <h4 class="text-white font-semibold">
                                    <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User'; ?>
                                </h4>
                                <p class="text-gray-400 text-sm">@<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'user'; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="simpan_feedback.php" method="POST" class="space-y-6">
                        <?php if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']): ?>
                        <div class="form-group">
                            <label for="nama" class="block text-white font-semibold mb-3 flex items-center gap-2">
                                <i class="fas fa-user text-gray-400"></i>
                                Nama Anda
                            </label>
                            <input type="text" name="nama" id="nama" placeholder="Masukkan nama lengkap" 
                                   class="w-full p-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-green-500 focus:bg-white/15 transition-all duration-300 focus:ring-2 focus:ring-green-500/20" required>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="pesan" class="block text-white font-semibold mb-3 flex items-center gap-2">
                                <i class="fas fa-comment-dots text-gray-400"></i>
                                Testimoni Anda
                            </label>
                            <textarea name="pesan" id="pesan" rows="5" placeholder="Ceritakan pengalaman Anda menggunakan layanan kami..."
                                      class="w-full p-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-green-500 focus:bg-white/15 transition-all duration-300 resize-none focus:ring-2 focus:ring-green-500/20" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="rating" class="block text-white font-semibold mb-3 flex items-center gap-2">
                                <i class="fas fa-star text-gray-400"></i>
                                Rating Layanan
                            </label>
                            <select name="rating" id="rating" required
                                    class="w-full p-4 rounded-xl bg-white/10 border border-white/20 text-white focus:outline-none focus:border-green-500 focus:bg-white/15 transition-all duration-300 focus:ring-2 focus:ring-green-500/20">
                                <option value="" class="bg-gray-800">Pilih rating Anda</option>
                                <option value="5" class="bg-gray-800">⭐⭐⭐⭐⭐ Sangat Memuaskan</option>
                                <option value="4" class="bg-gray-800">⭐⭐⭐⭐ Memuaskan</option>
                                <option value="3" class="bg-gray-800">⭐⭐⭐ Cukup Baik</option>
                                <option value="2" class="bg-gray-800">⭐⭐ Kurang Baik</option>
                                <option value="1" class="bg-gray-800">⭐ Perlu Perbaikan</option>
                            </select>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-yellow-600 text-white py-4 px-8 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-700 transition-all duration-300 hover:-translate-y-1 shadow-lg hover:shadow-xl flex items-center justify-center gap-3 group">
                                <i class="fas fa-star group-hover:rotate-12 transition-transform duration-300"></i>
                                Kirim Testimoni
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>


    <!-- Footer -->
    <footer class="bg-gradient-to-b from-gray-900 to-black py-16 border-t border-white/10 lg:ml-64 transition-all duration-300" id="footer">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Brand Section -->
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-palette text-white text-xl"></i>
                        </div>
                        <div class="text-3xl font-bold text-white">Desainin</div>
                    </div>
                    <p class="text-gray-300 mb-8 leading-relaxed">
                        Platform kreatif terpercaya untuk semua kebutuhan desain grafis dan video editing Anda. Wujudkan ide kreatif dengan kualitas profesional.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="group w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gradient-to-r hover:from-amber-500 hover:to-yellow-600 transition-all duration-300">
                            <i class="fab fa-instagram text-lg group-hover:scale-110 transition-transform"></i>
                        </a>
                        <a href="#" class="group w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gradient-to-r hover:from-amber-500 hover:to-yellow-600 transition-all duration-300">
                            <i class="fab fa-tiktok text-lg group-hover:scale-110 transition-transform"></i>
                        </a>
                        <a href="#" class="group w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gradient-to-r hover:from-amber-500 hover:to-yellow-600 transition-all duration-300">
                            <i class="fab fa-youtube text-lg group-hover:scale-110 transition-transform"></i>
                        </a>
                        <a href="#" onclick="openWhatsApp()" class="group w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-gradient-to-r hover:from-amber-500 hover:to-yellow-600 transition-all duration-300">
                            <i class="fab fa-whatsapp text-lg group-hover:scale-110 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-tools text-amber-500"></i>
                        Layanan Kami
                    </h4>
                    <ul class="space-y-4">
                        <li><a href="#services" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-video text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Video Editing
                        </a></li>
                        <li><a href="#services" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-palette text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Desain Grafis
                        </a></li>
                        <li><a href="#services" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-mobile-alt text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Konten Social Media
                        </a></li>
                        <li><a href="#services" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-presentation text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Presentasi PPT
                        </a></li>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-link text-amber-500"></i>
                        Quick Links
                    </h4>
                    <ul class="space-y-4">
                        <li><a href="#portfolio" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-folder text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Portfolio
                        </a></li>
                        <li><a href="#pricing" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-tag text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Harga
                        </a></li>
                        <li><a href="#testimonials" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-star text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Testimoni
                        </a></li>
                        <li><a href="#feedback" class="text-gray-300 hover:text-amber-400 transition-colors duration-300 flex items-center gap-2 group">
                            <i class="fas fa-comment text-xs text-gray-500 group-hover:text-amber-400"></i>
                            Feedback
                        </a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-phone text-amber-500"></i>
                        Hubungi Kami
                    </h4>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-lg border border-white/10 cursor-pointer hover:bg-white/10 transition-all duration-300" onclick="openWhatsApp()">
                            <i class="fab fa-whatsapp text-amber-400 text-lg mt-1"></i>
                            <div>
                                <p class="text-white font-medium">WhatsApp</p>
                                <p class="text-gray-300 text-sm">+62 882-9915-4725</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-lg border border-white/10">
                            <i class="fas fa-envelope text-amber-400 text-lg mt-1"></i>
                            <div>
                                <p class="text-white font-medium">Email</p>
                                <p class="text-gray-300 text-sm">info@desainin.com</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-lg border border-white/10">
                            <i class="fas fa-clock text-amber-400 text-lg mt-1"></i>
                            <div>
                                <p class="text-white font-medium">Jam Operasional</p>
                                <p class="text-gray-300 text-sm">08:00 - 22:00 WIB</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="pt-8 border-t border-white/10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-gray-400 text-sm">
                        &copy; 2025 <span class="text-white font-semibold">Desainin</span>. All rights reserved.
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        <div class="text-gray-500">Created by <span class="text-amber-400">Vylan Yoza Sinaga</span></div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- External JavaScript -->
    <script src="assets/js/Desainin.js"></script>
    <script src="assets/js/promo-popup.js"></script>
    
    <!-- Test popup directly -->
    <script>
        console.log('Testing popup creation...');
        setTimeout(() => {
            console.log('Direct popup test');
            if (typeof createPromoPopup === 'function') {
                console.log('createPromoPopup function exists');
                createPromoPopup();
            } else {
                console.error('createPromoPopup function not found');
            }
        }, 2000);
    </script>
    
    <script>
        // User dropdown functionality
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            const icon = document.getElementById('userDropdownIcon');
            const sidebar = document.getElementById('sidebar');
            
            // Don't show dropdown if sidebar is collapsed
            if (sidebar.classList.contains('collapsed')) {
                return;
            }
            
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                dropdown.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const userProfile = event.target.closest('[onclick="toggleUserDropdown()"]');
            
            if (!userProfile && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
                const icon = document.getElementById('userDropdownIcon');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        });
    </script>
</body>
</html>