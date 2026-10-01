CREATE DATABASE IF NOT EXISTS `pembayaran_spp_siswa` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pembayaran_spp_siswa`;

CREATE TABLE `petugas` (
    `id_petugas` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_petugas` VARCHAR(100) NOT NULL,
    `level` ENUM('admin', 'petugas', 'kepala_sekolah') NOT NULL DEFAULT 'petugas',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `tahun_ajaran` (
    `id_tahun_ajaran` INT AUTO_INCREMENT PRIMARY KEY,
    `tahun` VARCHAR(10) NOT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `kelas` (
    `id_kelas` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kelas` VARCHAR(20) NOT NULL,
    `tingkat` ENUM('10', '11', '12', '7', '8', '9', '1', '2', '3', '4', '5', '6') NOT NULL,
    `jurusan` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `tarif_spp` (
    `id_spp` INT AUTO_INCREMENT PRIMARY KEY,
    `id_tahun_ajaran` INT NOT NULL,
    `nominal` DECIMAL(12, 2) NOT NULL,
    `keterangan` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tarif_spp_tahun` FOREIGN KEY (`id_tahun_ajaran`) 
        REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `siswa` (
    `nisn` CHAR(10) PRIMARY KEY,
    `nis` CHAR(8) NOT NULL UNIQUE,
    `nama` VARCHAR(100) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `id_kelas` INT NOT NULL,
    `id_spp` INT NOT NULL,
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

CREATE TABLE `tagihan_spp` (
    `id_tagihan` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `nisn` CHAR(10) NOT NULL,
    `id_spp` INT NOT NULL,
    `bulan` TINYINT NOT NULL,
    `nama_bulan` VARCHAR(20) NOT NULL,
    `tahun` YEAR NOT NULL,
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

CREATE TABLE `pembayaran` (
    `id_pembayaran` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(30) NOT NULL UNIQUE,
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