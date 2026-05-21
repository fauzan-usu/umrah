<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_nama = $_SESSION['user_nama'];
$user_username = $_SESSION['user_username'];

// Cek status user masih aktif
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0 || $result->fetch_assoc()['status'] != 'aktif') {
    session_destroy();
    header("Location: " . BASE_URL . "index.php?error=account_disabled");
    exit();
}
?>
