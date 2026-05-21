# PT. Alfadh Berkah Haramain - Sistem Pemantauan Pembayaran Jamaah Umrah

Aplikasi web berbasis PHP & MySQL untuk memantau pembayaran jamaah umrah dengan fitur kwitansi otomatis.

## Fitur Utama

### Role Admin (Super User)
- Dashboard dengan grafik & statistik real-time
- Manajemen Paket Umrah (CRUD)
- Manajemen Data Jamaah dengan progress pembayaran
- Manajemen Pembayaran (input & tracking)
- Laporan & Rekapitulasi Keuangan (filter periode, paket, sales)
- Manajemen Sales (CRUD)
- Log Aktivitas

### Role Sales
- Dashboard personal
- Melihat jamaah yang ditangani
- Membuat kwitansi pembayaran (DP, Cicilan, Pelunasan, Lunas)
- Cetak kwitansi resmi

### Fitur Pembayaran
- Pembayaran Lunas (sekali bayar)
- Pembayaran Bertahap (DP + Cicilan + Pelunasan)
- Auto-generate nomor kwitansi (KWT-YYYYMMDD-XXXX)
- Progress bar visual untuk setiap jamaah
- Auto-update status lunas saat pembayaran penuh

## Teknologi
- PHP 8.x Native (tanpa framework)
- MySQL/MariaDB
- Bootstrap 5.3
- Chart.js (grafik dashboard)
- Font Awesome 6

## Instalasi (XAMPP)

1. Copy folder `alfadh-umrah-app` ke `C:\xampp\htdocs\`

2. Buat database di phpMyAdmin:
   - Buka browser: `http://localhost/phpmyadmin`
   - Klik "Import"
   - Pilih file `alfadh_umrah.sql`
   - Klik "Go"

3. Atur koneksi database (jika perlu):
   - Edit file `includes/config.php`
   - Sesuaikan `DB_USER` dan `DB_PASS` jika MySQL punya password

4. Akses aplikasi:
   - Buka browser: `http://localhost/alfadh-umrah-app/`

## Login Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | password |
| Sales | sales1 | password |
| Sales | sales2 | password |
| Sales | sales3 | password |

## Struktur Folder

```
alfadh-umrah-app/
├── index.php              # Halaman login
├── auth.php               # Proses login/logout
├── dashboard.php          # Router dashboard
├── alfadh_umrah.sql       # Database schema + dummy data
├── includes/
│   ├── config.php         # Konfigurasi & helper
│   └── session_check.php  # Cek session
├── pages/
│   ├── admin/             # Modul Admin
│   │   ├── dashboard.php
│   │   ├── paket.php
│   │   ├── jamaah.php
│   │   ├── pembayaran.php
│   │   ├── laporan.php
│   │   └── users.php
│   ├── sales/             # Modul Sales
│   │   ├── dashboard.php
│   │   ├── jamaah.php
│   │   └── pembayaran.php
│   └── kwitansi/          # Cetak kwitansi
│       └── cetak.php
└── assets/                # CSS, JS, Images
```

## Catatan Keamanan
- Password di-hash dengan `password_hash()` (bcrypt)
- Session management dengan validasi status user
- Prepared statements untuk semua query (anti SQL Injection)
- Log aktivitas untuk audit trail

## Lisensi
Free untuk PT. Alfadh Berkah Haramain

---
Dibuat untuk XAMPP terbaru (PHP 8.x + MySQL/MariaDB)
