<?php
$pageTitle = '404 - Halaman Tidak Ditemukan';
$pageDescription = 'Halaman yang Anda cari tidak ditemukan';
$cssPath = '../../assets/css/Style-Desainin-dark.css';
$rootPath = '../../';
include '../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md mx-auto">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-8xl text-amber-400 mb-4"></i>
            <h1 class="text-6xl font-bold text-white mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-300 mb-4">Halaman Tidak Ditemukan</h2>
            <p class="text-gray-400 mb-8">Maaf, halaman yang Anda cari tidak dapat ditemukan. Mungkin halaman telah dipindahkan atau dihapus.</p>
        </div>
        
        <div class="space-y-4">
            <a href="<?php echo $rootPath; ?>index.php" 
               class="inline-block bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-600 hover:to-amber-700 text-black font-bold py-3 px-8 rounded-full transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/30">
                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
            </a>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo $rootPath; ?>my-orders.php" class="text-gray-300 hover:text-amber-400 transition-colors">
                    <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                </a>
                <a href="<?php echo $rootPath; ?>order.php" class="text-gray-300 hover:text-amber-400 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Buat Pesanan
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
