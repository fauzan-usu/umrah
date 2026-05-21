<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Ganti jika ada password MySQL
define('DB_NAME', 'alfadh_umrah');

// Konfigurasi Aplikasi
define('APP_NAME', 'PT. Alfadh Berkah Haramain');
define('APP_SHORT', 'Alfadh Umrah');
define('BASE_URL', 'http://localhost/alfadh-umrah-app/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Koneksi Database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Session
session_start();

// Fungsi Helper
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

function generateKwitansi($conn) {
    $prefix = 'KWT-' . date('Ymd') . '-';
    $query = "SELECT COUNT(*) as total FROM pembayaran WHERE kwitansi_no LIKE '$prefix%'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $no = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . $no;
}

function logAktivitas($conn, $user_id, $aktivitas, $detail = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, detail, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $aktivitas, $detail, $ip);
    $stmt->execute();
}
?>
