    <!-- Footer -->
    <footer class="bg-gray-900/80 backdrop-blur-md border-t border-gray-800 mt-16" role="contentinfo" aria-label="Footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Brand -->
                <div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent mb-4">
                        <i class="fas fa-palette mr-2"></i>Desainin
                    </h3>
                    <p class="text-gray-400 text-sm">Platform kreatif terpercaya untuk semua kebutuhan desain dan video editing Anda.</p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo isset($rootPath) ? $rootPath : '../'; ?>index.php" class="text-gray-400 hover:text-amber-400 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/30 rounded">Beranda</a></li>
                        <li><a href="<?php echo isset($rootPath) ? $rootPath : '../'; ?>order.php" class="text-gray-400 hover:text-amber-400 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/30 rounded">Buat Pesanan</a></li>
                        <li><a href="<?php echo isset($rootPath) ? $rootPath : '../'; ?>my-orders.php" class="text-gray-400 hover:text-amber-400 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/30 rounded">Pesanan Saya</a></li>
                        <li><a href="<?php echo isset($rootPath) ? $rootPath : '../'; ?>pages/user/profile.php" class="text-gray-400 hover:text-amber-400 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400/30 rounded">Profil</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="font-semibold text-white mb-4">Hubungi Kami</h4>
                    <div class="space-y-2 text-sm text-gray-400">
                        <p><i class="fab fa-whatsapp mr-2 text-green-400"></i>+62 812-3456-7890</p>
                        <p><i class="fas fa-envelope mr-2 text-blue-400"></i>hello@desainin.com</p>
                        <p><i class="fas fa-clock mr-2 text-amber-400"></i>24/7 Support</p>
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-700 my-6">
            
            <!-- Copyright -->
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> Desainin. All rights reserved.</p>
                <p>Made with <i class="fas fa-heart text-red-500"></i> for creators</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo isset($jsPath) ? $jsPath : '../assets/js/Desainin.js'; ?>"></script>
    
    <!-- Additional scripts -->
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
