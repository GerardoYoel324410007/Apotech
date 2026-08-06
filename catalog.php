<?php
require_once 'includes/config.php';
$pageTitle  = 'Katalog & Kasir Apotek';
$activePage = 'catalog';

$sql = "
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
$res_initial = $conn->query($sql);
$initial_obat = $res_initial->fetch_all(MYSQLI_ASSOC);

$cats_initial = $conn->query("SELECT DISTINCT kategori FROM obat WHERE is_active = 1 ORDER BY kategori");
$initial_categories = array_column($cats_initial->fetch_all(MYSQLI_ASSOC), 'kategori');

$emojis_map = [
    'analgesik'      => '💊',
    'antibiotik'     => '🧬',
    'antivirus'      => '🦠',
    'antihipertensi' => '❤️',
    'vitamin'        => '🌿',
    'antasid'        => '🧪',
    'PPI'            => '💉',
    'sirup'          => '🍶',
    'salep'          => '🧴',
    'antihistamin'   => '🤧',
    'bronkodilator'  => '🫁',
    'antidiabetes'   => '🩸',
    'topikal'        => '🧴',
    'ekspektoran'    => '🍵',
    'oftalmologi'    => '👁️',
    'default'        => '💊',
];

include 'includes/header.php';
?>

<div class="page-header">
  <h1>Katalog Obat & Pemesanan Kasir</h1>
  <p>Klik obat untuk membuka Pop-Up Kustomisasi & Kalkulator Dosis/Rasa, lalu tambahkan ke keranjang</p>
</div>

<div class="catalog-container">
  <!-- Left Sidebar Filter -->
  <div class="sidebar-filter">
    <div class="card-title" style="margin-bottom:1rem;"><i class="bi bi-funnel"></i> Filter Katalog</div>

    <!-- Search -->
    <div class="form-group">
      <label>Cari Nama Obat</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Ketik nama obat...">
      </div>
    </div>

    <!-- Kategori -->
    <div class="form-group">
      <label>Kategori</label>
      <div class="filter-chips" id="kategoriFilter">
        <button class="filter-chip active" data-val="semua">Semua</button>
        <?php foreach ($initial_categories as $cat): ?>
          <button class="filter-chip" data-val="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Golongan -->
    <div class="form-group">
      <label>Golongan Obat</label>
      <div class="filter-chips">
        <button class="filter-chip active" data-golongan="semua">Semua</button>
        <button class="filter-chip" data-golongan="bebas">Bebas</button>
        <button class="filter-chip" data-golongan="bebas_terbatas">Bebas Terbatas</button>
        <button class="filter-chip" data-golongan="keras">Keras/Resep</button>
      </div>
    </div>

    <!-- Status Stok -->
    <div class="form-group">
      <label>Status Stok</label>
      <div class="filter-chips">
        <button class="filter-chip active" data-stok="semua">Semua</button>
        <button class="filter-chip" data-stok="aman">Tersedia</button>
        <button class="filter-chip" data-stok="kritis">Menipis/Habis</button>
      </div>
    </div>

    <div style="border-top:1px solid var(--border); padding-top:0.75rem; font-size:0.8rem; color:var(--text-muted);" id="resultCount">
      <?= count($initial_obat) ?> obat ditemukan
    </div>
  </div>

  <!-- Center Product Grid (Pre-rendered Server Side) -->
  <div>
    <div class="products-grid" id="productsGrid">
      <?php foreach ($initial_obat as $o):
        $emoji = $emojis_map[$o['kategori']] ?? $emojis_map['default'];
        $stok = (int)$o['stok'];
        $min  = (int)$o['stok_minimum'] ?: 10;
        $pct  = min(100, (int)round(($stok / ($min * 2)) * 100));
        $fillClass = $stok === 0 ? 'danger' : ($stok <= $min ? 'warning' : 'safe');

        $statusBadge = $o['status_stok'] === 'habis'
          ? '<span class="badge badge-red">Habis</span>'
          : ($o['status_stok'] === 'kritis'
          ? '<span class="badge badge-yellow">Stok: ' . $stok . '</span>'
          : '<span class="badge badge-green">Stok: ' . $stok . '</span>');
      ?>
        <div class="product-card" onclick="openCalculatorModal(<?= (int)$o['obat_id'] ?>)">
          <div class="product-img">
            <?php if (!empty($o['gambar_url'])): ?>
              <img src="<?= htmlspecialchars($o['gambar_url']) ?>" alt="<?= htmlspecialchars($o['nama_obat']) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
            <?php else: ?>
              <?= $emoji ?>
            <?php endif; ?>
          </div>
          <div class="product-info">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.25rem;">
              <div class="product-name"><?= htmlspecialchars($o['nama_obat']) ?></div>
              <?= $statusBadge ?>
            </div>
            <div class="product-cat"><?= htmlspecialchars($o['kategori']) ?><?= $o['ukuran_berat'] ? ' · ' . htmlspecialchars($o['ukuran_berat']) : '' ?></div>
            <div class="stock-progress-bar">
              <div class="stock-progress-fill <?= $fillClass ?>" style="width:<?= $pct ?>%;"></div>
            </div>
            <div class="product-price"><?= formatRupiah((float)$o['harga_jual']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Side Dedicated Shopping Cart Panel -->
  <div class="order-side-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <div class="card-title" style="margin:0;"><i class="bi bi-cart3"></i> Keranjang Belanja</div>
      <span class="badge badge-teal" id="cartCountBadge">0 Item</span>
    </div>

    <div id="emptyCartState" class="empty-state" style="padding:2rem 0;">
      <i class="bi bi-cart-x" style="font-size:2.5rem; margin-bottom:0.5rem; display:block;"></i>
      <p style="font-size:0.875rem; font-weight:600; color:var(--text-main); margin-bottom:0.2rem;">Keranjang Masih Kosong</p>
      <p style="font-size:0.8rem; color:var(--text-muted);">Klik obat di katalog untuk menghitung & menambahkan</p>
    </div>

    <div id="cartContent" style="display:none;">
      <div id="cartItemsList" style="display:flex; flex-direction:column; gap:0.65rem; max-height:300px; overflow-y:auto; margin-bottom:1rem; padding-right:0.25rem;">
        <!-- Cart items render here -->
      </div>

      <!-- Payment Method -->
      <div class="form-group">
        <label>Metode Pembayaran</label>
        <select class="form-select" id="metodeSelect">
          <option value="tunai">💵 Tunai</option>
          <option value="qris">📱 QRIS</option>
          <option value="transfer">🏦 Transfer Bank</option>
          <option value="kartu">💳 Kartu Debit/Kredit</option>
        </select>
      </div>

      <!-- Grand Total Box -->
      <div style="background:var(--bg-muted); border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.875rem 1rem; margin-bottom:1rem;">
        <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.25rem;">
          <span>TOTAL PEMBAYARAN</span>
        </div>
        <div id="grandTotalDisplay" style="font-size:1.6rem; font-weight:800; color:var(--primary);" class="num-tabular">Rp 0</div>
      </div>

      <button class="btn btn-primary btn-full btn-lg" id="checkoutBtn" onclick="checkoutCart()">
        <i class="bi bi-check-circle-fill"></i> Bayar Sekarang
      </button>
    </div>
  </div>
</div>

<!-- ══ POP-UP MODAL: KALKULATOR & KUSTOMISASI OBAT ════════════ -->
<div class="modal-overlay" id="calcModalOverlay">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <div style="display:flex; align-items:center; gap:0.75rem;">
        <div id="popEmoji" style="font-size:2rem; line-height:1;">💊</div>
        <div>
          <div class="modal-title" id="popNama">—</div>
          <div id="popKategori" style="font-size:0.78rem; color:var(--text-muted);">—</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeCalcModal()"><i class="bi bi-x"></i></button>
    </div>

    <div class="modal-body">
      <!-- Info & Price -->
      <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-muted); padding:0.875rem 1rem; border-radius:var(--radius-sm); margin-bottom:1.25rem;">
        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">HARGA SATUAN</div>
          <div id="popHarga" style="font-size:1.35rem; font-weight:800; color:var(--primary);" class="num-tabular">Rp 0</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">STOK TERSEDIA</div>
          <div id="popStok" style="font-size:1.1rem; font-weight:800; color:var(--text-main);" class="num-tabular">—</div>
        </div>
      </div>

      <!-- Deskripsi Obat Box -->
      <div style="background:var(--bg-app); border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.875rem; margin-bottom:1.25rem; font-size:0.825rem; color:var(--text-sub);">
        <div style="font-weight:700; color:var(--text-main); margin-bottom:0.3rem;"><i class="bi bi-info-circle" style="color:var(--primary);"></i> Deskripsi & Indikasi Obat</div>
        <div id="popDeskripsi" style="line-height:1.45;">—</div>
      </div>

      <!-- Opsi Dosis / Ukuran -->
      <div class="form-group" id="popDosisGroup">
        <label><i class="bi bi-capsule"></i> Pilih Dosis / Ukuran Standar</label>
        <div class="filter-chips" id="popDosisChips"></div>
      </div>

      <!-- Opsi Varian Rasa -->
      <div class="form-group" id="popRasaGroup">
        <label><i class="bi bi-palette"></i> Pilih Varian Rasa</label>
        <div class="filter-chips" id="popRasaChips"></div>
      </div>

      <!-- Quantity Stepper -->
      <div class="form-group">
        <label><i class="bi bi-hash"></i> Jumlah Item</label>
        <div style="display:flex; align-items:center; gap:1rem;">
          <div class="qty-stepper">
            <button class="qty-btn" onclick="changePopQty(-1)"><i class="bi bi-dash"></i></button>
            <input type="number" class="qty-input" id="popQtyInput" value="1" min="1">
            <button class="qty-btn" onclick="changePopQty(1)"><i class="bi bi-plus"></i></button>
          </div>
          <span style="font-size:0.85rem; color:var(--text-sub); font-weight:600;" id="popSatuan">satuan</span>
        </div>
      </div>

      <!-- Subtotal Box -->
      <div style="background:var(--primary-mint); border:1px solid var(--primary-light); border-radius:var(--radius-sm); padding:0.875rem 1rem; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:0.75rem; font-weight:700; color:var(--primary); text-transform:uppercase;">Subtotal Item</span>
        <span id="popSubtotalDisplay" style="font-size:1.35rem; font-weight:800; color:var(--primary);" class="num-tabular">Rp 0</span>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeCalcModal()">Batal</button>
      <button class="btn btn-primary" onclick="addPopItemToCart()">
        <i class="bi bi-cart-plus-fill"></i> + Tambah ke Keranjang
      </button>
    </div>
  </div>
</div>

<script>
let allObat = <?= json_encode($initial_obat) ?>;
let activeObat = null;
let activeDosis = 'Standard';
let activeRasa  = 'Original';

let activeKategori = 'semua';
let activeGolongan = 'semua';
let activeStok = 'semua';

// Shopping Cart Array
let cart = [];

const emojis = <?= json_encode($emojis_map) ?>;

async function loadObat() {
  const search  = document.getElementById('searchInput').value;
  const params  = new URLSearchParams({ search, kategori: activeKategori, golongan: activeGolongan });
  const data    = await apiCall(`api/get-obat.php?${params}`);
  if (!data || !data.success) { showToast('Gagal memuat data obat', 'error'); return; }

  allObat = data.obat;

  // Render Kategori Chips
  const chipContainer = document.getElementById('kategoriFilter');
  chipContainer.innerHTML = `<button class="filter-chip ${activeKategori === 'semua' ? 'active' : ''}" data-val="semua">Semua</button>`;
  data.kategori.forEach(k => {
    const active = activeKategori === k ? 'active' : '';
    chipContainer.innerHTML += `<button class="filter-chip ${active}" data-val="${k}">${k}</button>`;
  });
  chipContainer.querySelectorAll('.filter-chip').forEach(b => b.addEventListener('click', e => {
    activeKategori = e.target.dataset.val;
    chipContainer.querySelectorAll('.filter-chip').forEach(x => x.classList.remove('active'));
    e.target.classList.add('active');
    renderGrid(filterObat());
  }));

  renderGrid(filterObat());
}

function filterObat() {
  return allObat.filter(o => {
    // Filter Kategori
    if (activeKategori !== 'semua' && (o.kategori || '').toLowerCase() !== activeKategori.toLowerCase()) {
      return false;
    }
    // Filter Golongan Obat
    if (activeGolongan !== 'semua' && o.jenis_golongan !== activeGolongan) {
      return false;
    }
    // Filter Status Stok
    if (activeStok === 'aman' && o.status_stok !== 'aman') return false;
    if (activeStok === 'kritis' && o.status_stok === 'aman') return false;

    return true;
  });
}

function renderGrid(obat) {
  const grid = document.getElementById('productsGrid');
  document.getElementById('resultCount').textContent = `${obat.length} obat ditemukan`;

  if (!obat.length) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-search"></i><p>Tidak ada obat yang sesuai filter</p></div>`;
    return;
  }

  grid.innerHTML = obat.map(o => {
    const emoji = emojis[o.kategori] || emojis.default;
    const stok = +o.stok;
    const min  = +o.stok_minimum || 10;
    const pct  = Math.min(100, Math.round((stok / (min * 2)) * 100));
    const fillClass = stok === 0 ? 'danger' : stok <= min ? 'warning' : 'safe';

    const statusBadge = o.status_stok === 'habis'
      ? `<span class="badge badge-red">Habis</span>`
      : o.status_stok === 'kritis'
      ? `<span class="badge badge-yellow">Stok: ${stok}</span>`
      : `<span class="badge badge-green">Stok: ${stok}</span>`;

    const imgDisplay = o.gambar_url
      ? `<img src="${o.gambar_url}" alt="${o.nama_obat}" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">`
      : emoji;

    return `
      <div class="product-card" onclick="openCalculatorModal(${o.obat_id})">
        <div class="product-img">
          ${imgDisplay}
        </div>
        <div class="product-info">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.25rem;">
            <div class="product-name">${o.nama_obat}</div>
            ${statusBadge}
          </div>
          <div class="product-cat">${o.kategori}${o.ukuran_berat ? ' · ' + o.ukuran_berat : ''}</div>
          <div class="stock-progress-bar">
            <div class="stock-progress-fill ${fillClass}" style="width:${pct}%;"></div>
          </div>
          <div class="product-price">${formatRupiah(o.harga_jual)}</div>
        </div>
      </div>
    `;
  }).join('');
}

// ── Open Pop-Up Modal Calculator ─────────────────────────────
function openCalculatorModal(id) {
  activeObat = allObat.find(o => +o.obat_id === id);
  if (!activeObat) return;

  const o = activeObat;
  const popEmojiEl = document.getElementById('popEmoji');
  if (o.gambar_url) {
    popEmojiEl.innerHTML = `<img src="${o.gambar_url}" alt="${o.nama_obat}" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid var(--border);">`;
  } else {
    popEmojiEl.textContent = emojis[o.kategori] || emojis.default;
  }

  document.getElementById('popNama').textContent      = o.nama_obat;
  document.getElementById('popKategori').textContent  = `${o.kategori} · Golongan ${o.jenis_golongan || 'Bebas'}`;
  document.getElementById('popHarga').textContent     = formatRupiah(o.harga_jual);
  document.getElementById('popStok').textContent      = `${o.stok} ${o.satuan || ''}`;
  document.getElementById('popDeskripsi').textContent = o.deskripsi || `Obat ${o.kategori}. Harap gunakan sesuai dosis petunjuk apoteker.`;
  document.getElementById('popSatuan').textContent    = o.satuan || 'unit';

  // Render Dosis Chips
  const dosisArr = (o.dosis || 'Standard').split(',').map(s => s.trim()).filter(Boolean);
  activeDosis = dosisArr[0] || 'Standard';
  const dosisBox = document.getElementById('popDosisChips');
  dosisBox.innerHTML = dosisArr.map((d, idx) => `
    <button class="filter-chip ${idx === 0 ? 'active' : ''}" onclick="setPopDosis('${d}', this)">${d}</button>
  `).join('');

  // Render Rasa Chips
  const rasaArr = (o.varian_rasa || 'Original').split(',').map(s => s.trim()).filter(Boolean);
  activeRasa = rasaArr[0] || 'Original';
  const rasaBox = document.getElementById('popRasaChips');
  rasaBox.innerHTML = rasaArr.map((r, idx) => `
    <button class="filter-chip ${idx === 0 ? 'active' : ''}" onclick="setPopRasa('${r}', this)">${r}</button>
  `).join('');

  const qtyInp = document.getElementById('popQtyInput');
  qtyInp.value = 1;
  qtyInp.max = o.stok;

  updatePopSubtotal();
  document.getElementById('calcModalOverlay').classList.add('open');
}

function closeCalcModal() {
  document.getElementById('calcModalOverlay').classList.remove('open');
  activeObat = null;
}

document.getElementById('calcModalOverlay').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeCalcModal();
});

function setPopDosis(dosis, btn) {
  activeDosis = dosis;
  btn.parentElement.querySelectorAll('.filter-chip').forEach(x => x.classList.remove('active'));
  btn.classList.add('active');
}

function setPopRasa(rasa, btn) {
  activeRasa = rasa;
  btn.parentElement.querySelectorAll('.filter-chip').forEach(x => x.classList.remove('active'));
  btn.classList.add('active');
}

function changePopQty(delta) {
  if (!activeObat) return;
  const inp = document.getElementById('popQtyInput');
  const max = +activeObat.stok || Infinity;
  let val = Math.max(1, Math.min(max, +inp.value + delta));
  inp.value = val;
  updatePopSubtotal();
}

document.getElementById('popQtyInput').addEventListener('input', updatePopSubtotal);

function updatePopSubtotal() {
  if (!activeObat) return;
  const qty = +document.getElementById('popQtyInput').value || 0;
  const sub = qty * +activeObat.harga_jual;
  document.getElementById('popSubtotalDisplay').textContent = formatRupiah(sub);
}

function addPopItemToCart() {
  if (!activeObat) return;
  const qty = +document.getElementById('popQtyInput').value || 1;

  if (qty < 1 || qty > +activeObat.stok) {
    showToast('Jumlah tidak valid atau melebihi stok tersedia', 'error');
    return;
  }

  const cartKey = `${activeObat.obat_id}_${activeDosis}_${activeRasa}`;
  const existing = cart.find(c => c.key === cartKey);

  if (existing) {
    if (existing.jumlah + qty > +activeObat.stok) {
      showToast(`Stok ${activeObat.nama_obat} tidak mencukupi di keranjang`, 'error');
      return;
    }
    existing.jumlah += qty;
    existing.subtotal = existing.jumlah * existing.harga_jual;
  } else {
    cart.push({
      key:        cartKey,
      obat_id:    activeObat.obat_id,
      nama_obat:  activeObat.nama_obat,
      harga_jual: +activeObat.harga_jual,
      jumlah:     qty,
      dosis:      activeDosis,
      rasa:       activeRasa,
      subtotal:   qty * +activeObat.harga_jual
    });
  }

  showToast(`+${qty} ${activeObat.nama_obat} (${activeDosis}) masuk keranjang`, 'success');
  closeCalcModal();
  renderCart();
}

// ── Shopping Cart Logic ───────────────────────────────────────
function renderCart() {
  const countBadge  = document.getElementById('cartCountBadge');
  const emptyState  = document.getElementById('emptyCartState');
  const cartContent = document.getElementById('cartContent');
  const listEl      = document.getElementById('cartItemsList');

  const totalItems = cart.reduce((sum, c) => sum + c.jumlah, 0);
  countBadge.textContent = `${totalItems} Item`;

  if (cart.length === 0) {
    emptyState.style.display  = 'block';
    cartContent.style.display = 'none';
    return;
  }

  emptyState.style.display  = 'none';
  cartContent.style.display = 'block';

  let grandTotal = 0;

  listEl.innerHTML = cart.map((c, index) => {
    grandTotal += c.subtotal;
    return `
      <div style="background:var(--bg-app); border:1px solid var(--border); border-radius:var(--radius-sm); padding:0.65rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
        <div style="flex:1; min-width:0;">
          <div style="font-weight:700; font-size:0.85rem; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${c.nama_obat}</div>
          <div style="font-size:0.72rem; color:var(--text-muted);">${c.dosis} · Rasa ${c.rasa}</div>
          <div style="font-size:0.825rem; font-weight:800; color:var(--primary);" class="num-tabular">${formatRupiah(c.subtotal)}</div>
        </div>

        <div class="qty-stepper" style="transform:scale(0.85); transform-origin:right center;">
          <button class="qty-btn" onclick="updateCartQty(${index}, -1)"><i class="bi bi-dash"></i></button>
          <input type="number" class="qty-input" value="${c.jumlah}" readonly style="width:38px;">
          <button class="qty-btn" onclick="updateCartQty(${index}, 1)"><i class="bi bi-plus"></i></button>
        </div>

        <button class="btn btn-sm btn-danger" style="padding:0.25rem 0.4rem;" onclick="removeFromCart(${index})" title="Hapus"><i class="bi bi-trash"></i></button>
      </div>
    `;
  }).join('');

  document.getElementById('grandTotalDisplay').textContent = formatRupiah(grandTotal);
}

function updateCartQty(index, delta) {
  if (!cart[index]) return;
  const item = cart[index];
  const obat = allObat.find(o => o.obat_id === item.obat_id);
  const max  = obat ? +obat.stok : 999;

  const newQty = item.jumlah + delta;
  if (newQty <= 0) {
    removeFromCart(index);
    return;
  }
  if (newQty > max) {
    showToast(`Stok ${item.nama_obat} maksimal ${max}`, 'error');
    return;
  }

  item.jumlah   = newQty;
  item.subtotal = newQty * item.harga_jual;
  renderCart();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  renderCart();
}

async function checkoutCart() {
  if (cart.length === 0) {
    showToast('Keranjang belanja masih kosong', 'error');
    return;
  }

  const metode = document.getElementById('metodeSelect').value;
  const btn    = document.getElementById('checkoutBtn');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Memproses Pembayaran...';

  const payload = {
    metode_pembayaran: metode,
    items: cart.map(c => ({
      obat_id: c.obat_id,
      jumlah:  c.jumlah,
      dosis:   c.dosis,
      rasa:    c.rasa,
    }))
  };

  const res = await apiCall('api/order.php', {
    method: 'POST',
    body: JSON.stringify(payload)
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Bayar Sekarang';

  if (res && res.success) {
    showToast(res.message, 'success');
    cart = [];
    renderCart();
    loadObat();
  } else {
    showToast(res?.message || 'Gagal memproses pembayaran', 'error');
  }
}

// Event Listeners for Filters
document.querySelectorAll('[data-golongan]').forEach(b => b.addEventListener('click', e => {
  activeGolongan = e.target.dataset.golongan;
  document.querySelectorAll('[data-golongan]').forEach(x => x.classList.remove('active'));
  e.target.classList.add('active');
  renderGrid(filterObat());
}));

document.querySelectorAll('[data-stok]').forEach(b => b.addEventListener('click', e => {
  activeStok = e.target.dataset.stok;
  document.querySelectorAll('[data-stok]').forEach(x => x.classList.remove('active'));
  e.target.classList.add('active');
  renderGrid(filterObat());
}));

let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadObat, 300);
});

document.querySelectorAll('#kategoriFilter .filter-chip').forEach(b => b.addEventListener('click', e => {
  activeKategori = e.target.dataset.val;
  document.querySelectorAll('#kategoriFilter .filter-chip').forEach(x => x.classList.remove('active'));
  e.target.classList.add('active');
  renderGrid(filterObat());
}));
</script>

<?php include 'includes/footer.php'; ?>
