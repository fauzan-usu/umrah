<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'admin') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// CRUD Jamaah
if (isset($_POST['simpan'])) {
    $id = $_POST['id'];
    $kode = $_POST['kode_jamaah'];
    $nama = $_POST['nama_lengkap'];
    $jk = $_POST['jenis_kelamin'];
    $tempat = $_POST['tempat_lahir'];
    $tgl = $_POST['tanggal_lahir'];
    $ktp = $_POST['no_ktp'];
    $passport = $_POST['no_passport'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['no_hp'];
    $email = $_POST['email'];
    $paket_id = $_POST['paket_id'];
    $sales_id = $_POST['sales_id'];
    $metode = $_POST['metode_pembayaran'];

    $hpaket = $conn->query("SELECT harga FROM paket_umrah WHERE id = $paket_id")->fetch_assoc()['harga'] ?? 0;

    if ($id) {
        $stmt = $conn->prepare("UPDATE jamaah SET kode_jamaah=?, nama_lengkap=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, no_ktp=?, no_passport=?, alamat=?, no_hp=?, email=?, paket_id=?, harga_paket=?, sales_id=?, metode_pembayaran=? WHERE id=?");
        $stmt->bind_param("ssssssssssidiisi", $kode, $nama, $jk, $tempat, $tgl, $ktp, $passport, $alamat, $hp, $email, $paket_id, $hpaket, $sales_id, $metode, $id);
        logAktivitas($conn, $user_id, 'Update Jamaah', "Update jamaah: $nama");
    } else {
        $stmt = $conn->prepare("INSERT INTO jamaah (kode_jamaah, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, no_ktp, no_passport, alamat, no_hp, email, paket_id, harga_paket, sales_id, metode_pembayaran) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssidii", $kode, $nama, $jk, $tempat, $tgl, $ktp, $passport, $alamat, $hp, $email, $paket_id, $hpaket, $sales_id, $metode);
        logAktivitas($conn, $user_id, 'Tambah Jamaah', "Tambah jamaah: $nama");
    }
    $stmt->execute();
    header("Location: jamaah.php?msg=success");
    exit();
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM jamaah WHERE id = $id");
    logAktivitas($conn, $user_id, 'Hapus Jamaah', "Hapus jamaah ID: $id");
    header("Location: jamaah.php?msg=deleted");
    exit();
}

$jamaah = $conn->query("
    SELECT j.*, p.nama_paket, p.kode_paket, u.nama as nama_sales,
    COALESCE((SELECT SUM(jumlah_bayar) FROM pembayaran WHERE jamaah_id = j.id), 0) as total_bayar
    FROM jamaah j 
    LEFT JOIN paket_umrah p ON j.paket_id = p.id 
    LEFT JOIN users u ON j.sales_id = u.id 
    ORDER BY j.created_at DESC
");

$pakets = $conn->query("SELECT id, nama_paket, kode_paket FROM paket_umrah WHERE status = 'aktif' ORDER BY nama_paket");
$sales = $conn->query("SELECT id, nama FROM users WHERE role = 'sales' AND status = 'aktif' ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Jamaah - <?= APP_NAME ?></title>
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
        .progress-thin { height: 6px; border-radius: 3px; }
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
            <a href="jamaah.php" class="nav-link active"><i class="fas fa-users me-2"></i> Data Jamaah</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Pembayaran</a>
            <a href="laporan.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> Laporan</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-cog me-2"></i> Manajemen Sales</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Data Jamaah</h5></div>
            <div><small class="text-muted"><?= date('l, d F Y') ?></small></div>
        </div>

        <?php if($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>Operasi berhasil!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card-table">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-success"></i>Daftar Jamaah</h6>
                <button class="btn btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalJamaah">
                    <i class="fas fa-plus me-2"></i>Tambah Jamaah
                </button>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th><th>Nama</th><th>Paket</th><th>Harga</th>
                            <th>Terbayar</th><th>Sisa</th><th>Progress</th><th>Metode</th><th>Sales</th><th>Aksi</th>
                        </tr>
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
                            <td><?= $j['nama_sales'] ?? '-' ?></td>
                            <td>
                                <a href="pembayaran.php?jamaah_id=<?= $j['id'] ?>" class="btn btn-sm btn-success" title="Bayar"><i class="fas fa-money-bill"></i></a>
                                <a href="?hapus=<?= $j['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus jamaah ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalJamaah" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Form Jamaah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="jamaah_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Jamaah</label>
                                <input type="text" name="kode_jamaah" id="kode_jamaah" class="form-control" placeholder="JMH-XXX" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. KTP</label>
                                <input type="text" name="no_ktp" id="no_ktp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Passport</label>
                                <input type="text" name="no_passport" id="no_passport" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP</label>
                                <input type="text" name="no_hp" id="no_hp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Paket Umrah</label>
                                <select name="paket_id" id="paket_id" class="form-select" required>
                                    <option value="">Pilih Paket</option>
                                    <?php 
                                    $pakets->data_seek(0);
                                    while($p = $pakets->fetch_assoc()): 
                                    ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['kode_paket'] ?> - <?= $p['nama_paket'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sales</label>
                                <select name="sales_id" id="sales_id" class="form-select">
                                    <option value="">Pilih Sales</option>
                                    <?php 
                                    $sales->data_seek(0);
                                    while($s = $sales->fetch_assoc()): 
                                    ?>
                                    <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="metode_pembayaran" id="metode_pembayaran" class="form-select">
                                    <option value="bertahap">Bertahap</option>
                                    <option value="lunas">Lunas</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" id="alamat" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-save me-2"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
