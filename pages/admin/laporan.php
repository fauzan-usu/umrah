<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'admin') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Filter
$filter_paket = $_GET['paket'] ?? '';
$filter_sales = $_GET['sales'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_dari = $_GET['dari'] ?? date('Y-m-01');
$filter_sampai = $_GET['sampai'] ?? date('Y-m-d');

$where = "WHERE 1=1";
if ($filter_paket) $where .= " AND j.paket_id = $filter_paket";
if ($filter_sales) $where .= " AND j.sales_id = $filter_sales";
if ($filter_status) $where .= " AND j.metode_pembayaran = '$filter_status'";

// Data
$jamaah = $conn->query("
    SELECT j.*, p.nama_paket, p.kode_paket, u.nama as nama_sales,
    COALESCE((SELECT SUM(jumlah_bayar) FROM pembayaran WHERE jamaah_id = j.id), 0) as total_bayar
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    LEFT JOIN users u ON j.sales_id = u.id 
    $where
    ORDER BY j.created_at DESC
");

$pembayaran = $conn->query("
    SELECT pb.*, j.nama_lengkap, j.kode_jamaah, u.nama as nama_sales
    FROM pembayaran pb 
    LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
    LEFT JOIN users u ON pb.sales_id = u.id 
    WHERE pb.tanggal_bayar BETWEEN '$filter_dari' AND '$filter_sampai'
    ORDER BY pb.tanggal_bayar DESC
");

$total_masuk = $conn->query("SELECT COALESCE(SUM(jumlah_bayar), 0) as total FROM pembayaran WHERE tanggal_bayar BETWEEN '$filter_dari' AND '$filter_sampai'")->fetch_assoc()['total'];

$pakets = $conn->query("SELECT id, nama_paket FROM paket_umrah WHERE status = 'aktif'");
$sales_list = $conn->query("SELECT id, nama FROM users WHERE role = 'sales' AND status = 'aktif'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - <?= APP_NAME ?></title>
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
        .btn-success { background: linear-gradient(135deg, #1a5f2a, #2e8b57); border: none; }
        @media print { .sidebar, .topbar, .no-print { display: none !important; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-kaaba fa-2x mb-2"></i>
            <h5>Alfadh Umrah</h5>
            <small>Admin Panel</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a href="paket.php" class="nav-link"><i class="fas fa-box-open me-2"></i> Paket Umrah</a>
            <a href="jamaah.php" class="nav-link"><i class="fas fa-users me-2"></i> Data Jamaah</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Pembayaran</a>
            <a href="laporan.php" class="nav-link active"><i class="fas fa-file-alt me-2"></i> Laporan</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-cog me-2"></i> Manajemen Sales</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Laporan & Rekapitulasi</h5></div>
            <div><small class="text-muted"><?= date('l, d F Y') ?></small></div>
        </div>

        <div class="card-table p-4 mb-4">
            <form method="GET" class="row g-3 align-items-end no-print">
                <div class="col-md-2">
                    <label class="form-label">Paket</label>
                    <select name="paket" class="form-select">
                        <option value="">Semua</option>
                        <?php while($p = $pakets->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= $filter_paket == $p['id'] ? 'selected' : '' ?>><?= $p['nama_paket'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sales</label>
                    <select name="sales" class="form-select">
                        <option value="">Semua</option>
                        <?php while($s = $sales_list->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" <?= $filter_sales == $s['id'] ? 'selected' : '' ?>><?= $s['nama'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status Bayar</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="lunas" <?= $filter_status=='lunas'?'selected':'' ?>>Lunas</option>
                        <option value="bertahap" <?= $filter_status=='bertahap'?'selected':'' ?>>Bertahap</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" name="dari" class="form-control" value="<?= $filter_dari ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="<?= $filter_sampai ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100 text-white"><i class="fas fa-filter me-2"></i>Filter</button>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="card-table p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold"><i class="fas fa-money-bill-wave me-2 text-success"></i>Ringkasan Keuangan Periode: <?= tanggal_indo($filter_dari) ?> s/d <?= tanggal_indo($filter_sampai) ?></h6>
                        <button onclick="window.print()" class="btn btn-outline-success no-print"><i class="fas fa-print me-2"></i>Cetak</button>
                    </div>
                    <div class="row text-center">
                        <div class="col-md-4 border-end">
                            <h3 class="text-success"><?= rupiah($total_masuk) ?></h3>
                            <p class="text-muted">Total Pemasukan</p>
                        </div>
                        <div class="col-md-4 border-end">
                            <h3 class="text-primary"><?= $jamaah->num_rows ?></h3>
                            <p class="text-muted">Total Jamaah</p>
                        </div>
                        <div class="col-md-4">
                            <h3 class="text-warning"><?= $pembayaran->num_rows ?></h3>
                            <p class="text-muted">Jumlah Transaksi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-table">
            <div class="p-4 border-bottom">
                <h6 class="fw-bold"><i class="fas fa-list me-2 text-success"></i>Detail Pembayaran</h6>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>No</th><th>Tanggal</th><th>Kwitansi</th><th>Jamaah</th><th>Jenis</th><th>Jumlah</th><th>Metode</th><th>Sales</th></tr>
                    </thead>
                    <tbody>
                        <?php $no=1; while($p = $pembayaran->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= tanggal_indo($p['tanggal_bayar']) ?></td>
                            <td><strong class="text-success"><?= $p['kwitansi_no'] ?></strong></td>
                            <td><?= $p['nama_lengkap'] ?></td>
                            <td><?= ucfirst($p['jenis_pembayaran']) ?></td>
                            <td class="fw-bold text-success"><?= rupiah($p['jumlah_bayar']) ?></td>
                            <td><?= ucfirst($p['metode_bayar']) ?></td>
                            <td><?= $p['nama_sales'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">TOTAL:</td>
                            <td colspan="3" class="fw-bold text-success"><?= rupiah($total_masuk) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
