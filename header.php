<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Apotech' : 'Apotech' ?></title>
  <link rel="dns-prefetch" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <?= $extraHead ?? '' ?>
</head>
<body>

<div class="app-layout">
  <!-- Fixed Left Sidebar -->
  <aside class="sidebar">
    <a href="dashboard.php" class="brand">
      <div class="brand-icon"><i class="bi bi-capsule-pill"></i></div>
      <div class="brand-title">Apo<span>tech</span></div>
    </a>

    <ul class="sidebar-menu">
      <li class="menu-item">
        <a href="dashboard.php" class="menu-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="catalog.php" class="menu-link <?= ($activePage ?? '') === 'catalog' ? 'active' : '' ?>">
          <i class="bi bi-grid-3x3-gap"></i>
          <span>Katalog Obat</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="kelola-stok.php" class="menu-link <?= ($activePage ?? '') === 'kelola' ? 'active' : '' ?>">
          <i class="bi bi-box-seam"></i>
          <span>Kelola Stok</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <div class="sidebar-footer-title"><i class="bi bi-shield-check"></i> Single User Mode</div>
      <div class="sidebar-footer-desc">Sistem Manajemen Apotek</div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <main class="main-wrapper">