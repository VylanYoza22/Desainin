<?php
/**
 * Admin Dashboard
 * Overview stats and recent activities
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/admin_config.php';
require_once '../../config/status_functions.php';
require_once '../../config/whatsapp_functions.php';

// Access control (bypass may be enabled for dev)
requireAdmin($conn);

// Fetch current logged-in user to display in navbar
$currentUser = null;
if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $stmtCU = $conn->prepare("SELECT username, full_name, email, role FROM users WHERE id = ?");
  $stmtCU->bind_param("i", $uid);
  $stmtCU->execute();
  $resCU = $stmtCU->get_result();
  if ($resCU && $resCU->num_rows > 0) {
    $currentUser = $resCU->fetch_assoc();
  }
  $stmtCU->close();
}

// Fetch order counts by status
$statusDefs = getStatusDefinitions();
$statusCounts = [];
foreach (array_keys($statusDefs) as $key) { $statusCounts[$key] = 0; }
$res = $conn->query("SELECT status, COUNT(*) as c FROM orders GROUP BY status");
if ($res) {
  while ($row = $res->fetch_assoc()) { $statusCounts[$row['status']] = (int)$row['c']; }
}
$totalOrders = array_sum($statusCounts);

// Total users
$totalUsers = 0;
$uRes = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($uRes) { $totalUsers = (int)$uRes->fetch_assoc()['c']; }

// Recent orders
$recentOrders = [];
$rRes = $conn->query("SELECT o.id, o.title, o.status, o.progress_percentage, o.created_at, u.full_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8");
if ($rRes) { while ($r = $rRes->fetch_assoc()) { $recentOrders[] = $r; } }

// Recent users
$recentUsers = [];
$ruRes = $conn->query("SELECT id, username, full_name, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 8");
if ($ruRes) { while ($r = $ruRes->fetch_assoc()) { $recentUsers[] = $r; } }

// Admins list
$admins = [];
$aRes = $conn->query("SELECT id, username, full_name, email, phone, last_login FROM users WHERE role='admin' ORDER BY full_name, username");
if ($aRes) { while ($r = $aRes->fetch_assoc()) { $admins[] = $r; } }

// Online users list (logged-in users)
$onlineUsers = [];
$ouRes = $conn->query("SELECT id, username, full_name, email, phone, last_login FROM users WHERE role='user' AND is_online = 1 ORDER BY last_login DESC LIMIT 50");
if ($ouRes) { while ($r = $ouRes->fetch_assoc()) { $onlineUsers[] = $r; } }

function statusBadge($status) {
  $info = getStatusInfo($status);
  $map = [
    'amber'=>'bg-amber-500', 'green'=>'bg-green-500', 'blue'=>'bg-blue-500', 'purple'=>'bg-purple-500', 'red'=>'bg-red-500'
  ];
  $cls = $map[$info['color']] ?? 'bg-gray-500';
  return '<span class="px-2 py-0.5 rounded text-xs text-white '.$cls.'">'.htmlspecialchars(ucfirst(str_replace('_',' ',$status))).'</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/Style-Desainin-dark.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
  <style>
    body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
    .glass { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); backdrop-filter: blur(10px); }
    .stat-card { position:relative; overflow:hidden; transition: transform .25s ease, box-shadow .25s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .stat-icon { width:42px; height:42px; display:flex; align-items:center; justify-content:center; border-radius:12px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.2); }
    .list-item { transition: background .2s ease, border-color .2s ease; border-left-width: 4px; border-left-color: transparent; }
    .list-item:hover { background: rgba(255,255,255,.06); border-left-color: #f59e0b; }
    .section-title { display:flex; align-items:center; gap:.5rem; font-weight:600; }
    /* Animated background behind content */
    .bg-gradient-animated {
      background:
        radial-gradient(1200px circle at 0% 0%, rgba(245, 158, 11, 0.12), transparent 40%),
        radial-gradient(1000px circle at 100% 0%, rgba(59, 130, 246, 0.12), transparent 40%),
        radial-gradient(1200px circle at 100% 100%, rgba(34, 197, 94, 0.12), transparent 45%),
        radial-gradient(900px circle at 0% 100%, rgba(147, 51, 234, 0.12), transparent 45%);
      animation: floaty 14s ease-in-out infinite alternate;
    }
    @keyframes floaty {
      0% { transform: translateY(0px) translateX(0px) scale(1); opacity: .9; }
      100% { transform: translateY(-10px) translateX(6px) scale(1.02); opacity: 1; }
    }
  </style>
</head>
<body class="min-h-screen text-white">
  <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
  <?php include '../../includes/admin_header.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-6">
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <a href="orders.php" class="stat-card glass rounded-xl p-4 block group">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-300">Total Orders</div>
            <div class="text-3xl font-bold mt-1"><?php echo (int)$totalOrders; ?></div>
          </div>
          <div class="stat-icon group-hover:bg-amber-400/20 group-hover:border-amber-400/60"><i class="fas fa-cart-shopping text-amber-400"></i></div>
        </div>
      </a>
      <a href="users.php" class="stat-card glass rounded-xl p-4 block group">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-300">Users</div>
            <div class="text-3xl font-bold mt-1"><?php echo (int)$totalUsers; ?></div>
          </div>
          <div class="stat-icon group-hover:bg-sky-400/20 group-hover:border-sky-400/60"><i class="fas fa-users text-sky-400"></i></div>
        </div>
      </a>
      <a href="orders.php?status=in_progress" class="stat-card glass rounded-xl p-4 block group">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-300">In Progress</div>
            <div class="text-3xl font-bold mt-1"><?php echo (int)($statusCounts['in_progress'] ?? 0); ?></div>
          </div>
          <div class="stat-icon group-hover:bg-amber-400/20 group-hover:border-amber-400/60"><i class="fas fa-spinner text-amber-400"></i></div>
        </div>
      </a>
      <a href="orders.php?status=completed" class="stat-card glass rounded-xl p-4 block group">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-300">Completed</div>
            <div class="text-3xl font-bold mt-1"><?php echo (int)($statusCounts['completed'] ?? 0); ?></div>
          </div>
          <div class="stat-icon group-hover:bg-emerald-400/20 group-hover:border-emerald-400/60"><i class="fas fa-circle-check text-emerald-400"></i></div>
        </div>
      </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Orders -->
      <section class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold"><i class="fas fa-clipboard-list text-amber-400 mr-2"></i>Pesanan Terbaru</h2>
          <a href="orders.php" class="text-sm text-gray-300 hover:text-white">Lihat semua</a>
        </div>
        <?php if (!$recentOrders): ?>
          <div class="text-gray-400 text-sm">Belum ada pesanan.</div>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($recentOrders as $o): ?>
              <li class="flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <div>
                  <div class="font-medium">#<?php echo (int)$o['id']; ?> - <?php echo htmlspecialchars($o['title']); ?></div>
                  <div class="text-xs text-gray-400">oleh <?php echo htmlspecialchars($o['full_name']); ?> • <?php echo htmlspecialchars($o['created_at']); ?></div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="w-28">
                    <div class="w-full bg-gray-700 rounded-full h-2">
                      <div class="h-2 rounded-full bg-amber-500" style="width: <?php echo (int)$o['progress_percentage']; ?>%"></div>
                    </div>
                  </div>
                  <?php echo statusBadge($o['status']); ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <!-- Recent Users -->
      <section class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold"><i class="fas fa-users text-amber-400 mr-2"></i>User Terbaru</h2>
          <a href="users.php" class="text-sm text-gray-300 hover:text-white">Lihat semua</a>
        </div>
        <?php if (!$recentUsers): ?>
          <div class="text-gray-400 text-sm">Belum ada user.</div>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($recentUsers as $u): $praw = (string)($u['phone'] ?? ''); $wa = $praw ? validateWhatsAppNumber($praw) : ''; ?>
              <li class="flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <div>
                  <div class="font-medium"><?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?></div>
                  <div class="text-xs text-gray-400"><?php echo htmlspecialchars($u['email']); ?> • <?php echo $praw ?: '-'; ?></div>
                </div>
                <div class="flex items-center gap-2">
                  <?php if ($wa): $msg=urlencode('Halo '.($u['full_name']?:$u['username']).', ada yang bisa kami bantu?'); ?>
                  <a target="_blank" href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $msg; ?>" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs"><i class="fab fa-whatsapp"></i>WA</a>
                  <?php endif; ?>
                  <?php if ($praw): ?>
                  <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$praw); ?>" class="inline-flex items-center gap-1 bg-gray-700 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-phone"></i>Call</a>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>

    <!-- Admins and Online Users -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Admins List -->
      <section class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold"><i class="fas fa-user-shield text-amber-400 mr-2"></i>Admins</h2>
          <span class="text-sm text-gray-300"><?php echo count($admins); ?> admin</span>
        </div>
        <?php if (!$admins): ?>
          <div class="text-gray-400 text-sm">Belum ada admin.</div>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($admins as $ad): $praw = (string)($ad['phone'] ?? ''); $wa = $praw ? validateWhatsAppNumber($praw) : ''; ?>
              <li class="flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <div>
                  <div class="font-medium"><?php echo htmlspecialchars($ad['full_name'] ?: $ad['username']); ?></div>
                  <div class="text-xs text-gray-400">@<?php echo htmlspecialchars($ad['username']); ?> • <?php echo htmlspecialchars($ad['email']); ?></div>
                  <div class="text-xs text-gray-500 mt-0.5">Terakhir login: <?php echo htmlspecialchars($ad['last_login'] ?: '-'); ?></div>
                </div>
                <div class="flex items-center gap-2">
                  <?php if ($wa): $msg=urlencode('Halo Admin '.($ad['full_name']?:$ad['username'])); ?>
                  <a target="_blank" href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $msg; ?>" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs"><i class="fab fa-whatsapp"></i>WA</a>
                  <?php endif; ?>
                  <?php if ($praw): ?>
                  <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$praw); ?>" class="inline-flex items-center gap-1 bg-gray-700 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-phone"></i>Call</a>
                  <?php endif; ?>
                  <a href="mailto:<?php echo htmlspecialchars($ad['email']); ?>" class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs"><i class="fas fa-envelope"></i>Email</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <!-- Online Users List -->
      <section class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold"><i class="fas fa-user-clock text-amber-400 mr-2"></i>User Online</h2>
          <span class="text-sm text-gray-300"><?php echo count($onlineUsers); ?> online</span>
        </div>
        <?php if (!$onlineUsers): ?>
          <div class="text-gray-400 text-sm">Belum ada user yang online.</div>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($onlineUsers as $ou): $praw = (string)($ou['phone'] ?? ''); $wa = $praw ? validateWhatsAppNumber($praw) : ''; ?>
              <li class="flex items-center justify-between bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                <div>
                  <div class="font-medium"><?php echo htmlspecialchars($ou['full_name'] ?: $ou['username']); ?></div>
                  <div class="text-xs text-gray-400">@<?php echo htmlspecialchars($ou['username']); ?> • <?php echo htmlspecialchars($ou['email']); ?></div>
                  <div class="text-xs text-gray-500 mt-0.5">Last login: <?php echo htmlspecialchars($ou['last_login'] ?: '-'); ?></div>
                </div>
                <div class="flex items-center gap-2">
                  <?php if ($wa): $msg=urlencode('Halo '.($ou['full_name']?:$ou['username']).', ada yang bisa kami bantu?'); ?>
                  <a target="_blank" href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $msg; ?>" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs"><i class="fab fa-whatsapp"></i>WA</a>
                  <?php endif; ?>
                  <?php if ($praw): ?>
                  <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$praw); ?>" class="inline-flex items-center gap-1 bg-gray-700 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-phone"></i>Call</a>
                  <?php endif; ?>
                  <a href="mailto:<?php echo htmlspecialchars($ou['email']); ?>" class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs"><i class="fas fa-envelope"></i>Email</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>
  </main>
</body>
</html>
