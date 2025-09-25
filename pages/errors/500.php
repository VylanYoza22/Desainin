<?php
$pageTitle = '500 - Server Error';
$pageDescription = 'Terjadi kesalahan pada server';
$cssPath = '../../assets/css/Style-Desainin-dark.css';
$rootPath = '../../';
include '../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md mx-auto">
        <div class="mb-8">
            <i class="fas fa-server text-8xl text-red-400 mb-4"></i>
            <h1 class="text-6xl font-bold text-white mb-4">500</h1>
            <h2 class="text-2xl font-semibold text-gray-300 mb-4">Server Error</h2>
            <p class="text-gray-400 mb-8">Maaf, terjadi kesalahan pada server. Tim kami sedang memperbaiki masalah ini. Silakan coba lagi nanti.</p>
        </div>
        
        <div class="space-y-4">
            <a href="<?php echo $rootPath; ?>index.php" 
               class="inline-block bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 hover:from-amber-500 hover:via-yellow-600 hover:to-amber-700 text-black font-bold py-3 px-8 rounded-full transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/30">
                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
            </a>
            
            <p class="text-sm text-gray-500 mt-4">
                Jika masalah berlanjut, hubungi support kami di 
                <a href="mailto:support@desainin.com" class="text-amber-400 hover:text-amber-300">support@desainin.com</a>
            </p>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
