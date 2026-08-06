<?php
require_once 'includes/config.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$extraHead  = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>';

// Server-side direct pre-render queries for instant loading (0 ms client delay)
$today_start = date('Y-m-d 00:00:00');
$today_end   = date('Y-m-d 23:59:59');
$seven_ago   = date('Y-m-d 00:00:00', strtotime('-6 days'));

$total_obat  = $conn->query("SELECT COUNT(*) FROM obat WHERE is_active = 1")->fetch_row()[0];
$obat_kritis = $conn->query("SELECT COUNT(*) FROM obat WHERE is_active = 1 AND stok <= stok_minimum")->fetch_row()[0];

$res_today   = $conn->query("
    SELECT COALESCE(SUM(total_harga), 0) as total, COUNT(*) as jumlah
    FROM transaksi
    WHERE tanggal_transaksi >= '$today_start' AND tanggal_transaksi <= '$today_end'
");
$today_data  = $res_today->fetch_assoc();

$res_kritis = $conn->query("
    SELECT obat_id, nama_obat, stok, stok_minimum, satuan
    FROM obat
    WHERE is_active = 1 AND stok <= stok_minimum
    ORDER BY stok ASC
    LIMIT 8
");
$daftar_kritis = $res_kritis->fetch_all(MYSQLI_ASSOC);

$res_recent = $conn->query("
    SELECT
        dt.nama_obat,
        dt.jumlah,
        dt.harga_satuan,
        dt.subtotal,
        t.tanggal_transaksi,
        t.metode_pembayaran
    FROM detail_transaksi dt
    JOIN transaksi t ON dt.transaksi_id = t.transaksi_id
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 10
");
$recent_sales = $res_recent->fetch_all(MYSQLI_ASSOC);

$res_trend = $conn->query("
    SELECT
        DATE(tanggal_transaksi) as tanggal,
        SUM(total_harga) as total
    FROM transaksi
    WHERE tanggal_transaksi >= '$seven_ago'
    GROUP BY DATE(tanggal_transaksi)
    ORDER BY tanggal ASC
");
$trend_raw = $res_trend->fetch_all(MYSQLI_ASSOC);

$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $found = array_filter($trend_raw, fn($r) => $r['tanggal'] === $d);
    $trend[] = [
        'tanggal' => $d,
        'label'   => date('d/m', strtotime($d)),
        'total'   => $found ? (float)array_values($found)[0]['total'] : 0,
    ];
}

$total7_initial = array_sum(array_column($trend, 'total'));

include 'includes/header.php';
?>

<div class="page-header">
  <h1>Dashboard Ringkasan</h1>
  <p>Kondisi operasional dan aktivitas apotek hari ini, <?= date('l, d F Y') ?></p>
</div>

<!-- Summary Cards (Pre-rendered Server Side) -->
<div class="summary-grid" id="summaryGrid">
  <div class="summary-card">
    <div class="summary-icon teal"><i class="bi bi-capsule-pill"></i></div>
    <div class="summary-info">
      <div class="summary-label">Total Jenis Obat</div>
      <div class="summary-value" id="statObat"><?= (int)$total_obat ?></div>
      <div class="summary-sub">item terdaftar di katalog</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon red"><i class="bi bi-exclamation-triangle"></i></div>
    <div class="summary-info">
      <div class="summary-label">Stok Kritis / Menipis</div>
      <div class="summary-value" id="statKritis" style="color:var(--danger);"><?= (int)$obat_kritis ?></div>
      <div class="summary-sub">perlu penambahan stok</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon teal"><i class="bi bi-cash-stack"></i></div>
    <div class="summary-info">
      <div class="summary-label">Pendapatan Hari Ini</div>
      <div class="summary-value" id="statPendapatan" style="font-size:1.35rem; color:var(--primary);"><?= formatRupiah((float)$today_data['total']) ?></div>
      <div class="summary-sub">akumulasi transaksi hari ini</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon blue"><i class="bi bi-receipt-cutoff"></i></div>
    <div class="summary-info">
      <div class="summary-label">Jumlah Transaksi</div>
      <div class="summary-value" id="statTransaksi"><?= (int)$today_data['jumlah'] ?></div>
      <div class="summary-sub">transaksi selesai hari ini</div>
    </div>
  </div>
</div>

<!-- Chart Pendapatan -->
<div class="card" style="margin-bottom: 1.5rem;">
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
    <div>
      <div class="card-title"><i class="bi bi-graph-up-arrow"></i> Tren Pendapatan Penjualan</div>
      <div style="font-size:0.8rem; color:var(--text-sub);">7 hari terakhir (harian)</div>
    </div>
    <div>
      <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">TOTAL 7 HARI: </span>
      <span id="chartTotal" style="font-size:1.1rem; font-weight:800; color:var(--primary); font-variant-numeric:tabular-nums;"><?= formatRupiah($total7_initial) ?></span>
    </div>
  </div>
  <div class="chart-container" style="position:relative; height:260px; width:100%;">
    <canvas id="trendChart"></canvas>
  </div>
</div>

<!-- Panels -->
<div class="panel-grid">
  <!-- Panel Obat Kritis -->
  <div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
      <div class="card-title" style="margin:0;"><i class="bi bi-exclamation-circle" style="color:var(--warning);"></i> &nbsp;Daftar Obat Kritis / Menipis</div>
      <a href="kelola-stok.php" class="btn btn-outline btn-sm">Kelola Stok <i class="bi bi-arrow-right"></i></a>
    </div>
    <div id="kritisPanel">
      <?php if (empty($daftar_kritis)): ?>
        <div class="empty-state"><i class="bi bi-check-circle" style="color:var(--success);"></i><p>Semua stok obat dalam kondisi aman</p></div>
      <?php else: ?>
        <div class="critical-list">
          <?php foreach ($daftar_kritis as $o):
            $stok = (int)$o['stok'];
            $min  = (int)$o['stok_minimum'] ?: 10;
            $pct  = min(100, (int)round(($stok / ($min * 2)) * 100));
            $colorClass = $stok === 0 ? 'danger' : 'warning';
            $isEmpty = $stok === 0 ? 'empty-stok' : '';
          ?>
            <div class="critical-item <?= $isEmpty ?>">
              <div class="critical-info">
                <div class="critical-name"><?= htmlspecialchars($o['nama_obat']) ?></div>
                <div class="critical-stock">Stok: <strong><?= $stok ?> <?= htmlspecialchars($o['satuan'] ?? '') ?></strong> (Batas min: <?= $min ?>)</div>
                <div class="stock-progress-bar">
                  <div class="stock-progress-fill <?= $colorClass ?>" style="width: <?= $pct ?>%;"></div>
                </div>
              </div>
              <a href="kelola-stok.php?tab=stok-masuk&obat=<?= $o['obat_id'] ?>" class="btn btn-sm btn-outline">Isi Stok</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Panel Baru Saja Terjual -->
  <div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
      <div class="card-title" style="margin:0;"><i class="bi bi-clock-history" style="color:var(--primary);"></i> &nbsp;Baru Saja Terjual</div>
      <a href="catalog.php" class="btn btn-outline btn-sm">Buka Katalog <i class="bi bi-arrow-right"></i></a>
    </div>
    <div id="recentPanel">
      <?php if (empty($recent_sales)): ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>Belum ada transaksi hari ini</p></div>
      <?php else: ?>
        <div class="recent-list">
          <?php foreach ($recent_sales as $r): ?>
            <div class="recent-item">
              <div class="recent-dot"></div>
              <div class="recent-info">
                <div class="recent-name"><?= htmlspecialchars($r['nama_obat'] ?? 'Obat') ?> × <?= (int)$r['jumlah'] ?></div>
                <div class="recent-meta"><?= date('d M, H:i', strtotime($r['tanggal_transaksi'])) ?> · <?= htmlspecialchars($r['metode_pembayaran']) ?></div>
              </div>
              <div class="recent-amount"><?= formatRupiah((float)$r['subtotal']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Load Chart.js with fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
let trendChart = null;
let initialTrend = <?= json_encode($trend) ?>;

function initChart(trendData) {
  const labels = trendData.map(t => t.label);
  const values = trendData.map(t => t.total);
  const total7 = values.reduce((a, b) => a + b, 0);
  const totalEl = document.getElementById('chartTotal');
  if (totalEl) totalEl.textContent = formatRupiah(total7);

  const canvas = document.getElementById('trendChart');
  if (!canvas) return;

  if (trendChart) {
    try { trendChart.destroy(); } catch (e) {}
  }

  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 220);
  gradient.addColorStop(0, 'rgba(20, 184, 166, 0.25)');
  gradient.addColorStop(1, 'rgba(20, 184, 166, 0)');

  trendChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Pendapatan',
        data: values,
        borderColor: '#0f766e',
        backgroundColor: gradient,
        borderWidth: 2.5,
        pointBackgroundColor: '#14b8a6',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
        tension: 0.35,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: { label: ctx => ' ' + formatRupiah(ctx.raw) },
          backgroundColor: '#1e293b',
          borderColor: '#e2e8f0',
          borderWidth: 1,
          titleColor: '#94a3b8',
          bodyColor: '#ffffff',
          bodyFont: { weight: 'bold' },
          padding: 10,
        }
      },
      scales: {
        x: {
          grid: { color: '#f1f5f9' },
          ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11, weight: 600 } },
        },
        y: {
          grid: { color: '#f1f5f9' },
          ticks: {
            color: '#64748b',
            font: { family: 'Plus Jakarta Sans', size: 11 },
            callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k'
          },
          beginAtZero: true,
        }
      }
    }
  });
}

function safeInitChart(trendData, retries = 0) {
  if (typeof Chart !== 'undefined') {
    initChart(trendData);
  } else if (retries < 30) {
    setTimeout(() => safeInitChart(trendData, retries + 1), 100);
  }
}

async function refreshDashboardData() {
  const data = await apiCall('api/get-dashboard.php');
  if (!data || !data.success) return;

  document.getElementById('statObat').textContent       = data.summary.total_obat;
  document.getElementById('statKritis').textContent     = data.summary.obat_kritis;
  document.getElementById('statPendapatan').textContent  = formatRupiah(data.summary.pendapatan_hari);
  document.getElementById('statTransaksi').textContent   = data.summary.transaksi_hari;

  safeInitChart(data.trend);

  const kritis = data.kritis;
  const kritisEl = document.getElementById('kritisPanel');
  if (!kritis.length) {
    kritisEl.innerHTML = `<div class="empty-state"><i class="bi bi-check-circle" style="color:var(--success);"></i><p>Semua stok obat dalam kondisi aman</p></div>`;
  } else {
    kritisEl.innerHTML = `<div class="critical-list">` + kritis.map(o => {
      const stok = +o.stok;
      const min  = +o.stok_minimum || 10;
      const pct  = Math.min(100, Math.round((stok / (min * 2)) * 100));
      const colorClass = stok === 0 ? 'danger' : 'warning';
      const isEmpty = stok === 0 ? 'empty-stok' : '';

      return `
        <div class="critical-item ${isEmpty}">
          <div class="critical-info">
            <div class="critical-name">${o.nama_obat}</div>
            <div class="critical-stock">Stok: <strong>${stok} ${o.satuan || ''}</strong> (Batas min: ${min})</div>
            <div class="stock-progress-bar">
              <div class="stock-progress-fill ${colorClass}" style="width: ${pct}%;"></div>
            </div>
          </div>
          <a href="kelola-stok.php?tab=stok-masuk&obat=${o.obat_id}" class="btn btn-sm btn-outline">Isi Stok</a>
        </div>
      `;
    }).join('') + `</div>`;
  }

  const recent = data.recent_sales;
  const recentEl = document.getElementById('recentPanel');
  if (!recent.length) {
    recentEl.innerHTML = `<div class="empty-state"><i class="bi bi-receipt"></i><p>Belum ada transaksi hari ini</p></div>`;
  } else {
    recentEl.innerHTML = `<div class="recent-list">` + recent.map(r => `
      <div class="recent-item">
        <div class="recent-dot"></div>
        <div class="recent-info">
          <div class="recent-name">${r.nama_obat || 'Obat'} × ${r.jumlah}</div>
          <div class="recent-meta">${new Date(r.tanggal_transaksi).toLocaleString('id-ID', {day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'})} · ${r.metode_pembayaran}</div>
        </div>
        <div class="recent-amount">${formatRupiah(r.subtotal || 0)}</div>
      </div>
    `).join('') + `</div>`;
  }
}

// Start safe initialization on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => safeInitChart(initialTrend));
} else {
  safeInitChart(initialTrend);
}

// Periodic background sync every 30s
setInterval(refreshDashboardData, 30000);
</script>

<?php include 'includes/footer.php'; ?>