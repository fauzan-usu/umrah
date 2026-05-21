<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'sales') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$filter_jamaah = isset($_GET['jamaah_id']) ? $_GET['jamaah_id'] : '';

// Proses tambah pembayaran
if (isset($_POST['simpan'])) {
    $jamaah_id = $_POST['jamaah_id'];
    $tanggal = $_POST['tanggal_bayar'];
    $jumlah = str_replace('.', '', $_POST['jumlah_bayar']);
    $jenis = $_POST['jenis_pembayaran'];
    $cicilan = $_POST['cicilan_ke'] ?? 0;
    $metode = $_POST['metode_bayar'];
    $ket = $_POST['keterangan'];
    $kwitansi = generateKwitansi($conn);

    $stmt = $conn->prepare("INSERT INTO pembayaran (kwitansi_no, jamaah_id, tanggal_bayar, jumlah_bayar, jenis_pembayaran, cicilan_ke, metode_bayar, keterangan, sales_id) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sisdisssi", $kwitansi, $jamaah_id, $tanggal, $jumlah, $jenis, $cicilan, $metode, $ket, $user_id);
    $stmt->execute();

    logAktivitas($conn, $user_id, 'Sales Buat Kwitansi', "Kwitansi: $kwitansi, Jumlah: $jumlah");

    // Update status jamaah jika lunas
    $total_bayar = $conn->query("SELECT SUM(jumlah_bayar) as total FROM pembayaran WHERE jamaah_id = $jamaah_id")->fetch_assoc()['total'];
    $harga_paket = $conn->query("SELECT harga_paket FROM jamaah WHERE id = $jamaah_id")->fetch_assoc()['harga_paket'];
    if ($total_bayar >= $harga_paket) {
        $conn->query("UPDATE jamaah SET metode_pembayaran = 'lunas' WHERE id = $jamaah_id");
    }

    header("Location: pembayaran.php?msg=success&kwitansi=" . urlencode($kwitansi));
    exit();
}

// Data
$where = $filter_jamaah ? "WHERE pb.jamaah_id = $filter_jamaah" : "WHERE pb.sales_id = $user_id";
$pembayaran = $conn->query("
    SELECT pb.*, j.nama_lengkap, j.kode_jamaah, j.harga_paket
    FROM pembayaran pb 
    LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
    $where
    ORDER BY pb.created_at DESC
");

$jamaahs = $conn->query("
    SELECT j.*, p.nama_paket, 
    COALESCE((SELECT SUM(jumlah_bayar) FROM pembayaran WHERE jamaah_id = j.id), 0) as total_bayar
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    WHERE j.sales_id = $user_id OR j.sales_id IS NULL
    ORDER BY j.nama_lengkap
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Kwitansi - <?= APP_NAME ?></title>
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
        .kwitansi-card { border: 2px dashed #2e8b57; border-radius: 10px; padding: 15px; background: #f8fff8; }
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
            <a href="jamaah.php" class="nav-link"><i class="fas fa-users me-2"></i> Jamaah Saya</a>
            <a href="pembayaran.php" class="nav-link active"><i class="fas fa-money-bill-wave me-2"></i> Buat Kwitansi</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Buat Kwitansi Pembayaran</h5></div>
            <div><small class="text-muted"><?= date('l, d F Y') ?></small></div>
        </div>

        <?php if($msg == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>Kwitansi berhasil dibuat! 
            <a href="../kwitansi/cetak.php?kwitansi=<?= $_GET['kwitansi'] ?>" target="_blank" class="alert-link">Cetak Kwitansi</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-table p-4">
                    <h6 class="mb-3 fw-bold"><i class="fas fa-file-invoice me-2 text-success"></i>Form Kwitansi</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Pilih Jamaah</label>
                            <select name="jamaah_id" id="jamaah_select" class="form-select" required onchange="updateInfo()">
                                <option value="">-- Pilih Jamaah --</option>
                                <?php while($j = $jamaahs->fetch_assoc()): 
                                    $sisa = $j['harga_paket'] - $j['total_bayar'];
                                    $selected = ($filter_jamaah == $j['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $j['id'] ?>" data-harga="<?= $j['harga_paket'] ?>" data-bayar="<?= $j['total_bayar'] ?>" data-sisa="<?= $sisa ?>" <?= $selected ?>>
                                    <?= $j['kode_jamaah'] ?> - <?= $j['nama_lengkap'] ?> (Sisa: <?= rupiah($sisa) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div id="info_jamaah" class="kwitansi-card mb-3 d-none">
                            <small class="text-muted">Harga Paket:</small>
                            <div class="fw-bold text-success" id="info_harga"></div>
                            <small class="text-muted">Total Terbayar:</small>
                            <div class="fw-bold text-primary" id="info_bayar"></div>
                            <small class="text-muted">Sisa Pembayaran:</small>
                            <div class="fw-bold text-danger" id="info_sisa"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Bayar (Rp)</label>
                            <input type="number" name="jumlah_bayar" id="jumlah_bayar" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Pembayaran</label>
                            <select name="jenis_pembayaran" class="form-select">
                                <option value="dp">DP (Uang Muka)</option>
                                <option value="cicilan">Cicilan</option>
                                <option value="pelunasan">Pelunasan</option>
                                <option value="lunas">Lunas</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cicilan Ke-</label>
                            <input type="number" name="cicilan_ke" class="form-control" value="0" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metode Bayar</label>
                            <select name="metode_bayar" class="form-select">
                                <option value="transfer">Transfer Bank</option>
                                <option value="cash">Cash</option>
                                <option value="debit">Kartu Debit</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-success w-100 text-white">
                            <i class="fas fa-file-invoice me-2"></i>Buat Kwitansi
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card-table">
                    <div class="p-4 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-success"></i>Riwayat Kwitansi Saya</h6>
                    </div>
                    <div class="table-responsive p-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>No. Kwitansi</th><th>Tanggal</th><th>Jamaah</th><th>Jenis</th><th>Jumlah</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php while($p = $pembayaran->fetch_assoc()): ?>
                                <tr>
                                    <td><strong class="text-success"><?= $p['kwitansi_no'] ?></strong></td>
                                    <td><?= tanggal_indo($p['tanggal_bayar']) ?></td>
                                    <td><?= $p['nama_lengkap'] ?></td>
                                    <td>
                                        <?php 
                                        $badge = ['dp'=>'bg-info','cicilan'=>'bg-warning','pelunasan'=>'bg-primary','lunas'=>'bg-success'];
                                        echo '<span class="badge ' . ($badge[$p['jenis_pembayaran']] ?? 'bg-secondary') . '">' . ucfirst($p['jenis_pembayaran']) . '</span>';
                                        ?>
                                    </td>
                                    <td class="fw-bold text-success"><?= rupiah($p['jumlah_bayar']) ?></td>
                                    <td>
                                        <a href="../kwitansi/cetak.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-print"></i></a>
                                    </td>
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
        function updateInfo() {
            const select = document.getElementById('jamaah_select');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('info_jamaah');
            if (select.value) {
                info.classList.remove('d-none');
                document.getElementById('info_harga').textContent = 'Rp ' + parseInt(option.dataset.harga).toLocaleString('id-ID');
                document.getElementById('info_bayar').textContent = 'Rp ' + parseInt(option.dataset.bayar).toLocaleString('id-ID');
                document.getElementById('info_sisa').textContent = 'Rp ' + parseInt(option.dataset.sisa).toLocaleString('id-ID');
            } else {
                info.classList.add('d-none');
            }
        }
        updateInfo();
    </script>
</body>
</html>
