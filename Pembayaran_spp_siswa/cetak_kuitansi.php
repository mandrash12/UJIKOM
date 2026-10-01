<?php
require_once __DIR__ . '/koneksi.php';
require_login();

$id_pembayaran = (int)($_GET['id'] ?? 0);

if ($id_pembayaran <= 0) {
    die("ID Transaksi Pembayaran tidak valid.");
}

// Ambil detail transaksi
$stmt = $pdo->prepare("
    SELECT p.*, t.nama_bulan, t.tahun AS tahun_spp, t.nominal_tagihan,
           s.nisn, s.nis, s.nama AS nama_siswa, k.nama_kelas,
           pet.nama_petugas
    FROM pembayaran p
    JOIN tagihan_spp t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.nisn = s.nisn
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN petugas pet ON p.id_petugas = pet.id_petugas
    WHERE p.id_pembayaran = ?
    LIMIT 1
");
$stmt->execute([$id_pembayaran]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Data transaksi pembayaran tidak ditemukan.");
}

// Jika siswa login, pastikan hanya boleh mencetak kuitansi miliknya
if (is_siswa() && $trx['nisn'] !== $_SESSION['siswa_nisn']) {
    die("Akses ditolak. Anda tidak berhak melihat kuitansi ini.");
}

// Fungsi Terbilang Rupiah sederhana
function terbilang($angka) {
    $angka = abs((int)$angka);
    $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $terbilang = '';

    if ($angka < 12) {
        $terbilang = ' ' . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $terbilang = terbilang(floor($angka / 10)) . ' Puluh' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = ' Seratus' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang(floor($angka / 100)) . ' Ratus' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = ' Seribu' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang(floor($angka / 1000)) . ' Ribu' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang(floor($angka / 1000000)) . ' Juta' . terbilang($angka % 1000000);
    }
    return trim($terbilang);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Pembayaran SPP - <?= htmlspecialchars($trx['kode_transaksi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Space+Mono&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 30px 10px;
        }
        .receipt-box {
            max-width: 680px;
            margin: auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .kop-sekolah {
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .kode-badge {
            font-family: 'Space Mono', monospace;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
        }
        .nominal-highlight {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 15px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .receipt-box {
                box-shadow: none;
                border: 1px solid #000000;
                padding: 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="bi bi-printer"></i> Cetak Dokumen Kuitansi
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2 rounded-pill ms-2">
            Tutup
        </button>
    </div>

    <div class="receipt-box">
        <!-- KOP BUKTI PEMBAYARAN -->
        <div class="kop-sekolah d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-dark">KUITANSI PEMBAYARAN SPP</h4>
                <div class="text-muted small">Sistem Informasi Administrasi SPP Siswa Terpadu</div>
            </div>
            <div class="text-end">
                <span class="kode-badge text-primary"><?= htmlspecialchars($trx['kode_transaksi']) ?></span>
                <div class="small text-muted mt-1"><?= tgl_indo($trx['tgl_bayar'], true) ?></div>
            </div>
        </div>

        <!-- RINCIAN SISWA -->
        <table class="table table-borderless table-sm mb-3">
            <tr>
                <td width="28%" class="text-muted">Nomor Induk Siswa (NIS)</td>
                <td width="2%">:</td>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($trx['nis']) ?> (NISN: <?= htmlspecialchars($trx['nisn']) ?>)</td>
            </tr>
            <tr>
                <td class="text-muted">Nama Siswa</td>
                <td>:</td>
                <td class="fw-bold text-dark fs-6"><?= htmlspecialchars($trx['nama_siswa']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Kelas</td>
                <td>:</td>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($trx['nama_kelas']) ?></td>
            </tr>
            <tr>
                <td class="text-muted">Untuk Pembayaran</td>
                <td>:</td>
                <td class="fw-semibold text-primary">SPP Bulan <?= htmlspecialchars($trx['nama_bulan']) . ' ' . $trx['tahun_spp'] ?></td>
            </tr>
            <tr>
                <td class="text-muted">Metode Pembayaran</td>
                <td>:</td>
                <td class="text-capitalize fw-semibold text-dark"><?= htmlspecialchars($trx['metode_pembayaran']) ?></td>
            </tr>
            <?php if (!empty($trx['catatan'])): ?>
                <tr>
                    <td class="text-muted">Keterangan / Catatan</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($trx['catatan']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- RINCIAN NOMINAL -->
        <div class="nominal-highlight mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-semibold text-uppercase">Jumlah Diterima (Lunas):</span>
                <span class="fs-4 fw-bold text-success"><?= rupiah($trx['jumlah_bayar']) ?></span>
            </div>
            <div class="small text-muted fst-italic">
                Terbilang: <strong><?= terbilang($trx['jumlah_bayar']) ?> Rupiah</strong>
            </div>
        </div>

        <!-- TANDA TANGAN -->
        <div class="row pt-2 text-center">
            <div class="col-6">
                <div class="small text-muted mb-5">Siswa / Pembayar</div>
                <div class="fw-bold text-dark text-decoration-underline"><?= htmlspecialchars($trx['nama_siswa']) ?></div>
            </div>
            <div class="col-6">
                <div class="small text-muted mb-5">Petugas Kasir Sekolah</div>
                <div class="fw-bold text-dark text-decoration-underline"><?= htmlspecialchars($trx['nama_petugas']) ?></div>
            </div>
        </div>

        <div class="text-center mt-4 pt-3 border-top text-muted" style="font-size: 0.72rem;">
            <em>Simpan kuitansi ini sebagai bukti pembayaran SPP yang sah. Dokumen dicetak otomatis oleh sistem.</em>
        </div>
    </div>

</body>
</html>
