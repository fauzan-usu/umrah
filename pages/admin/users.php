<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

if ($user_role != 'admin') {
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// CRUD Sales
if (isset($_POST['simpan'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $no_hp = $_POST['no_hp'];
    $email = $_POST['email'];
    $status = $_POST['status'];

    if ($id) {
        if ($password) {
            $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, no_hp=?, email=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssi", $nama, $username, $password, $no_hp, $email, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, no_hp=?, email=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $nama, $username, $no_hp, $email, $status, $id);
        }
        logAktivitas($conn, $user_id, 'Update Sales', "Update sales: $nama");
    } else {
        $pass = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role, no_hp, email, status) VALUES (?, ?, ?, 'sales', ?, ?, ?)");
        $stmt->bind_param("ssssss", $nama, $username, $pass, $no_hp, $email, $status);
        logAktivitas($conn, $user_id, 'Tambah Sales', "Tambah sales: $nama");
    }
    $stmt->execute();
    header("Location: users.php?msg=success");
    exit();
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'sales'");
    logAktivitas($conn, $user_id, 'Hapus Sales', "Hapus sales ID: $id");
    header("Location: users.php?msg=deleted");
    exit();
}

$users = $conn->query("
    SELECT u.*, 
    COUNT(DISTINCT j.id) as total_jamaah,
    COALESCE((SELECT SUM(pb.jumlah_bayar) FROM pembayaran pb LEFT JOIN jamaah j2 ON pb.jamaah_id = j2.id WHERE j2.sales_id = u.id), 0) as total_penjualan
    FROM users u 
    LEFT JOIN jamaah j ON u.id = j.sales_id
    WHERE u.role = 'sales'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Sales - <?= APP_NAME ?></title>
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
            <a href="paket.php" class="nav-link"><i class="fas fa-box-open me-2"></i> Paket Umrah</a>
            <a href="jamaah.php" class="nav-link"><i class="fas fa-users me-2"></i> Data Jamaah</a>
            <a href="pembayaran.php" class="nav-link"><i class="fas fa-money-bill-wave me-2"></i> Pembayaran</a>
            <a href="laporan.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> Laporan</a>
            <a href="users.php" class="nav-link active"><i class="fas fa-user-cog me-2"></i> Manajemen Sales</a>
            <a href="../../auth.php?logout=1" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <div class="topbar d-flex justify-content-between align-items-center">
            <div><h5 class="mb-0">Manajemen Sales</h5></div>
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
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-tie me-2 text-success"></i>Daftar Sales</h6>
                <button class="btn btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalSales">
                    <i class="fas fa-plus me-2"></i>Tambah Sales
                </button>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>Nama</th><th>Username</th><th>No. HP</th><th>Email</th><th>Jamaah</th><th>Penjualan</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $u['nama'] ?></strong></td>
                            <td><?= $u['username'] ?></td>
                            <td><?= $u['no_hp'] ?></td>
                            <td><?= $u['email'] ?></td>
                            <td><?= $u['total_jamaah'] ?> orang</td>
                            <td class="text-success fw-bold"><?= rupiah($u['total_penjualan']) ?></td>
                            <td>
                                <?php if($u['status']=='aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editSales(<?= $u['id'] ?>, '<?= addslashes($u['nama']) ?>', '<?= $u['username'] ?>', '<?= $u['no_hp'] ?>', '<?= $u['email'] ?>', '<?= $u['status'] ?>')"><i class="fas fa-edit"></i></button>
                                <a href="?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus sales ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSales" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Form Sales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="sales_id">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" id="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" id="no_hp" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
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
        function editSales(id, nama, username, no_hp, email, status) {
            document.getElementById('sales_id').value = id;
            document.getElementById('nama').value = nama;
            document.getElementById('username').value = username;
            document.getElementById('no_hp').value = no_hp;
            document.getElementById('email').value = email;
            document.getElementById('status').value = status;
            document.getElementById('password').value = '';
            new bootstrap.Modal(document.getElementById('modalSales')).show();
        }
    </script>
</body>
</html>
