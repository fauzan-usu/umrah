-- Database: alfadh_umrah
-- Versi: 1.0
-- Dibuat untuk: PT. Alfadh Berkah Haramain

CREATE DATABASE IF NOT EXISTS alfadh_umrah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alfadh_umrah;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','sales') NOT NULL DEFAULT 'sales',
    no_hp VARCHAR(20),
    email VARCHAR(100),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Paket Umrah
CREATE TABLE IF NOT EXISTS paket_umrah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_paket VARCHAR(150) NOT NULL,
    kode_paket VARCHAR(20) NOT NULL UNIQUE,
    harga DECIMAL(15,2) NOT NULL,
    durasi_hari INT NOT NULL,
    keberangkatan DATE,
    kamar ENUM('quad','triple','double') DEFAULT 'quad',
    fasilitas TEXT,
    kuota INT DEFAULT 0,
    status ENUM('aktif','nonaktif','selesai') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Jamaah
CREATE TABLE IF NOT EXISTS jamaah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_jamaah VARCHAR(20) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    no_ktp VARCHAR(20),
    no_passport VARCHAR(20),
    alamat TEXT,
    no_hp VARCHAR(20),
    email VARCHAR(100),
    paket_id INT,
    harga_paket DECIMAL(15,2),
    sales_id INT,
    status_jamaah ENUM('daftar','berkas','berangkat','selesai','batal') DEFAULT 'daftar',
    metode_pembayaran ENUM('lunas','bertahap') DEFAULT 'bertahap',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_id) REFERENCES paket_umrah(id) ON DELETE SET NULL,
    FOREIGN KEY (sales_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Pembayaran
CREATE TABLE IF NOT EXISTS pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kwitansi_no VARCHAR(30) NOT NULL UNIQUE,
    jamaah_id INT NOT NULL,
    tanggal_bayar DATE NOT NULL,
    jumlah_bayar DECIMAL(15,2) NOT NULL,
    jenis_pembayaran ENUM('dp','cicilan','pelunasan','lunas') NOT NULL,
    cicilan_ke INT DEFAULT 0,
    metode_bayar ENUM('transfer','cash','debit') DEFAULT 'transfer',
    keterangan TEXT,
    sales_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jamaah_id) REFERENCES jamaah(id) ON DELETE CASCADE,
    FOREIGN KEY (sales_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Log Aktivitas
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    aktivitas VARCHAR(255),
    detail TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert Data Dummy Admin
INSERT INTO users (nama, username, password, role, no_hp, email, status) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '081234567890', 'admin@alfadh.com', 'aktif');
-- Password default: password

-- Insert Data Dummy Sales
INSERT INTO users (nama, username, password, role, no_hp, email, status) VALUES
('Ahmad Fauzi', 'sales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', '081234567891', 'sales1@alfadh.com', 'aktif'),
('Budi Santoso', 'sales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', '081234567892', 'sales2@alfadh.com', 'aktif'),
('Citra Lestari', 'sales3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', '081234567893', 'sales3@alfadh.com', 'aktif');

-- Insert Data Dummy Paket Umrah
INSERT INTO paket_umrah (nama_paket, kode_paket, harga, durasi_hari, keberangkatan, kamar, fasilitas, kuota, status) VALUES
('Umrah Reguler Ramadhan 2026', 'UMR-RAM-001', 35000000, 12, '2026-03-15', 'quad', 'Tiket PP, Hotel Makkah & Madinah, Makan 3x, Bus AC, Guide Berpengalaman, Air Zamzam', 100, 'aktif'),
('Umrah Plus Turki 2026', 'UMR-TUR-002', 45000000, 15, '2026-04-10', 'triple', 'Tiket PP, Hotel Bintang 4, Tour Turki 3 hari, Makan Fullboard, Guide', 50, 'aktif'),
('Umrah Ekonomis 2026', 'UMR-EKO-003', 28000000, 9, '2026-05-20', 'quad', 'Tiket PP, Hotel Standar, Makan 2x, Bus, Guide', 80, 'aktif'),
('Umrah VIP Private 2026', 'UMR-VIP-004', 75000000, 12, '2026-06-01', 'double', 'Tiket Business Class, Hotel Bintang 5 dekat Haram, Private Guide, Fullboard Premium, Visa Express', 20, 'aktif'),
('Umrah Awal Tahun 2027', 'UMR-AWL-005', 32000000, 10, '2027-01-15', 'quad', 'Tiket PP, Hotel 3*, Makan 3x, Bus, Guide, Perlengkapan Umrah', 60, 'aktif');

-- Insert Data Dummy Jamaah
INSERT INTO jamaah (kode_jamaah, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, no_ktp, no_passport, alamat, no_hp, email, paket_id, harga_paket, sales_id, status_jamaah, metode_pembayaran) VALUES
('JMH-001', 'Abdullah bin Zubair', 'L', 'Jakarta', '1985-03-10', '3175011003850001', 'A1234567', 'Jl. Mawar No. 1, Jakarta', '081111111111', 'abdullah@email.com', 1, 35000000, 1, 'daftar', 'lunas'),
('JMH-002', 'Siti Aminah', 'P', 'Bandung', '1990-07-15', '3273011507900002', 'B2345678', 'Jl. Melati No. 5, Bandung', '082222222222', 'aminah@email.com', 1, 35000000, 1, 'daftar', 'bertahap'),
('JMH-003', 'Muhammad Rizky', 'L', 'Surabaya', '1988-11-20', '3578012011880003', 'C3456789', 'Jl. Anggrek No. 10, Surabaya', '083333333333', 'rizky@email.com', 2, 45000000, 2, 'daftar', 'bertahap'),
('JMH-004', 'Fatimah Az-Zahra', 'P', 'Yogyakarta', '1992-05-25', '3471012505920004', 'D4567890', 'Jl. Kenanga No. 3, Yogyakarta', '084444444444', 'fatimah@email.com', 1, 35000000, 1, 'daftar', 'lunas'),
('JMH-005', 'Umar bin Khattab', 'L', 'Medan', '1980-09-05', '1275010509800005', 'E5678901', 'Jl. Cempaka No. 7, Medan', '085555555555', 'umar@email.com', 3, 28000000, 3, 'daftar', 'bertahap'),
('JMH-006', 'Khadijah binti Khuwaylid', 'P', 'Palembang', '1983-12-12', '1671011212830006', 'F6789012', 'Jl. Dahlia No. 2, Palembang', '086666666666', 'khadijah@email.com', 2, 45000000, 2, 'daftar', 'lunas'),
('JMH-007', 'Ali bin Abi Thalib', 'L', 'Makassar', '1995-01-30', '7371013001950007', 'G7890123', 'Jl. Flamboyan No. 8, Makassar', '087777777777', 'ali@email.com', 4, 75000000, 1, 'daftar', 'bertahap'),
('JMH-008', 'Aisyah binti Abu Bakar', 'P', 'Semarang', '1987-04-18', '3371011804870008', 'H8901234', 'Jl. Bougenville No. 4, Semarang', '088888888888', 'aisyah@email.com', 1, 35000000, 3, 'daftar', 'bertahap'),
('JMH-009', 'Bilal bin Rabah', 'L', 'Malang', '1991-08-22', '3573012208910009', 'I9012345', 'Jl. Teratai No. 6, Malang', '089999999999', 'bilal@email.com', 3, 28000000, 2, 'daftar', 'lunas'),
('JMH-010', 'Zainab binti Jahsy', 'P', 'Denpasar', '1989-02-14', '5171011402890010', 'J0123456', 'Jl. Kamboja No. 9, Denpasar', '080101010101', 'zainab@email.com', 5, 32000000, 3, 'daftar', 'bertahap');

-- Insert Data Dummy Pembayaran
INSERT INTO pembayaran (kwitansi_no, jamaah_id, tanggal_bayar, jumlah_bayar, jenis_pembayaran, cicilan_ke, metode_bayar, keterangan, sales_id) VALUES
('KWT-20260521-0001', 1, '2026-05-21', 35000000, 'lunas', 0, 'transfer', 'Pembayaran lunas paket Umrah Reguler', 1),
('KWT-20260520-0002', 2, '2026-05-20', 10000000, 'dp', 1, 'transfer', 'DP pertama', 1),
('KWT-20260518-0003', 3, '2026-05-18', 15000000, 'dp', 1, 'cash', 'DP Paket Umrah Plus Turki', 2),
('KWT-20260515-0004', 4, '2026-05-15', 35000000, 'lunas', 0, 'transfer', 'Pelunasan', 1),
('KWT-20260510-0005', 5, '2026-05-10', 5000000, 'dp', 1, 'transfer', 'DP Umrah Ekonomis', 3),
('KWT-20260512-0006', 6, '2026-05-12', 45000000, 'lunas', 0, 'debit', 'Pembayaran lunas', 2),
('KWT-20260508-0007', 7, '2026-05-08', 25000000, 'dp', 1, 'transfer', 'DP VIP Private', 1),
('KWT-20260505-0008', 8, '2026-05-05', 8000000, 'dp', 1, 'cash', 'DP pertama', 3),
('KWT-20260501-0009', 9, '2026-05-01', 28000000, 'lunas', 0, 'transfer', 'Lunas Umrah Ekonomis', 2),
('KWT-20260428-0010', 2, '2026-04-28', 5000000, 'cicilan', 2, 'transfer', 'Cicilan ke-2', 1),
('KWT-20260425-0011', 3, '2026-04-25', 10000000, 'cicilan', 2, 'transfer', 'Cicilan ke-2', 2),
('KWT-20260420-0012', 10, '2026-04-20', 10000000, 'dp', 1, 'transfer', 'DP Awal Tahun', 3);
