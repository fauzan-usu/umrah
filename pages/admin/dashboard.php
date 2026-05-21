<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'admin') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Statistik
$stats = [];

// Total Jamaah
$result = $conn->query("SELECT COUNT(*) as total FROM jamaah");
$stats['total_jamaah'] = $result->fetch_assoc()['total'];

// Total Lunas
$result = $conn->query("SELECT COUNT(*) as total FROM jamaah WHERE metode_pembayaran = 'lunas'");
$stats['jamaah_lunas'] = $result->fetch_assoc()['total'];

// Total Bertahap
$result = $conn->query("SELECT COUNT(*) as total FROM jamaah WHERE metode_pembayaran = 'bertahap'");
$stats['jamaah_bertahap'] = $result->fetch_assoc()['total'];

// Total Pembayaran Masuk
$result = $conn->query("SELECT COALESCE(SUM(jumlah_bayar), 0) as total FROM pembayaran");
$stats['total_pemasukan'] = $result->fetch_assoc()['total'];

// Total Piutang
$result = $conn->query("
    SELECT COALESCE(SUM(j.harga_paket - COALESCE(p.total_bayar, 0)), 0) as piutang 
    FROM jamaah j 
    LEFT JOIN (SELECT jamaah_id, SUM(jumlah_bayar) as total_bayar FROM pembayaran GROUP BY jamaah_id) p 
    ON j.id = p.jamaah_id
");
$stats['total_piutang'] = $result->fetch_assoc()['piutang'];

// Jamaah Terbaru
$jamaah_terbaru = $conn->query("
    SELECT j.*, p.nama_paket, u.nama as nama_sales 
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    LEFT JOIN users u ON j.sales_id = u.id 
    ORDER BY j.created_at DESC LIMIT 5
");

// Pembayaran Terbaru
$pembayaran_terbaru = $conn->query("
    SELECT pb.*, j.nama_lengkap, j.kode_jamaah, u.nama as nama_sales 
    FROM pembayaran pb 
    LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
    LEFT JOIN users u ON pb.sales_id = u.id 
    ORDER BY pb.created_at DESC LIMIT 5
");

// Data per paket untuk chart
$paket_data = $conn->query("
    SELECT p.nama_paket, COUNT(j.id) as jumlah_jamaah, COALESCE(SUM(pb.jumlah_bayar), 0) as total_bayar
    FROM paket_umrah p
    LEFT JOIN jamaah j ON p.id = j.paket_id
    LEFT JOIN pembayaran pb ON j.id = pb.jamaah_id
    WHERE p.status = 'aktif'
    GROUP BY p.id
");

// Data sales performance
$sales_data = $conn->query("
    SELECT u.nama, COUNT(DISTINCT j.id) as jumlah_jamaah, COALESCE(SUM(pb.jumlah_bayar), 0) as total_penjualan
    FROM users u
    LEFT JOIN jamaah j ON u.id = j.sales_id
    LEFT JOIN pembayaran pb ON j.id = pb.jamaah_id
    WHERE u.role = 'sales' AND u.status = 'aktif'
    GROUP BY u.id
    ORDER BY total_penjualan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1a5f2a;
            --secondary: #2e8b57;
            --accent: #f39c12;
            --light: #f8f9fa;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, #0d3d16 100%);
            color: white;
            position: fixed;
            width: 260px;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-brand h5 {
            margin: 0;
            font-weight: 700;
        }
        .sidebar-brand small {
            opacity: 0.7;
            font-size: 0.75rem;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--accent);
        }
        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        .topbar {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: none;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .bg-gradient-success { background: linear-gradient(135deg, #28a745, #20c997); }
        .bg-gradient-info { background: linear-gradient(135deg, #17a2b8, #0dcaf0); }
        .bg-gradient-warning { background: linear-gradient(135deg, #ffc107, #ff9800); }
        .bg-gradient-danger { background: linear-gradient(135deg, #dc3545, #f44336); }
        .bg-gradient-primary { background: linear-gradient(135deg, #1a5f2a, #2e8b57); }
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f2f5;
            padding: 20px 25px;
            font-weight: 600;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-kaaba fa-2x mb-2"></i>
            <h5>Alfadh Umrah</h5>
            <small>Admin Panel</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="paket.php" class="nav-link">
                <i class="fas fa-box-open"></i> Paket Umrah
            </a>
            <a href="jamaah.php" class="nav-link">
                <i class="fas fa-users"></i> Data Jamaah
            </a>
            <a href="pembayaran.php" class="nav-link">
                <i class="fas fa-money-bill-wave"></i> Pembayaran
            </a>
            <a href="laporan.php" class="nav-link">
                <i class="fas fa-file-alt"></i> Laporan
            </a>
            <a href="users.php" class="nav-link">
                <i class="fas fa-user-cog"></i> Manajemen Sales
            </a>
            <div class="mt-auto">
                <a href="../../auth.php?logout=1" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div>
                <h5 class="mb-0">Dashboard</h5>
                <small class="text-muted">Selamat datang, <strong><?= $user_nama ?></strong></small>
            </div>
            <div class="text-end">
                <small class="text-muted"><?= date('l, d F Y') ?></small>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gradient-success text-white">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['total_jamaah'] ?></h3>
                    <p class="text-muted mb-0">Total Jamaah</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gradient-primary text-white">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['jamaah_lunas'] ?></h3>
                    <p class="text-muted mb-0">Jamaah Lunas</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gradient-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="mb-1"><?= $stats['jamaah_bertahap'] ?></h3>
                    <p class="text-muted mb-0">Pembayaran Bertahap</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-gradient-info text-white">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="mb-1"><?= rupiah($stats['total_pemasukan']) ?></h3>
                    <p class="text-muted mb-0">Total Pemasukan</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-7">
                <div class="chart-container">
                    <h6 class="mb-3"><i class="fas fa-chart-bar me-2 text-success"></i>Pemasukan per Paket</h6>
                    <canvas id="paketChart" height="200"></canvas>
                </div>
            </div>
            <div class="col-md-5">
                <div class="chart-container">
                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2 text-success"></i>Status Pembayaran</h6>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Performance -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="chart-container">
                    <h6 class="mb-3"><i class="fas fa-trophy me-2 text-warning"></i>Performa Sales</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Sales</th>
                                    <th>Jumlah Jamaah</th>
                                    <th>Total Penjualan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($s = $sales_data->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $s['nama'] ?></strong></td>
                                    <td><?= $s['jumlah_jamaah'] ?> jamaah</td>
                                    <td class="text-success fw-bold"><?= rupiah($s['total_penjualan']) ?></td>
                                    <td><span class="badge bg-success">Aktif</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-plus me-2 text-success"></i>Jamaah Terbaru</span>
                        <a href="jamaah.php" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Paket</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($j = $jamaah_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $j['kode_jamaah'] ?></strong></td>
                                    <td><?= $j['nama_lengkap'] ?></td>
                                    <td><?= $j['nama_paket'] ?? '-' ?></td>
                                    <td>
                                        <?php if($j['metode_pembayaran'] == 'lunas'): ?>
                                            <span class="badge-status bg-success text-white">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge-status bg-warning text-dark">Bertahap</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-receipt me-2 text-success"></i>Pembayaran Terbaru</span>
                        <a href="pembayaran.php" class="btn btn-sm btn-outline-success">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kwitansi</th>
                                    <th>Jamaah</th>
                                    <th>Jumlah</th>
                                    <th>Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = $pembayaran_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $p['kwitansi_no'] ?></strong></td>
                                    <td><?= $p['nama_lengkap'] ?></td>
                                    <td class="text-success fw-bold"><?= rupiah($p['jumlah_bayar']) ?></td>
                                    <td><?= $p['nama_sales'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Paket Chart
        const paketCtx = document.getElementById('paketChart').getContext('2d');
        new Chart(paketCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $paket_data->data_seek(0);
                    $labels = [];
                    $values = [];
                    while($pd = $paket_data->fetch_assoc()) {
                        $labels[] = "'" . addslashes($pd['nama_paket']) . "'";
                        $values[] = $pd['total_bayar'];
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Total Pembayaran',
                    data: [<?= implode(',', $values) ?>],
                    backgroundColor: 'rgba(46, 139, 87, 0.8)',
                    borderColor: '#1a5f2a',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Lunas', 'Bertahap'],
                datasets: [{
                    data: [<?= $stats['jamaah_lunas'] ?>, <?= $stats['jamaah_bertahap'] ?>],
                    backgroundColor: ['#28a745', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
