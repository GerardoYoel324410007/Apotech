<?php
/**
 * File alerts.php - Untuk menampilkan notifikasi
 */
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show">';
    echo $_SESSION['success'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show">';
    echo $_SESSION['error'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['warning'])) {
    echo '<div class="alert alert-warning alert-dismissible fade show">';
    echo $_SESSION['warning'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['warning']);
}
?>