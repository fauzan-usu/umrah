<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session_check.php';

if ($user_role == 'admin') {
    header("Location: pages/admin/dashboard.php");
} else {
    header("Location: pages/sales/dashboard.php");
}
exit();
?>
