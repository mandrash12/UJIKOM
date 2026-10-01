<?php
require_once __DIR__ . '/koneksi.php';
require_petugas();

// Parameter Filter
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$id_kelas = (int)($_GET['id_kelas'] ?? 0);
$metode = $_GET['metode'] ?? 'semua';

$nama_filter_kelas = 'Semua Kelas';
if ($id_kelas > 0) {
    $stmtK = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id_kelas = ?");
    $stmtK->execute([$id_kelas]);
    $nama_filter_kelas = $stmtK->fetchColumn() ?: 'Semua Kelas';
}

// Query Laporan
$query = "
    SELECT p.*, t.nama_bulan, t.tahun AS tahun_spp,
           s.nisn, s.nis, s.nama AS nama_siswa, k.nama_kelas,
           pet.nama_petugas
    FROM pembayaran p
    JOIN tagihan_spp t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.nisn = s.nisn
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN petugas pet ON p.id_petugas = pet.id_petugas
    WHERE DATE(p.tgl_bayar) BETWEEN ? AND ?
";
$params = [$tgl_mulai, $tgl_selesai];

if ($id_kelas > 0) {
    $query .= " AND s.id_kelas = ?";
    $params[] = $id_kelas;
}

if ($metode !== 'semua' && !empty($metode)) {
    $query .= " AND p.metode_pembayaran = ?";
    $params[] = $metode;
}

$query .= " ORDER BY p.tgl_bayar ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data_laporan = $stmt->fetchAll();

$total_nominal = 0;
foreach ($data_laporan as $row) {
    $total_nominal += (float)$row['jumlah_bayar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Pembayaran SPP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Space+Mono&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #ffffff;
            color: #000000;
            padding: 30px;
        }
        .header-kop {
            text-align: center;
            border-bottom: 3px double #000000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-kop h3 {
            font-weight: 700;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .table-print {
            border: 1px solid #000000;
        }
        .table-print th, .table-print td {
            border: 1px solid #000000 !important;
            padding: 6px 10px;
            font-size: 0.88rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="bi bi-printer"></i> Cetak / Simpan ke PDF
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2 rounded-pill ms-2">
            Tutup
        </button>
    </div>

    <!-- KOP LAPORAN RESMI -->
    <div class="header-kop">
        <h3>PEMERINTAH PROVINSI / DAERAH</h3>
        <h2 class="fw-bold mb-1">SMK / SMA NEGERI INDONESIA</h2>
        <div class="small">Jl. Pendidikan No. 45, Gedung Pusat Administrasi &bull; Telp. (021) 12345678</div>
        <div class="small fst-italic">Email: info@sekolah.sch.id &bull; Website: www.sekolah.sch.id</div>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-uppercase text-decoration-underline mb-1">LAPORAN REKAPITULASI PEMBAYARAN SPP</h5>
        <div class="small">
            Periode: <strong><?= tgl_indo($tgl_mulai) ?></strong> s.d. <strong><?= tgl_indo($tgl_selesai) ?></strong> 
            &bull; Kelas: <strong><?= htmlspecialchars($nama_filter_kelas) ?></strong>
        </div>
    </div>

    <!-- TABEL DATA -->
    <table class="table table-bordered table-print align-middle mb-4">
        <thead class="table-light text-center">
            <tr>
                <th width="4%">No</th>
                <th>Kode Trx</th>
                <th>Tgl Bayar</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>SPP Bulan</th>
                <th>Metode</th>
                <th>Petugas</th>
                <th class="text-end">Jumlah Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data_laporan)): ?>
                <?php $no = 1; foreach ($data_laporan as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="font-monospace small"><?= htmlspecialchars($row['kode_transaksi']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tgl_bayar'])) ?></td>
                        <td class="font-monospace small"><?= htmlspecialchars($row['nisn']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                        <td><?= htmlspecialchars($row['nama_bulan']) . ' ' . $row['tahun_spp'] ?></td>
                        <td class="text-capitalize text-center"><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                        <td><?= htmlspecialchars($row['nama_petugas']) ?></td>
                        <td class="text-end fw-semibold"><?= rupiah($row['jumlah_bayar']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="fw-bold table-light">
                    <td colspan="9" class="text-end text-uppercase">Total Penerimaan:</td>
                    <td class="text-end fs-6"><?= rupiah($total_nominal) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center py-3">Tidak ada data pembayaran pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN TANDA TANGAN -->
    <div class="row pt-4 text-center" style="page-break-inside: avoid;">
        <div class="col-6">
            <div class="small mb-1">Mengetahui,</div>
            <div class="small fw-bold mb-5">Kepala Sekolah</div>
            <div class="fw-bold text-decoration-underline">Drs. H. Mulyadi, M.Pd.</div>
            <div class="small text-muted">NIP. 19750812 200003 1 002</div>
        </div>
        <div class="col-6">
            <div class="small mb-1">Kota Sekolah, <?= tgl_indo(date('Y-m-d')) ?></div>
            <div class="small fw-bold mb-5">Bendahara / Petugas Kasir</div>
            <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'Petugas Kasir') ?></div>
            <div class="small text-muted">NIP / NUPTK. -</div>
        </div>
    </div>

</body>
</html>
