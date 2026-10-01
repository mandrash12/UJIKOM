-- ====================================================================
-- Database: pembayaran_spp_siswa
-- RDBMS: MySQL / MariaDB (kompatibel InnoDB)
-- Dibuat untuk sistem informasi pembayaran SPP sekolah
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `pembayaran_spp_siswa` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `pembayaran_spp_siswa`;

-- Nonaktifkan pengecekan foreign key sementara untuk inisialisasi
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabel User / Petugas / Pengguna Sistem
DROP TABLE IF EXISTS `petugas`;
CREATE TABLE `petugas` (
    `id_petugas` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_petugas` VARCHAR(100) NOT NULL,
    `level` ENUM('admin', 'petugas', 'kepala_sekolah') NOT NULL DEFAULT 'petugas',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Kelas
DROP TABLE IF EXISTS `kelas`;
CREATE TABLE `kelas` (
    `id_kelas` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kelas` VARCHAR(20) NOT NULL, -- Contoh: 'X RPL 1', 'XII IPA 2'
    `tingkat` ENUM('10', '11', '12', '7', '8', '9', '1', '2', '3', '4', '5', '6') NOT NULL,
    `jurusan` VARCHAR(50) DEFAULT NULL, -- Contoh: 'Rekayasa Perangkat Lunak', 'MIPA'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Tarif SPP
DROP TABLE IF EXISTS `tarif_spp`;
CREATE TABLE `tarif_spp` (
    `id_spp` INT AUTO_INCREMENT PRIMARY KEY,
    `tahun` VARCHAR(10) NOT NULL, -- Contoh: '2025/2026'
    `nominal` DECIMAL(12, 2) NOT NULL, -- Contoh: 250000.00
    `keterangan` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Tabel Siswa
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
    `nisn` CHAR(10) PRIMARY KEY, -- Nomor Induk Siswa Nasional (10 digit standar Kemdikbud)
    `nis` CHAR(8) NOT NULL UNIQUE,
    `nama` VARCHAR(100) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `id_kelas` INT NOT NULL,
    `id_spp` INT NOT NULL, -- Relasi ke tarif SPP yang berlaku untuk siswa ini
    `alamat` TEXT NULL,
    `no_telp` VARCHAR(15) NULL,
    `nama_wali` VARCHAR(100) NULL,
    `no_telp_wali` VARCHAR(15) NULL,
    `status_siswa` ENUM('aktif', 'lulus', 'pindah', 'keluar') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`id_kelas`) 
        REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_siswa_spp` FOREIGN KEY (`id_spp`) 
        REFERENCES `tarif_spp` (`id_spp`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Tabel Tagihan SPP (Mencatat kewajiban bayar per bulan tiap siswa)
DROP TABLE IF EXISTS `tagihan_spp`;
CREATE TABLE `tagihan_spp` (
    `id_tagihan` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `nisn` CHAR(10) NOT NULL,
    `id_spp` INT NOT NULL,
    `bulan` TINYINT NOT NULL, -- 1 = Januari, ..., 12 = Desember, atau sesuai kalender akademik (7 = Juli)
    `nama_bulan` VARCHAR(20) NOT NULL, -- 'Juli', 'Agustus', dst.
    `tahun` YEAR NOT NULL, -- 2025, 2026
    `nominal_tagihan` DECIMAL(12, 2) NOT NULL,
    `terbayar` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('belum_lunas', 'lunas') NOT NULL DEFAULT 'belum_lunas',
    `jatuh_tempo` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_siswa_bulan_tahun` (`nisn`, `bulan`, `tahun`),
    CONSTRAINT `fk_tagihan_siswa` FOREIGN KEY (`nisn`) 
        REFERENCES `siswa` (`nisn`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tagihan_spp` FOREIGN KEY (`id_spp`) 
        REFERENCES `tarif_spp` (`id_spp`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Tabel Pembayaran / Transaksi SPP
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
    `id_pembayaran` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(30) NOT NULL UNIQUE, -- Contoh: 'TRX-SPP-20251001-0001'
    `id_tagihan` BIGINT NOT NULL,
    `id_petugas` INT NOT NULL,
    `tgl_bayar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `jumlah_bayar` DECIMAL(12, 2) NOT NULL,
    `metode_pembayaran` ENUM('tunai', 'transfer_bank', 'qris') NOT NULL DEFAULT 'tunai',
    `catatan` VARCHAR(255) NULL,
    `bukti_transfer` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pembayaran_tagihan` FOREIGN KEY (`id_tagihan`) 
        REFERENCES `tagihan_spp` (`id_tagihan`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pembayaran_petugas` FOREIGN KEY (`id_petugas`) 
        REFERENCES `petugas` (`id_petugas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Aktifkan kembali pengecekan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- INDEKS OPTIMASI PENCARIAN
-- ====================================================================
CREATE INDEX `idx_siswa_nama` ON `siswa` (`nama`);
CREATE INDEX `idx_pembayaran_tgl` ON `pembayaran` (`tgl_bayar`);
CREATE INDEX `idx_tagihan_status` ON `tagihan_spp` (`status`);

-- ====================================================================
-- DATA CONTOH / SEED DATA
-- ====================================================================

-- 1. Petugas (admin: 'admin123', petugas1 & kepsek: 'password123')
INSERT INTO `petugas` (`id_petugas`, `username`, `password`, `nama_petugas`, `level`) VALUES
(1, 'admin', '$2y$10$wSkNH7hX0jJJkQZHRMtWt.3kyc4tkqIk60f7gH5B9iK9QrcPkKkRG', 'Administrator Utama', 'admin'),
(2, 'petugas1', '$2y$10$RY2htW3vpry56B2uH89uK.LmaaWbqlrNxG3x4Kr6y8UWkeYzcukSm', 'Siti Nurhaliza', 'petugas'),
(3, 'kepsek', '$2y$10$RY2htW3vpry56B2uH89uK.LmaaWbqlrNxG3x4Kr6y8UWkeYzcukSm', 'Drs. H. Mulyadi, M.Pd.', 'kepala_sekolah');

-- 2. Kelas
INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `tingkat`, `jurusan`) VALUES
(1, 'X RPL 1', '10', 'Rekayasa Perangkat Lunak'),
(2, 'X TKJ 1', '10', 'Teknik Komputer & Jaringan'),
(3, 'XI RPL 1', '11', 'Rekayasa Perangkat Lunak'),
(4, 'XII RPL 1', '12', 'Rekayasa Perangkat Lunak');

-- 3. Tarif SPP
INSERT INTO `tarif_spp` (`id_spp`, `tahun`, `nominal`, `keterangan`) VALUES
(1, '2024/2025', 200000.00, 'Tarif Angkatan Masuk 2024'),
(2, '2025/2026', 250000.00, 'Tarif Angkatan Masuk 2025');

-- 5. Siswa
INSERT INTO `siswa` (`nisn`, `nis`, `nama`, `jenis_kelamin`, `id_kelas`, `id_spp`, `alamat`, `no_telp`, `nama_wali`, `no_telp_wali`, `status_siswa`) VALUES
('0061234567', '25261001', 'Ahmad Farhan', 'L', 1, 2, 'Jl. Merdeka No. 12', '081234567890', 'Budi Santoso', '081298765432', 'aktif'),
('0067654321', '25261002', 'Bunga Citra Lestari', 'P', 1, 2, 'Jl. Mawar No. 45', '081398761234', 'Iwan Setiawan', '081345678901', 'aktif');

-- 6. Tagihan SPP (Contoh tagihan bulan Juli 2025)
INSERT INTO `tagihan_spp` (`id_tagihan`, `nisn`, `id_spp`, `bulan`, `nama_bulan`, `tahun`, `nominal_tagihan`, `terbayar`, `status`, `jatuh_tempo`) VALUES
(1, '0061234567', 2, 7, 'Juli', 2025, 250000.00, 250000.00, 'lunas', '2025-07-10'),
(2, '0061234567', 2, 8, 'Agustus', 2025, 250000.00, 0.00, 'belum_lunas', '2025-08-10'),
(3, '0067654321', 2, 7, 'Juli', 2025, 250000.00, 0.00, 'belum_lunas', '2025-07-10');

-- 7. Riwayat Transaksi Pembayaran
INSERT INTO `pembayaran` (`id_pembayaran`, `kode_transaksi`, `id_tagihan`, `id_petugas`, `tgl_bayar`, `jumlah_bayar`, `metode_pembayaran`, `catatan`) VALUES
(1, 'TRX-SPP-20250705-0001', 1, 2, '2025-07-05 09:30:00', 250000.00, 'tunai', 'Pembayaran SPP Bulan Juli lunas');
