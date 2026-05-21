<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'admin') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Proses CRUD
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_paket'];
    $kode = $_POST['kode_paket'];
    $harga = str_replace('.', '', $_POST['harga']);
    $durasi = $_POST['durasi_hari'];
    $keberangkatan = $_POST['keberangkatan'];
    $kamar = $_POST['kamar'];
    $fasilitas = $_POST['fasilitas'];
    $kuota = $_POST['kuota'];

    if ($_POST['id']) {
        $stmt = $conn->prepare("UPDATE paket_umrah SET nama_paket=?, kode_paket=?, harga=?, durasi_hari=?, keberangkatan=?, kamar=?, fasilitas=?, kuota=? WHERE id=?");
        $stmt->bind_param("ssdiissii", $nama, $kode, $harga, $durasi, $keberangkatan, $kamar, $fasilitas, $kuota, $_POST['id']);
        logAktivitas($conn, $user_id, 'Update Paket', "Update paket: $nama");
    } else {
        $stmt = $conn->prepare("INSERT INTO paket_umrah (nama_paket, kode_paket, harga, durasi_hari, keberangkatan, kamar, fasilitas, kuota) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssdiissi", $nama, $kode, $harga, $durasi, $keberangkatan, $kamar, $fasilitas, $kuota);
        logAktivitas($conn, $user_id, 'Tambah Paket', "Tambah paket: $nama");
    }
    $stmt->execute();
    header("Location: paket.php?msg=success");
    exit();
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM paket_umrah WHERE id = $id");
    logAktivitas($conn, $user_id, 'Hapus Paket', "Hapus paket ID: $id");
    header("Location: paket.php?msg=deleted");
    exit();
}

if (isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $conn->query("UPDATE paket_umrah SET status = '$status' WHERE id = $id");
    header("Location: paket.php?msg=status");
    exit();
}

// Data
$paket = $conn->query("SELECT p.*, COUNT(j.id) as terisi FROM paket_umrah p LEFT JOIN jamaah j ON p.id = j.paket_id GROUP BY p.id ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Paket Umrah - <?= APP_NAME ?></title>
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
            <a href="paket.php" class="nav-link active"><i class="fas fa-box-open me-2"></i> Paket Umrah</a>
            <a href="jamaah.php" class="nav-link"><i class="fas fa-users me-2"></i> Data Jamaah</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Pembayaran</a>
            <a href="laporan.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> Laporan</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-cog me-2"></i> Manajemen Sales</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Manajemen Paket Umrah</h5></div>
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
                <h6 class="mb-0 fw-bold"><i class="fas fa-box-open me-2 text-success"></i>Daftar Paket Umrah</h6>
                <button class="btn btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalPaket">
                    <i class="fas fa-plus me-2"></i>Tambah Paket
                </button>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th><th>Nama Paket</th><th>Harga</th><th>Durasi</th>
                            <th>Keberangkatan</th><th>Kamar</th><th>Kuota</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $paket->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $p['kode_paket'] ?></strong></td>
                            <td><?= $p['nama_paket'] ?></td>
                            <td class="text-success fw-bold"><?= rupiah($p['harga']) ?></td>
                            <td><?= $p['durasi_hari'] ?> hari</td>
                            <td><?= $p['keberangkatan'] ? tanggal_indo($p['keberangkatan']) : '-' ?></td>
                            <td><?= ucfirst($p['kamar']) ?></td>
                            <td><?= $p['terisi'] ?>/<?= $p['kuota'] ?></td>
                            <td>
                                <?php if($p['status']=='aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php elseif($p['status']=='selesai'): ?>
                                    <span class="badge bg-secondary">Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editPaket(<?= $p['id'] ?>, '<?= addslashes($p['nama_paket']) ?>', '<?= $p['kode_paket'] ?>', <?= $p['harga'] ?>, <?= $p['durasi_hari'] ?>, '<?= $p['keberangkatan'] ?>', '<?= $p['kamar'] ?>', '<?= addslashes($p['fasilitas']) ?>', <?= $p['kuota'] ?>)"><i class="fas fa-edit"></i></button>
                                <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalPaket" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-box-open me-2"></i>Form Paket Umrah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="paket_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Paket</label>
                                <input type="text" name="nama_paket" id="nama_paket" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Paket</label>
                                <input type="text" name="kode_paket" id="kode_paket" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" name="harga" id="harga" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Durasi (Hari)</label>
                                <input type="number" name="durasi_hari" id="durasi_hari" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Keberangkatan</label>
                                <input type="date" name="keberangkatan" id="keberangkatan" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Kamar</label>
                                <select name="kamar" id="kamar" class="form-select">
                                    <option value="quad">Quad (4 orang)</option>
                                    <option value="triple">Triple (3 orang)</option>
                                    <option value="double">Double (2 orang)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kuota</label>
                                <input type="number" name="kuota" id="kuota" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Fasilitas</label>
                                <textarea name="fasilitas" id="fasilitas" class="form-control" rows="3"></textarea>
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
    <script>
        function editPaket(id, nama, kode, harga, durasi, keberangkatan, kamar, fasilitas, kuota) {
            document.getElementById('paket_id').value = id;
            document.getElementById('nama_paket').value = nama;
            document.getElementById('kode_paket').value = kode;
            document.getElementById('harga').value = harga;
            document.getElementById('durasi_hari').value = durasi;
            document.getElementById('keberangkatan').value = keberangkatan;
            document.getElementById('kamar').value = kamar;
            document.getElementById('fasilitas').value = fasilitas;
            document.getElementById('kuota').value = kuota;
            new bootstrap.Modal(document.getElementById('modalPaket')).show();
        }
    </script>
</body>
</html>
