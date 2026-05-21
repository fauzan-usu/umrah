<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'sales') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Statistik Sales
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM jamaah WHERE sales_id = $user_id");
$stats['jamaah_saya'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(jumlah_bayar), 0) as total FROM pembayaran WHERE sales_id = $user_id");
$stats['penjualan_saya'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM pembayaran WHERE sales_id = $user_id AND DATE(created_at) = CURDATE()");
$stats['transaksi_hari_ini'] = $result->fetch_assoc()['total'];

// Jamaah saya
$jamaah = $conn->query("
    SELECT j.*, p.nama_paket, p.kode_paket,
    COALESCE((SELECT SUM(jumlah_bayar) FROM pembayaran WHERE jamaah_id = j.id), 0) as total_bayar
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    WHERE j.sales_id = $user_id OR j.sales_id IS NULL
    ORDER BY j.created_at DESC
");

// Pembayaran saya
$pembayaran = $conn->query("
    SELECT pb.*, j.nama_lengkap, j.kode_jamaah
    FROM pembayaran pb 
    LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
    WHERE pb.sales_id = $user_id 
    ORDER BY pb.created_at DESC LIMIT 10
");

$pakets = $conn->query("SELECT id, nama_paket, kode_paket, harga FROM paket_umrah WHERE status = 'aktif' ORDER BY nama_paket");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Sales - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #1a5f2a 0%, #0d3d16 100%); color: white; position: fixed; width: 260px; }
        .sidebar-brand { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #f39c12; }
        .main-content { margin-left: 260px; padding: 20px; }
        .topbar { background: white; padding: 15px 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .btn-success { background: linear-gradient(135deg, #1a5f2a, #2e8b57); border: none; }
        .card-table { background: white; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .progress-thin { height: 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-kaaba fa-2x mb-2"></i>
            <h5>Alfadh Umrah</h5>
            <small>Sales Panel</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a href="jamaah.php" class="nav-link"><i class="fas fa-users me-2"></i> Jamaah Saya</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Buat Kwitansi</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Dashboard Sales</h5>
                <small class="text-muted">Selamat datang, <strong><?= $user_nama ?></strong></small>
            </div>
            <div><small class="text-muted"><?= date('l, d F Y') ?></small></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white rounded-circle p-3 me-3"><i class="fas fa-users fa-lg"></i></div>
                        <div><h3 class="mb-0"><?= $stats['jamaah_saya'] ?></h3><small class="text-muted">Jamaah Saya</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle p-3 me-3"><i class="fas fa-wallet fa-lg"></i></div>
                        <div><h3 class="mb-0"><?= rupiah($stats['penjualan_saya']) ?></h3><small class="text-muted">Total Penjualan</small></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning text-white rounded-circle p-3 me-3"><i class="fas fa-receipt fa-lg"></i></div>
                        <div><h3 class="mb-0"><?= $stats['transaksi_hari_ini'] ?></h3><small class="text-muted">Transaksi Hari Ini</small></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-7">
                <div class="card-table">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-success"></i>Jamaah Saya</h6>
                        <a href="jamaah.php" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                    </div>
                    <div class="table-responsive p-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>Kode</th><th>Nama</th><th>Paket</th><th>Progress</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php while($j = $jamaah->fetch_assoc()): 
                                    $sisa = $j['harga_paket'] - $j['total_bayar'];
                                    $progress = $j['harga_paket'] > 0 ? ($j['total_bayar'] / $j['harga_paket'] * 100) : 0;
                                ?>
                                <tr>
                                    <td><strong><?= $j['kode_jamaah'] ?></strong></td>
                                    <td><?= $j['nama_lengkap'] ?></td>
                                    <td><?= $j['kode_paket'] ?? '-' ?></td>
                                    <td style="min-width: 100px;">
                                        <div class="progress progress-thin">
                                            <div class="progress-bar <?= $progress >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= min($progress, 100) ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= round($progress) ?>%</small>
                                    </td>
                                    <td>
                                        <?php if($j['metode_pembayaran'] == 'lunas'): ?>
                                            <span class="badge bg-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Bertahap</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card-table">
                    <div class="p-4 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-receipt me-2 text-success"></i>Pembayaran Terbaru</h6>
                    </div>
                    <div class="p-3">
                        <?php while($p = $pembayaran->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 mb-2 bg-light rounded">
                            <div>
                                <div class="fw-bold"><?= $p['kwitansi_no'] ?></div>
                                <small class="text-muted"><?= $p['nama_lengkap'] ?> - <?= tanggal_indo($p['tanggal_bayar']) ?></small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success"><?= rupiah($p['jumlah_bayar']) ?></div>
                                <small class="badge bg-info"><?= ucfirst($p['jenis_pembayaran']) ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
