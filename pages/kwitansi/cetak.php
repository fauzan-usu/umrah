<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session_check.php';

$id = $_GET['id'] ?? 0;
$kwitansi_no = $_GET['kwitansi'] ?? '';

if ($id) {
    $result = $conn->query("
        SELECT pb.*, j.nama_lengkap, j.kode_jamaah, j.alamat, j.no_hp, j.no_ktp, 
        p.nama_paket, p.kode_paket, p.harga as harga_paket,
        u.nama as nama_sales, u.no_hp as hp_sales
        FROM pembayaran pb 
        LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
        LEFT JOIN paket_umrah p ON j.paket_id = p.id 
        LEFT JOIN users u ON pb.sales_id = u.id 
        WHERE pb.id = $id
    ");
} else if ($kwitansi_no) {
    $result = $conn->query("
        SELECT pb.*, j.nama_lengkap, j.kode_jamaah, j.alamat, j.no_hp, j.no_ktp, 
        p.nama_paket, p.kode_paket, p.harga as harga_paket,
        u.nama as nama_sales, u.no_hp as hp_sales
        FROM pembayaran pb 
        LEFT JOIN jamaah j ON pb.jamaah_id = j.id 
        LEFT JOIN paket_umrah p ON j.paket_id = p.id 
        LEFT JOIN users u ON pb.sales_id = u.id 
        WHERE pb.kwitansi_no = '$kwitansi_no'
    ");
}

if (!$result || $result->num_rows == 0) {
    die('Kwitansi tidak ditemukan');
}

$data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi <?= $data['kwitansi_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body { font-family: 'Times New Roman', serif; background: #f5f5f5; }
        .kwitansi-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border: 3px solid #1a5f2a;
            position: relative;
        }
        .kwitansi-header {
            text-align: center;
            border-bottom: 3px double #1a5f2a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .kwitansi-header h3 {
            color: #1a5f2a;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }
        .kwitansi-header p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .kwitansi-no {
            background: #1a5f2a;
            color: white;
            padding: 8px 20px;
            display: inline-block;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            width: 180px;
            font-weight: bold;
        }
        .info-value {
            flex: 1;
            border-bottom: 1px dotted #999;
            padding-left: 10px;
        }
        .amount-box {
            border: 2px solid #1a5f2a;
            padding: 15px 25px;
            margin: 25px 0;
            text-align: center;
            background: #f8fff8;
        }
        .amount-box h2 {
            color: #1a5f2a;
            margin: 0;
            font-weight: bold;
        }
        .signature-area {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 60px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            color: rgba(26, 95, 42, 0.05);
            font-weight: bold;
            pointer-events: none;
            z-index: 0;
        }
        .stamp {
            position: absolute;
            bottom: 80px;
            right: 60px;
            width: 120px;
            height: 120px;
            border: 3px solid #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            font-weight: bold;
            font-size: 0.8rem;
            transform: rotate(-15deg);
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="no-print text-center mt-4 mb-3">
        <button onclick="window.print()" class="btn btn-success btn-lg">
            <i class="fas fa-print me-2"></i>Cetak Kwitansi
        </button>
        <a href="javascript:history.back()" class="btn btn-secondary btn-lg ms-2">Kembali</a>
    </div>

    <div class="kwitansi-container">
        <div class="watermark">ALFADH</div>

        <div class="kwitansi-header">
            <h3><i class="fas fa-kaaba"></i> KWITANSI PEMBAYARAN</h3>
            <p><strong>PT. ALFADH BERKAH HARAMAIN</strong></p>
            <p>Travel Umrah & Haji Khusus Terpercaya</p>
            <p>Telp: (021) 1234-5678 | Email: info@alfadh.com</p>
        </div>

        <div class="text-center">
            <div class="kwitansi-no">NO. <?= $data['kwitansi_no'] ?></div>
        </div>

        <div class="mb-4">
            <div class="info-row">
                <div class="info-label">Sudah terima dari</div>
                <div class="info-value">: <?= $data['nama_lengkap'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kode Jamaah</div>
                <div class="info-value">: <?= $data['kode_jamaah'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">No. KTP</div>
                <div class="info-value">: <?= $data['no_ktp'] ?? '-' ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat</div>
                <div class="info-value">: <?= $data['alamat'] ?? '-' ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">No. HP</div>
                <div class="info-value">: <?= $data['no_hp'] ?? '-' ?></div>
            </div>
        </div>

        <div class="amount-box">
            <small class="text-muted d-block mb-2">Uang Sejumlah:</small>
            <h2><?= rupiah($data['jumlah_bayar']) ?></h2>
            <small class="text-muted">(<?= ucwords(strtolower(terbilang($data['jumlah_bayar']))) ?> Rupiah)</small>
        </div>

        <div class="mb-4">
            <div class="info-row">
                <div class="info-label">Untuk Pembayaran</div>
                <div class="info-value">: <?= ucfirst($data['jenis_pembayaran']) ?> Paket Umrah <?= $data['nama_paket'] ?> (<?= $data['kode_paket'] ?>)</div>
            </div>
            <div class="info-row">
                <div class="info-label">Metode Bayar</div>
                <div class="info-value">: <?= ucfirst($data['metode_bayar']) ?></div>
            </div>
            <?php if($data['cicilan_ke'] > 0): ?>
            <div class="info-row">
                <div class="info-label">Cicilan Ke-</div>
                <div class="info-value">: <?= $data['cicilan_ke'] ?></div>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">Keterangan</div>
                <div class="info-value">: <?= $data['keterangan'] ?: '-' ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Bayar</div>
                <div class="info-value">: <?= tanggal_indo($data['tanggal_bayar']) ?></div>
            </div>
        </div>

        <div class="signature-area">
            <div class="signature-box">
                <p>Jakarta, <?= tanggal_indo($data['tanggal_bayar']) ?></p>
                <div class="signature-line"></div>
                <p><strong><?= $data['nama_lengkap'] ?></strong></p>
                <small>Jamaah</small>
            </div>
            <div class="signature-box">
                <p>&nbsp;</p>
                <div class="signature-line"></div>
                <p><strong><?= $data['nama_sales'] ?? 'Petugas' ?></strong></p>
                <small>Sales / Petugas</small>
            </div>
        </div>

        <div class="stamp">
            <div style="text-align:center; line-height:1.2">
                LUNAS<br>TERBAYAR<br><?= date('d/m/Y', strtotime($data['tanggal_bayar'])) ?>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size:0.8rem">
            <p>Kwitansi ini adalah bukti pembayaran yang sah. Simpan dengan baik.</p>
            <p><strong>PT. Alfadh Berkah Haramain</strong> - Izin: Umrah-2026-001</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
function terbilang($angka) {
    $angka = abs($angka);
    $baca = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
    $hasil = "";
    if ($angka < 12) {
        $hasil = $baca[$angka];
    } else if ($angka < 20) {
        $hasil = terbilang($angka - 10) . " belas";
    } else if ($angka < 100) {
        $hasil = terbilang($angka / 10) . " puluh " . terbilang($angka % 10);
    } else if ($angka < 200) {
        $hasil = "seratus " . terbilang($angka - 100);
    } else if ($angka < 1000) {
        $hasil = terbilang($angka / 100) . " ratus " . terbilang($angka % 100);
    } else if ($angka < 2000) {
        $hasil = "seribu " . terbilang($angka - 1000);
    } else if ($angka < 1000000) {
        $hasil = terbilang($angka / 1000) . " ribu " . terbilang($angka % 1000);
    } else if ($angka < 1000000000) {
        $hasil = terbilang($angka / 1000000) . " juta " . terbilang($angka % 1000000);
    } else if ($angka < 1000000000000) {
        $hasil = terbilang($angka / 1000000000) . " miliar " . terbilang($angka % 1000000000);
    }
    return trim($hasil);
}
?>
