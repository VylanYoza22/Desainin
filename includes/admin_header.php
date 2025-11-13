<?php
/**
 * Admin Header (Reusable)
 * Usage: include '../../includes/admin_header.php';
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$admin_current = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-gray-900/70 border-b border-gray-800 backdrop-blur-md sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="flex items-center gap-3 min-w-0">
      <a href="index.php" class="text-lg font-bold bg-gradient-to-r from-amber-400 to-yellow-500 bg-clip-text text-transparent whitespace-nowrap">
        <i class="fas fa-gauge-high mr-2"></i>Admin
      </a>
    </div>
    <!-- Desktop links -->
    <div class="hidden sm:flex items-center gap-2 text-sm">
      <a href="index.php" class="px-3 py-1.5 rounded <?php echo $admin_current==='index.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?>">Dashboard</a>
      <a href="orders.php" class="px-3 py-1.5 rounded <?php echo $admin_current==='orders.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?>">Orders</a>
      <a href="users.php" class="px-3 py-1.5 rounded <?php echo $admin_current==='users.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?>">Users</a>
      <a href="../auth/logout.php" class="ml-2 px-3 py-1.5 bg-red-600 hover:bg-red-700 rounded text-white inline-flex items-center gap-2"><i class="fas fa-sign-out-alt"></i><span class="hidden md:inline">Logout</span></a>
    </div>
    <!-- Mobile menu button -->
    <button id="adminMenuBtn" class="sm:hidden inline-flex items-center justify-center w-9 h-9 rounded-md border border-white/10 bg-white/5 hover:bg-white/10 text-white">
      <i class="fas fa-bars text-base"></i>
      <span class="sr-only">Toggle menu</span>
    </button>
  </div>
  <!-- Mobile dropdown -->
  <div id="adminMenu" class="sm:hidden hidden border-t border-gray-800 bg-black/80 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-4 py-2 grid gap-1 text-sm">
      <a href="index.php" class="px-3 py-2 rounded <?php echo $admin_current==='index.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?> flex items-center justify-between">Dashboard <?php if($admin_current==='index.php'){ echo '<i class=\'fas fa-check text-amber-400\'></i>'; } ?></a>
      <a href="orders.php" class="px-3 py-2 rounded <?php echo $admin_current==='orders.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?> flex items-center justify-between">Orders <?php if($admin_current==='orders.php'){ echo '<i class=\'fas fa-check text-amber-400\'></i>'; } ?></a>
      <a href="users.php" class="px-3 py-2 rounded <?php echo $admin_current==='users.php' ? 'bg-white/10 font-semibold' : 'hover:bg-white/10'; ?> flex items-center justify-between">Users <?php if($admin_current==='users.php'){ echo '<i class=\'fas fa-check text-amber-400\'></i>'; } ?></a>
      <a href="../auth/logout.php" class="mt-1 px-3 py-2 bg-red-600 hover:bg-red-700 rounded text-white inline-flex items-center gap-2 justify-center"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
  </div>
</nav>

<script>
  (function(){
    const btn = document.getElementById('adminMenuBtn');
    const menu = document.getElementById('adminMenu');
    if(!btn || !menu) return;
    function closeMenu(){ menu.classList.add('hidden'); }
    function toggleMenu(){ menu.classList.toggle('hidden'); }
    btn.addEventListener('click', function(e){ e.stopPropagation(); toggleMenu(); });
    document.addEventListener('click', function(e){
      if (!menu.classList.contains('hidden')) {
        const nav = btn.closest('nav');
        if (nav && !nav.contains(e.target)) closeMenu();
      }
    });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMenu(); });
  })();
</script>
