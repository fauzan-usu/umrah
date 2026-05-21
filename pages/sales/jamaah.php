<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'sales') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$jamaah = $conn->query("
    SELECT j.*, p.nama_paket, p.kode_paket,
    COALESCE((SELECT SUM(jumlah_bayar) FROM pembayaran WHERE jamaah_id = j.id), 0) as total_bayar
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    WHERE j.sales_id = $user_id OR j.sales_id IS NULL
    ORDER BY j.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jamaah Saya - <?= APP_NAME ?></title>
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
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a href="jamaah.php" class="nav-link active"><i class="fas fa-users me-2"></i> Jamaah Saya</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Buat Kwitansi</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Jamaah Saya</h5></div>
            <div><small class="text-muted"><?= date('l, d F Y') ?></small></div>
        </div>

        <div class="card-table">
            <div class="p-4 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-success"></i>Daftar Jamaah</h6>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Kode</th><th>Nama</th><th>Paket</th><th>Harga</th><th>Terbayar</th><th>Sisa</th><th>Progress</th><th>Status</th><th>Aksi</th></tr>
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
                            <td><?= rupiah($j['harga_paket']) ?></td>
                            <td class="text-success"><?= rupiah($j['total_bayar']) ?></td>
                            <td class="<?= $sisa > 0 ? 'text-danger' : 'text-success' ?>"><?= rupiah($sisa) ?></td>
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
                            <td>
                                <a href="pembayaran.php?jamaah_id=<?= $j['id'] ?>" class="btn btn-sm btn-success" title="Buat Kwitansi"><i class="fas fa-money-bill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
