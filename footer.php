  </main><!-- /.main-wrapper -->
</div><!-- /.app-layout -->

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Global JS utilities -->
<script>
// ── Toast Notification (Green/Teal feedback) ────────────────
function showToast(message, type = 'success') {
  const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="bi ${icons[type] || icons.info} toast-icon"></i>
    <span class="toast-msg">${message}</span>
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.25s ease';
    setTimeout(() => toast.remove(), 250);
  }, 3500);
}

// ── Format Rupiah ───────────────────────────────────────────
function formatRupiah(amount) {
  return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
}

// ── API Helper ──────────────────────────────────────────────
async function apiCall(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...options.headers },
      ...options
    });
    return await res.json();
  } catch (err) {
    showToast('Gagal terhubung ke server', 'error');
    return null;
  }
}
</script>

<?= $extraScripts ?? '' ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>