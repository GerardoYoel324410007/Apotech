<?php
require_once 'config.php';

/**
 * Mendapatkan daftar obat yang sudah kadaluarsa
 */
function getExpiredDrugs() {
    global $conn;

    $stmt = $conn->prepare("
        SELECT * FROM obat
        WHERE tanggal_kadaluarsa <= CURDATE()
        ORDER BY tanggal_kadaluarsa ASC
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


/**
 * Mendapatkan total penjualan hari ini
 */
function getTodaySales() {
    global $conn;
    
    $today = date('Y-m-d');
    $sql = "SELECT SUM(total_harga) as total 
            FROM transaksi 
            WHERE DATE(tanggal_transaksi) = '$today'";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}


/**
 * Mendapatkan daftar supplier dari database
 */
/**
 * Mendapatkan daftar obat yang tersedia di stok
 * @return array Daftar obat dengan stok > 0
 */
function getDrugsInStock() {
    global $conn;
    
    $sql = "SELECT obat_id, nama_obat, harga_jual, stok, satuan 
            FROM obat 
            WHERE stok > 0
            ORDER BY nama_obat ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("Error getting drugs: " . $conn->error);
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
function getSuppliers() {
    global $conn;
    
    $sql = "SELECT * FROM supplier ORDER BY nama_supplier ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("Error getting suppliers: " . $conn->error);
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Format tanggal Indonesia
function indoDate($date) {
    $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', strtotime($date));
    $m = date('n', strtotime($date)) - 1;
    $y = date('Y', strtotime($date));
    return "$d {$bulan[$m]} $y";
}

// Hitung total stok hampir habis
function getLowStockCount() {
    global $conn;
    $sql = "SELECT COUNT(*) as count FROM obat WHERE stok < 10";
    $result = $conn->query($sql);
    return $result->fetch_assoc()['count'];
}

// Get daftar obat
function getDrugs($search = '') {
    global $conn;

    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM obat WHERE nama_obat LIKE ? ORDER BY nama_obat");
        $like = '%' . $search . '%';
        $stmt->bind_param("s", $like);
    } else {
        $stmt = $conn->prepare("SELECT * FROM obat ORDER BY nama_obat");
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}



?>