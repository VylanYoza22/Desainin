<?php
/**
 * Admin Users Management
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/admin_config.php';
require_once '../../config/whatsapp_functions.php';

requireAdmin($conn);

$q = trim($_GET['q'] ?? '');
// sorting
$allowedSort = ['created_at' => 'created_at', 'name' => 'full_name', 'username' => 'username', 'email' => 'email'];
$sortKey = $_GET['sort'] ?? 'created_at';
$sortCol = $allowedSort[$sortKey] ?? 'created_at';
$dir = strtolower($_GET['dir'] ?? 'desc');
$dir = $dir === 'asc' ? 'ASC' : 'DESC';
// pagination
$perPage = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// where builder
$where = [];
$params = [];
$types = '';
if ($q !== '') {
  $where[] = '(username LIKE CONCAT("%", ?, "%") OR full_name LIKE CONCAT("%", ?, "%") OR email LIKE CONCAT("%", ?, "%"))';
  $params[] = $q; $params[] = $q; $params[] = $q; $types .= 'sss';
}

// total count
$countSql = 'SELECT COUNT(*) AS c FROM users' . ($where ? (' WHERE ' . implode(' AND ', $where)) : '');
$stmtC = $conn->prepare($countSql);
if ($params) { $stmtC->bind_param($types, ...$params); }
$stmtC->execute();
$total = (int)($stmtC->get_result()->fetch_assoc()['c'] ?? 0);
$stmtC->close();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// data query
$sql = 'SELECT id, username, full_name, email, phone, created_at FROM users' . ($where ? (' WHERE ' . implode(' AND ', $where)) : '');
$sql .= ' ORDER BY ' . $sortCol . ' ' . $dir . ' LIMIT ? OFFSET ?';
$stmt = $conn->prepare($sql);
// bind dynamic + limit/offset
if ($params) {
  $bindTypes = $types . 'ii';
  $bindParams = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($bindTypes, ...$bindParams);
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
$users = [];
if ($res) { while ($row = $res->fetch_assoc()) { $users[] = $row; } }
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Users</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/Style-Desainin-dark.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
  <style>
    body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); }
    .glass { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); backdrop-filter: blur(10px); }
    /* Animated gradient background */
    .bg-gradient-animated {
      background:
        radial-gradient(1200px circle at 0% 0%, rgba(245, 158, 11, 0.12), transparent 40%),
        radial-gradient(1000px circle at 100% 0%, rgba(59, 130, 246, 0.12), transparent 40%),
        radial-gradient(1200px circle at 100% 100%, rgba(34, 197, 94, 0.12), transparent 45%),
        radial-gradient(900px circle at 0% 100%, rgba(147, 51, 234, 0.12), transparent 45%);
      animation: floaty 14s ease-in-out infinite alternate;
    }
    @keyframes floaty { 0% { transform: translateY(0) } 100% { transform: translateY(-8px) } }
    /* Pill controls */
    .btn-pill { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; border:1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.08); color:#e5e7eb; }
    .btn-pill:hover { background: rgba(255,255,255,.12); }
    .btn-amber { background:#f59e0b; color:#0b0b0b; border-color: rgba(245,158,11,.6); }
    .btn-amber:hover { background:#d97706; color:#0b0b0b; }
    .btn-gray { background: rgba(255,255,255,.08); color:#e5e7eb; }
    .input-pill { background: rgba(17,24,39,.85); color:#e5e7eb; border:1px solid rgba(255,255,255,.18); padding:10px 12px; border-radius:12px; }
    /* User card */
    .user-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 14px; }
    .user-card:hover { border-color: rgba(245,158,11,.45); }
  </style>
</head>
<body class="min-h-screen text-white">
  <div class="fixed inset-0 -z-20 bg-gradient-animated"></div>
  <?php include '../../includes/admin_header.php'; ?>

  <main class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header controls -->
    <div class="glass rounded-2xl p-5 mb-6">
      <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="text-left w-full sm:w-auto">
          <h1 class="text-2xl font-bold">Kelola Pengguna</h1>
          <p class="text-gray-300 text-sm">Cari dan hubungi pengguna Anda</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 items-center">
          <a href="index.php" class="btn-pill"><i class="fas fa-arrow-left"></i>Kembali ke Dashboard</a>
          <form method="GET" class="flex flex-col sm:flex-row gap-3 items-center">
            <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Cari username/nama/email" class="input-pill" />
            <select name="sort" class="select-pill">
              <option value="created_at" <?= $sortKey==='created_at'?'selected':''; ?>>Terbaru</option>
              <option value="name" <?= $sortKey==='name'?'selected':''; ?>>Nama</option>
              <option value="username" <?= $sortKey==='username'?'selected':''; ?>>Username</option>
              <option value="email" <?= $sortKey==='email'?'selected':''; ?>>Email</option>
            </select>
            <select name="dir" class="select-pill">
              <option value="desc" <?= strtolower($_GET['dir'] ?? 'desc')==='desc'?'selected':''; ?>>↓</option>
              <option value="asc" <?= strtolower($_GET['dir'] ?? '')==='asc'?'selected':''; ?>>↑</option>
            </select>
            <select name="per_page" class="select-pill">
              <?php foreach ([10,20,50,100] as $pp): ?>
              <option value="<?= $pp ?>" <?= $perPage==$pp?'selected':''; ?>><?= $pp ?>/hal</option>
              <?php endforeach; ?>
            </select>
            <button class="btn-pill btn-amber"><i class="fas fa-search"></i>Filter</button>
            <a href="users.php" class="btn-pill btn-gray"><i class="fas fa-rotate"></i>Reset</a>
          </form>
        </div>
      </div>
    </div>

    <!-- Users list cards -->
    <div class="glass rounded-2xl p-5">
      <?php if (!$users): ?>
        <div class="user-card text-gray-300">Tidak ada data pengguna.</div>
      <?php else: ?>
      <ul class="space-y-3">
        <?php foreach ($users as $u): $praw = (string)($u['phone'] ?? ''); $wa = $praw ? validateWhatsAppNumber($praw) : ''; ?>
          <li class="user-card">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <!-- Left: identity -->
              <div class="flex items-start gap-3 min-w-[200px]">
                <div class="font-mono text-sm text-gray-300">#<?php echo (int)$u['id']; ?></div>
                <div>
                  <div class="text-white font-medium leading-tight"><?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?></div>
                  <div class="text-xs text-gray-400 leading-tight">@<?php echo htmlspecialchars($u['username']); ?></div>
                </div>
              </div>
              <!-- Middle: contact -->
              <div class="flex-1">
                <div class="text-xs text-gray-300 mb-1"><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($u['email']); ?></div>
                <div class="text-xs text-gray-300"><i class="fab fa-whatsapp mr-2 text-green-400"></i><?php echo $praw ?: '-'; ?></div>
                <div class="mt-2 flex gap-2">
                  <?php if ($wa): $msg=urlencode('Halo '.($u['full_name']?:$u['username']).', ada yang bisa kami bantu?'); ?>
                  <a target="_blank" href="https://wa.me/<?php echo $wa; ?>?text=<?php echo $msg; ?>" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs"><i class="fab fa-whatsapp"></i>WA</a>
                  <?php endif; ?>
                  <?php if ($praw): ?>
                  <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$praw); ?>" class="inline-flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs"><i class="fas fa-phone"></i>Call</a>
                  <?php endif; ?>
                  <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs"><i class="fas fa-envelope"></i>Email</a>
                </div>
              </div>
              <!-- Right: created at -->
              <div class="md:text-right min-w-[160px]">
                <div class="text-gray-300 text-sm">Dibuat</div>
                <div class="text-white font-semibold"><?php echo htmlspecialchars($u['created_at']); ?></div>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php if ($total > 0): ?>
    <?php 
      $qs = function($pageNum) use ($q,$sortKey,$dir,$perPage){
        $params = [
          'q'=>$q,
          'sort'=>$sortKey,
          'dir'=>strtolower($dir),
          'per_page'=>$perPage,
          'page'=>$pageNum
        ];
        return 'users.php?'.http_build_query($params);
      };
      $from = ($page-1)*$perPage + 1;
      $to = min($total, $page*$perPage);
    ?>
    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="text-sm text-gray-300">Menampilkan <?php echo $from; ?>–<?php echo $to; ?> dari <?php echo $total; ?> pengguna</div>
      <div class="flex items-center gap-2">
        <a class="btn-pill btn-gray <?php echo $page<=1?'pointer-events-none opacity-50':''; ?>" href="<?php echo $qs(max(1,$page-1)); ?>"><i class="fas fa-chevron-left"></i>Prev</a>
        <span class="text-gray-300 text-sm">Hal <?php echo $page; ?> / <?php echo $totalPages; ?></span>
        <a class="btn-pill btn-gray <?php echo $page>=$totalPages?'pointer-events-none opacity-50':''; ?>" href="<?php echo $qs(min($totalPages,$page+1)); ?>">Next<i class="fas fa-chevron-right"></i></a>
      </div>
    </div>
    <?php endif; ?>
  </main>
</body>
</html>
