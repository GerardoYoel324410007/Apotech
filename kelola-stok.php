<?php
require_once 'includes/config.php';
$pageTitle  = 'Kelola Stok';
$activePage = 'kelola';

// Server-side direct pre-render queries for instant 0ms load
$sql_obat = "
    SELECT
        o.*,
        s.nama_supplier,
        CASE
            WHEN o.stok = 0 THEN 'habis'
            WHEN o.stok <= o.stok_minimum THEN 'kritis'
            ELSE 'aman'
        END as status_stok
    FROM obat o
    LEFT JOIN supplier s ON o.supplier_id = s.supplier_id
    WHERE o.is_active = 1
    ORDER BY o.nama_obat ASC
";
$obat_list = $conn->query($sql_obat)->fetch_all(MYSQLI_ASSOC);

$supplier_list = $conn->query("SELECT supplier_id, nama_supplier FROM supplier ORDER BY nama_supplier")->fetch_all(MYSQLI_ASSOC);
$riwayat       = $conn->query("SELECT * FROM riwayat_stok ORDER BY waktu DESC LIMIT 25")->fetch_all(MYSQLI_ASSOC);

$defaultTab  = $_GET['tab'] ?? 'stok-masuk';
$defaultObat = (int)($_GET['obat'] ?? 0);

include 'includes/header.php';
?>

<div class="page-header">
  <h1>Kelola Stok & Master Obat</h1>
  <p>Kelola penerimaan stok masuk (restock) dan pembaruan data obat</p>
</div>

<!-- Tab Bar -->
<div class="tab-bar">
  <button class="tab-btn <?= $defaultTab === 'stok-masuk' ? 'active' : '' ?>" data-tab="stok-masuk">
    <i class="bi bi-box-arrow-in-down"></i> Stok Masuk (Restock)
  </button>
  <button class="tab-btn <?= $defaultTab === 'data-obat' ? 'active' : '' ?>" data-tab="data-obat">
    <i class="bi bi-database"></i> Data Master Obat
  </button>
</div>

<!-- ══ TAB: STOK MASUK ═══════════════════════════════════════ -->
<div class="tab-panel <?= $defaultTab === 'stok-masuk' ? 'active' : '' ?>" id="tab-stok-masuk">
  <div class="grid-2">
    <!-- Form Restock -->
    <div class="card">
      <div class="card-title" style="margin-bottom:1.25rem;"><i class="bi bi-plus-circle-fill" style="color:var(--primary);"></i> Input Stok Masuk</div>

      <div class="form-group">
        <label>Pilih Obat <span style="color:var(--danger)">*</span></label>
        <select class="form-select" id="stokObatSelect">
          <option value="">— Pilih Obat —</option>
          <?php foreach ($obat_list as $o): ?>
            <option value="<?= (int)$o['obat_id'] ?>"
                    data-stok="<?= (int)$o['stok'] ?>"
                    data-satuan="<?= htmlspecialchars($o['satuan'] ?? '') ?>"
                    data-harga="<?= (float)$o['harga_jual'] ?>"
                    <?= $defaultObat === (int)$o['obat_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($o['nama_obat']) ?> (Stok: <?= (int)$o['stok'] ?> <?= htmlspecialchars($o['satuan'] ?? '') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Info Current Stock -->
      <div id="stokInfo" style="display:none; margin-bottom:1.25rem;">
        <div style="background:var(--primary-mint); border:1px solid var(--primary-light); border-radius:var(--radius-sm); padding:0.85rem 1rem; display:flex; align-items:center; justify-content:space-between;">
          <span style="font-size:0.8rem; font-weight:600; color:var(--primary);">Stok Obat saat ini</span>
          <span id="stokSaatIni" style="font-weight:800; font-size:1.1rem; color:var(--primary);" class="num-tabular"></span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Jumlah Stok Masuk <span style="color:var(--danger)">*</span></label>
          <input type="number" class="form-control" id="jumlahMasuk" min="1" placeholder="Misal: 50">
        </div>
        <div class="form-group">
          <label>Update Harga Jual <span style="color:var(--text-muted); font-weight:400;">(opsional)</span></label>
          <input type="number" class="form-control" id="hargaBaru" min="0" step="100" placeholder="Kosongkan jika harga tetap">
        </div>
      </div>

      <div class="form-group">
        <label>Catatan Restock</label>
        <textarea class="form-control" id="catatanStok" rows="2" placeholder="Nomor faktur, supplier, atau catatan pengiriman..."></textarea>
      </div>

      <button class="btn btn-primary btn-full btn-lg" onclick="submitStokMasuk()">
        <i class="bi bi-box-arrow-in-down"></i> Simpan Stok Masuk
      </button>
    </div>

    <!-- Riwayat Restock (Pre-rendered Server Side) -->
    <div class="card">
      <div class="card-title" style="margin-bottom:1.25rem;"><i class="bi bi-clock-history"></i> Riwayat Restock Terakhir</div>

      <div id="riwayatList">
        <?php if (empty($riwayat)): ?>
          <div class="empty-state"><i class="bi bi-inbox"></i><p>Belum ada catatan riwayat stok masuk</p></div>
        <?php else: ?>
          <div class="table-wrapper" style="max-height:420px; overflow-y:auto;">
            <table>
              <thead>
                <tr>
                  <th>Obat</th>
                  <th>Jumlah</th>
                  <th>Waktu</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody id="riwayatTbody">
                <?php foreach ($riwayat as $r): ?>
                  <tr>
                    <td style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($r['nama_obat']) ?></td>
                    <td><span class="badge badge-teal">+<?= (int)$r['jumlah_masuk'] ?></span></td>
                    <td style="white-space:nowrap; font-size:0.8rem;" class="num-tabular"><?= date('d/m/Y H:i', strtotime($r['waktu'])) ?></td>
                    <td style="color:var(--text-sub); font-size:0.8rem;"><?= htmlspecialchars($r['catatan'] ?? '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══ TAB: DATA MASTER OBAT (Pre-rendered Server Side) ═════ -->
<div class="tab-panel <?= $defaultTab === 'data-obat' ? 'active' : '' ?>" id="tab-data-obat">
  <div style="display:flex; gap:0.75rem; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap;">
    <div class="search-bar" style="flex:1; min-width:240px; margin-bottom:0;">
      <i class="bi bi-search"></i>
      <input type="text" class="form-control" id="searchObat" placeholder="Cari obat berdasarkan nama...">
    </div>
    <button class="btn btn-primary" onclick="openObatModal()">
      <i class="bi bi-plus-lg"></i> Tambah Obat Baru
    </button>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Nama Obat</th>
          <th>Kategori</th>
          <th>Harga Beli / Jual</th>
          <th>Stok & Level</th>
          <th>Batas Min.</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="obatTbody">
        <?php if (empty($obat_list)): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada data obat terdaftar</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($obat_list as $o):
            $stok = (int)$o['stok'];
            $min  = (int)$o['stok_minimum'] ?: 10;
            $pct  = min(100, (int)round(($stok / ($min * 2)) * 100));
            $fillClass = $stok === 0 ? 'danger' : ($stok <= $min ? 'warning' : 'safe');

            $statusBadge = $o['status_stok'] === 'habis'
              ? '<span class="badge badge-red">Habis</span>'
              : ($o['status_stok'] === 'kritis'
              ? '<span class="badge badge-yellow">Menipis</span>'
              : '<span class="badge badge-green">Aman</span>');
          ?>
            <tr>
              <td style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($o['nama_obat']) ?></td>
              <td><span class="badge badge-gray"><?= htmlspecialchars($o['kategori']) ?></span></td>
              <td>
                <div style="font-size:0.85rem; font-weight:700; color:var(--primary);" class="num-tabular"><?= formatRupiah((float)$o['harga_jual']) ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted);" class="num-tabular">Beli: <?= formatRupiah((float)$o['harga_beli']) ?></div>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-main);" class="num-tabular"><?= $stok ?> <?= htmlspecialchars($o['satuan'] ?? '') ?></div>
                <div class="stock-progress-bar" style="width:100px;">
                  <div class="stock-progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%;"></div>
                </div>
              </td>
              <td style="color:var(--text-muted);" class="num-tabular"><?= $min ?></td>
              <td><?= $statusBadge ?></td>
              <td>
                <div style="display:flex; gap:0.35rem;">
                  <button class="btn btn-sm btn-secondary" onclick="editObatById(<?= (int)$o['obat_id'] ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="deleteObat(<?= (int)$o['obat_id'] ?>, '<?= addslashes($o['nama_obat']) ?>')" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Form Tambah/Edit Obat -->
<div class="modal-overlay" id="obatModalOverlay">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header">
      <div class="modal-title" id="obatModalTitle">Tambah Obat Baru</div>
      <button class="modal-close" onclick="closeObatModal()"><i class="bi bi-x"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editObatId">
      <div class="form-row">
        <div class="form-group" style="grid-column: 1/-1;">
          <label>Nama Obat <span style="color:var(--danger)">*</span></label>
          <input type="text" class="form-control" id="fNama" placeholder="Contoh: Paracetamol 500mg">
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <input type="text" class="form-control" id="fKategori" placeholder="analgesik, antibiotik...">
        </div>
        <div class="form-group">
          <label>Golongan Obat</label>
          <select class="form-select" id="fGolongan">
            <option value="bebas">Bebas</option>
            <option value="bebas_terbatas">Bebas Terbatas</option>
            <option value="keras">Keras / Resep</option>
            <option value="psikotropika">Psikotropika</option>
          </select>
        </div>
        <div class="form-group">
          <label>Bentuk Sediaan</label>
          <select class="form-select" id="fBentuk">
            <option value="tablet">Tablet</option>
            <option value="kapsul">Kapsul</option>
            <option value="sirup">Sirup</option>
            <option value="salep">Salep</option>
            <option value="injeksi">Injeksi</option>
            <option value="tetes">Tetes</option>
            <option value="sachet">Sachet</option>
            <option value="botol">Botol</option>
          </select>
        </div>
        <div class="form-group">
          <label>Ukuran / Berat Kemasan</label>
          <input type="text" class="form-control" id="fUkuran" placeholder="500mg, 60ml...">
        </div>
        <div class="form-group">
          <label>Harga Beli (Rp) <span style="color:var(--danger)">*</span></label>
          <input type="number" class="form-control" id="fHargaBeli" min="0" step="100" placeholder="0">
        </div>
        <div class="form-group">
          <label>Harga Jual (Rp) <span style="color:var(--danger)">*</span></label>
          <input type="number" class="form-control" id="fHarga" min="0" step="100" placeholder="0">
        </div>
        <div class="form-group">
          <label>Stok Awal</label>
          <input type="number" class="form-control" id="fStok" min="0" value="0">
        </div>
        <div class="form-group">
          <label>Batas Stok Minimum</label>
          <input type="number" class="form-control" id="fMinStok" min="0" value="10">
        </div>
        <div class="form-group">
          <label>Pilihan Dosis <span style="color:var(--text-muted); font-weight:400;">(pisahkan koma)</span></label>
          <input type="text" class="form-control" id="fDosis" placeholder="500mg, 250mg...">
        </div>
        <div class="form-group">
          <label>Pilihan Varian Rasa <span style="color:var(--text-muted); font-weight:400;">(pisahkan koma)</span></label>
          <input type="text" class="form-control" id="fRasa" placeholder="Original, Mint, Jeruk...">
        </div>
        <div class="form-group" style="grid-column: 1/-1;">
          <label><i class="bi bi-image"></i> Gambar Produk Obat</label>
          <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
            <input type="file" class="form-control" id="fGambarFile" accept="image/*" style="flex:1; min-width:200px;">
            <span style="font-size:0.8rem; color:var(--text-muted);">atau URL:</span>
            <input type="text" class="form-control" id="fGambarUrl" placeholder="https://..." style="flex:1; min-width:200px;">
          </div>
          <div id="imgPreviewBox" style="margin-top:0.75rem; display:none;">
            <img id="imgPreview" src="" alt="Preview" style="max-height:80px; border-radius:6px; border:1px solid var(--border);">
          </div>
        </div>

        <div class="form-group" style="grid-column: 1/-1;">
          <label>Supplier</label>
          <select class="form-select" id="fSupplier">
            <option value="">— Pilih Supplier —</option>
            <?php foreach ($supplier_list as $s): ?>
              <option value="<?= (int)$s['supplier_id'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column: 1/-1;">
          <label>Deskripsi & Indikasi Obat</label>
          <textarea class="form-control" id="fDeskripsi" rows="3" placeholder="Deskripsi penggunaan, khasiat, indikasi, atau efikasi..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeObatModal()">Batal</button>
      <button class="btn btn-primary" onclick="saveObat()">
        <i class="bi bi-check-lg"></i> Simpan Data Obat
      </button>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="deleteModalOverlay">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <div class="modal-title">Konfirmasi Hapus Obat</div>
      <button class="modal-close" onclick="closeDeleteModal()"><i class="bi bi-x"></i></button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-sub);">Obat <strong id="deleteObatNama" style="color:var(--text-main);"></strong> akan dinonaktifkan dari katalog. Riwayat transaksi historis tidak akan terhapus.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
      <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
        <i class="bi bi-trash"></i> Hapus Obat
      </button>
    </div>
  </div>
</div>

<script>
let allObatData = <?= json_encode($obat_list) ?>;
const stokSelect = document.getElementById('stokObatSelect');

// Tab switching
document.querySelectorAll('.tab-btn').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    document.getElementById(`tab-${b.dataset.tab}`).classList.add('active');
  });
});

// Select Event Handler
stokSelect.addEventListener('change', () => {
  const opt = stokSelect.selectedOptions[0];
  const stokInfo = document.getElementById('stokInfo');
  if (!opt || !opt.value) { stokInfo.style.display = 'none'; return; }
  document.getElementById('stokSaatIni').textContent = `${opt.dataset.stok} ${opt.dataset.satuan}`;
  document.getElementById('hargaBaru').placeholder = `Saat ini: Rp ${(+opt.dataset.harga).toLocaleString('id-ID')}`;
  stokInfo.style.display = 'block';
});

<?php if ($defaultObat): ?>
stokSelect.value = <?= $defaultObat ?>;
stokSelect.dispatchEvent(new Event('change'));
<?php endif; ?>

async function loadObatTable() {
  const data = await apiCall('api/get-obat.php');
  if (!data || !data.success) return;

  allObatData = data.obat;
  renderObatTable(allObatData);
  updateStokSelectOptions(allObatData);
}

function updateStokSelectOptions(obatList) {
  const currentVal = stokSelect.value;
  stokSelect.innerHTML = `<option value="">— Pilih Obat —</option>` + obatList.map(o => `
    <option value="${o.obat_id}"
            data-stok="${o.stok}"
            data-satuan="${o.satuan || ''}"
            data-harga="${o.harga_jual}">
      ${o.nama_obat} (Stok: ${o.stok} ${o.satuan || ''})
    </option>
  `).join('');
  if (currentVal) stokSelect.value = currentVal;
}

function statusBadge(status) {
  if (status === 'habis') return `<span class="badge badge-red">Habis</span>`;
  if (status === 'kritis') return `<span class="badge badge-yellow">Menipis</span>`;
  return `<span class="badge badge-green">Aman</span>`;
}

function renderObatTable(obat) {
  const tbody = document.getElementById('obatTbody');
  if (!tbody) return;

  if (!obat.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada data obat terdaftar</p></div></td></tr>`;
    return;
  }

  tbody.innerHTML = obat.map(o => {
    const stok = +o.stok;
    const min  = +o.stok_minimum || 10;
    const pct  = Math.min(100, Math.round((stok / (min * 2)) * 100));
    const fillClass = stok === 0 ? 'danger' : stok <= min ? 'warning' : 'safe';

    return `
      <tr>
        <td style="font-weight:700; color:var(--text-main);">${o.nama_obat}</td>
        <td><span class="badge badge-gray">${o.kategori}</span></td>
        <td>
          <div style="font-size:0.85rem; font-weight:700; color:var(--primary);" class="num-tabular">${formatRupiah(o.harga_jual)}</div>
          <div style="font-size:0.75rem; color:var(--text-muted);" class="num-tabular">Beli: ${formatRupiah(o.harga_beli)}</div>
        </td>
        <td>
          <div style="font-weight:700; color:var(--text-main);" class="num-tabular">${o.stok} ${o.satuan || ''}</div>
          <div class="stock-progress-bar" style="width:100px;">
            <div class="stock-progress-fill ${fillClass}" style="width:${pct}%;"></div>
          </div>
        </td>
        <td style="color:var(--text-muted);" class="num-tabular">${o.stok_minimum}</td>
        <td>${statusBadge(o.status_stok)}</td>
        <td>
          <div style="display:flex; gap:0.35rem;">
            <button class="btn btn-sm btn-secondary" onclick="editObatById(${o.obat_id})" title="Edit"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-danger" onclick="deleteObat(${o.obat_id}, '${o.nama_obat.replace(/'/g,"\\'")}')" title="Hapus"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function editObatById(id) {
  const obat = allObatData.find(o => +o.obat_id === id);
  if (obat) openObatModal(obat);
}

// Submit Stok Masuk
async function submitStokMasuk() {
  const obat_id    = +stokSelect.value;
  const jumlah     = +document.getElementById('jumlahMasuk').value;
  const harga_baru = document.getElementById('hargaBaru').value;
  const catatan    = document.getElementById('catatanStok').value;
  const btn        = document.querySelector('#tab-stok-masuk .btn-primary');

  if (!obat_id) { showToast('Pilih obat terlebih dahulu', 'error'); return; }
  if (!jumlah || jumlah < 1) { showToast('Jumlah stok masuk tidak valid', 'error'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Menyimpan Restock...';

  const res = await apiCall('api/stok-masuk.php', {
    method: 'POST',
    body: JSON.stringify({ obat_id, jumlah, harga_baru: harga_baru || null, catatan })
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-box-arrow-in-down"></i> Simpan Stok Masuk';

  if (res && res.success) {
    showToast(res.message, 'success');
    stokSelect.value = '';
    document.getElementById('stokInfo').style.display = 'none';
    document.getElementById('jumlahMasuk').value = '';
    document.getElementById('hargaBaru').value = '';
    document.getElementById('catatanStok').value = '';
    loadRiwayat();
    loadObatTable();
  } else {
    showToast(res?.message || 'Gagal menyimpan restock', 'error');
  }
}

// Load Riwayat Stok
async function loadRiwayat() {
  const res = await apiCall('api/get-riwayat.php');
  if (!res || !res.success) return;

  const riwayat = res.riwayat;
  const listEl  = document.getElementById('riwayatList');

  if (!riwayat || !riwayat.length) {
    listEl.innerHTML = `<div class="empty-state"><i class="bi bi-inbox"></i><p>Belum ada riwayat stok masuk</p></div>`;
    return;
  }

  const rows = riwayat.map(r => `
    <tr>
      <td style="font-weight:600; color:var(--text-main);">${r.nama_obat}</td>
      <td><span class="badge badge-teal">+${r.jumlah_masuk}</span></td>
      <td style="white-space:nowrap; font-size:0.8rem;" class="num-tabular">${new Date(r.waktu).toLocaleString('id-ID', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})}</td>
      <td style="color:var(--text-sub); font-size:0.8rem;">${r.catatan || '—'}</td>
    </tr>
  `).join('');

  listEl.innerHTML = `
    <div class="table-wrapper" style="max-height:420px; overflow-y:auto;">
      <table>
        <thead>
          <tr>
            <th>Obat</th>
            <th>Jumlah</th>
            <th>Waktu</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody id="riwayatTbody">${rows}</tbody>
      </table>
    </div>
  `;
}

// Search Obat Table
document.getElementById('searchObat').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderObatTable(allObatData.filter(o => o.nama_obat.toLowerCase().includes(q)));
});

// Modal Obat Handler
function openObatModal(obat = null) {
  document.getElementById('obatModalTitle').textContent = obat ? 'Edit Data Obat' : 'Tambah Obat Baru';
  document.getElementById('editObatId').value    = obat?.obat_id || '';
  document.getElementById('fNama').value         = obat?.nama_obat || '';
  document.getElementById('fKategori').value     = obat?.kategori || '';
  document.getElementById('fGolongan').value     = obat?.jenis_golongan || 'bebas';
  document.getElementById('fBentuk').value       = obat?.bentuk_sediaan || 'tablet';
  document.getElementById('fUkuran').value       = obat?.ukuran_berat || '';
  document.getElementById('fHargaBeli').value    = obat?.harga_beli || '';
  document.getElementById('fHarga').value        = obat?.harga_jual || '';
  document.getElementById('fStok').value         = obat?.stok || 0;
  document.getElementById('fMinStok').value      = obat?.stok_minimum || 10;
  document.getElementById('fDosis').value        = obat?.dosis || 'Standard';
  document.getElementById('fRasa').value         = obat?.varian_rasa || 'Original';
  document.getElementById('fGambarUrl').value    = obat?.gambar_url || '';
  document.getElementById('fGambarFile').value   = '';
  document.getElementById('fDeskripsi').value    = obat?.deskripsi || '';
  document.getElementById('fSupplier').value     = obat?.supplier_id || '';

  const previewBox = document.getElementById('imgPreviewBox');
  const previewImg = document.getElementById('imgPreview');
  if (obat?.gambar_url) {
    previewImg.src = obat.gambar_url;
    previewBox.style.display = 'block';
  } else {
    previewBox.style.display = 'none';
  }

  document.getElementById('obatModalOverlay').classList.add('open');
}

function closeObatModal() {
  document.getElementById('obatModalOverlay').classList.remove('open');
}

document.getElementById('obatModalOverlay').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeObatModal();
});

// Live image URL preview
document.getElementById('fGambarUrl').addEventListener('input', function() {
  const url = this.value.trim();
  const previewBox = document.getElementById('imgPreviewBox');
  const previewImg = document.getElementById('imgPreview');
  if (url) {
    previewImg.src = url;
    previewBox.style.display = 'block';
  } else {
    previewBox.style.display = 'none';
  }
});

async function saveObat() {
  const formData = new FormData();
  formData.append('id', document.getElementById('editObatId').value || '0');
  formData.append('nama_obat', document.getElementById('fNama').value);
  formData.append('kategori', document.getElementById('fKategori').value);
  formData.append('jenis_golongan', document.getElementById('fGolongan').value);
  formData.append('bentuk_sediaan', document.getElementById('fBentuk').value);
  formData.append('ukuran_berat', document.getElementById('fUkuran').value);
  formData.append('harga_beli', document.getElementById('fHargaBeli').value);
  formData.append('harga_jual', document.getElementById('fHarga').value);
  formData.append('stok', document.getElementById('fStok').value);
  formData.append('stok_minimum', document.getElementById('fMinStok').value);
  formData.append('dosis', document.getElementById('fDosis').value || 'Standard');
  formData.append('varian_rasa', document.getElementById('fRasa').value || 'Original');
  formData.append('gambar_url', document.getElementById('fGambarUrl').value || '');
  formData.append('deskripsi', document.getElementById('fDeskripsi').value);
  formData.append('satuan', document.getElementById('fBentuk').value);
  formData.append('supplier_id', document.getElementById('fSupplier').value || '');

  const fileInput = document.getElementById('fGambarFile');
  if (fileInput.files.length > 0) {
    formData.append('gambar_file', fileInput.files[0]);
  }

  if (!document.getElementById('fNama').value) { showToast('Nama obat wajib diisi', 'error'); return; }
  if (!document.getElementById('fHarga').value) { showToast('Harga jual wajib diisi', 'error'); return; }

  try {
    const res = await fetch('api/save-obat.php', {
      method: 'POST',
      body: formData
    }).then(r => r.json());

    if (res && res.success) {
      showToast(res.message, 'success');
      closeObatModal();
      loadObatTable();
    } else {
      showToast(res?.message || 'Gagal menyimpan data obat', 'error');
    }
  } catch (err) {
    showToast('Gagal terhubung ke server', 'error');
  }
}

// Delete Obat Handler
let deleteTargetId = null;

function deleteObat(id, nama) {
  deleteTargetId = id;
  document.getElementById('deleteObatNama').textContent = nama;
  document.getElementById('deleteModalOverlay').classList.add('open');
}

function closeDeleteModal() {
  document.getElementById('deleteModalOverlay').classList.remove('open');
  deleteTargetId = null;
}

document.getElementById('deleteModalOverlay').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeDeleteModal();
});

async function confirmDelete() {
  if (!deleteTargetId) return;
  const btn = document.getElementById('confirmDeleteBtn');
  btn.disabled = true;

  const res = await apiCall('api/delete-obat.php', { method: 'POST', body: JSON.stringify({ id: deleteTargetId }) });
  btn.disabled = false;

  if (res && res.success) {
    showToast(res.message, 'success');
    closeDeleteModal();
    loadObatTable();
  } else {
    showToast(res?.message || 'Gagal menghapus obat', 'error');
  }
}
</script>

<?php include 'includes/footer.php'; ?>
