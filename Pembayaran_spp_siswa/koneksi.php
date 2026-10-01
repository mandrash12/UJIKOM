<?php
/**
 * Konfigurasi Database dan Helper Global
 * Sistem Pembayaran SPP Siswa
 * Mendukung koneksi PDO ($pdo) dan MySQLi ($koneksi)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pembayaran_spp_siswa';

$pdo = null;
$koneksi = null;
$db_error = null;

// Koneksi PDO
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// Koneksi MySQLi (Kompatibilitas)
try {
    $koneksi = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$koneksi->connect_error) {
        $koneksi->set_charset('utf8mb4');
    }
} catch (Exception $e) {
    // Biarkan jika gagal, PDO tetap aktif
}

/**
 * Format mata uang Rupiah
 */
function rupiah($nominal) {
    return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
}

/**
 * Format Tanggal Indonesia
 */
function tgl_indo($datetime, $with_time = false) {
    if (!$datetime || $datetime === '0000-00-00' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);

    $hasil = "{$hari} {$bulan} {$tahun}";
    if ($with_time) {
        $hasil .= ' ' . date('H:i', $timestamp);
    }
    return $hasil;
}

/**
 * Flash Message (Notifikasi satu kali tampil)
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Autentikasi & Autorisasi
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) || isset($_SESSION['siswa_nisn']);
}

function is_admin() {
    return isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin';
}

function is_petugas() {
    return isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], ['admin', 'petugas']);
}

function is_siswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'siswa';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak! Halaman ini hanya untuk Administrator.');
        header('Location: index.php');
        exit;
    }
}

function require_petugas() {
    require_login();
    if (!is_petugas()) {
        set_flash('danger', 'Akses ditolak! Anda tidak memiliki izin untuk tindakan ini.');
        header('Location: index.php');
        exit;
    }
}
?>
