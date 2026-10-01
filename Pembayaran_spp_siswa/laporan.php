<?php
require_once __DIR__ . '/koneksi.php';
require_petugas();

$pageTitle = 'Laporan Pembayaran SPP';
$pageHeader = 'Laporan & Rekapitulasi Pembayaran SPP';

// Parameter Filter
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$id_kelas = (int)($_GET['id_kelas'] ?? 0);
$metode = $_GET['metode'] ?? 'semua';

// Ambil kelas untuk filter
$daftar_kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

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

$query .= " ORDER BY p.tgl_bayar DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data_laporan = $stmt->fetchAll();

// Hitung total akumulasi
$total_nominal = 0;
$total_transaksi = count($data_laporan);
foreach ($data_laporan as $row) {
    $total_nominal += (float)$row['jumlah_bayar'];
}

include __DIR__ . '/includes/header.php';
?>

<div class="card card-custom p-4 mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-funnel-fill me-2 text-primary"></i>Filter Laporan Pembayaran</h6>
    <form method="GET" action="laporan.php" class="row g-3 align-items-end">
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-semibold">Tanggal Mulai</label>
            <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label small fw-semibold">Tanggal Selesai</label>
            <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>" required>
        </div>
        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold">Kelas</label>
            <select name="id_kelas" class="form-select">
                <option value="0">-- Semua Kelas --</option>
                <?php foreach ($daftar_kelas as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= ($id_kelas == $k['id_kelas']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6">
            <label class="form-label small fw-semibold">Metode</label>
            <select name="metode" class="form-select">
                <option value="semua" <?= ($metode === 'semua') ? 'selected' : '' ?>>Semua Metode</option>
                <option value="tunai" <?= ($metode === 'tunai') ? 'selected' : '' ?>>Tunai</option>
                <option value="transfer_bank" <?= ($metode === 'transfer_bank') ? 'selected' : '' ?>>Transfer Bank</option>
                <option value="qris" <?= ($metode === 'qris') ? 'selected' : '' ?>>QRIS</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-filter me-1"></i> Terapkan
            </button>
            <a href="laporan.php" class="btn btn-light border">Reset</a>
        </div>
    </form>
</div>

<!-- STATISTIK REKAP -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <span class="text-muted small fw-medium">Jumlah Transaksi Masuk</span>
            <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_transaksi) ?> <span class="fs-6 fw-normal text-muted">transaksi</span></h4>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <span class="text-muted small fw-medium">Total Akumulasi Pemasukan</span>
            <h4 class="fw-bold text-success mt-1 mb-0"><?= rupiah($total_nominal) ?></h4>
        </div>
    </div>
</div>

<!-- HASIL LAPORAN -->
<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0">Rincian Data Pembayaran</h6>
            <span class="text-muted small">Periode: <strong><?= tgl_indo($tgl_mulai) ?></strong> s.d. <strong><?= tgl_indo($tgl_selesai) ?></strong></span>
        </div>
        <div>
            <?php
            $printUrl = "cetak_laporan.php?tgl_mulai=" . urlencode($tgl_mulai) . "&tgl_selesai=" . urlencode($tgl_selesai) . "&id_kelas=" . $id_kelas . "&metode=" . urlencode($metode);
            ?>
            <a href="<?= $printUrl ?>" target="_blank" class="btn btn-outline-success fw-semibold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Cetak Laporan (PDF/Print)
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
                <tr>
                    <th>No.</th>
                    <th>Kode Trx</th>
                    <th>Tanggal Bayar</th>
                    <th>NISN / NIS</th>
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
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($row['kode_transaksi']) ?></span></td>
                            <td class="small"><?= tgl_indo($row['tgl_bayar'], true) ?></td>
                            <td class="small font-monospace"><?= htmlspecialchars($row['nisn']) ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($row['nama_kelas']) ?></span></td>
                            <td><?= htmlspecialchars($row['nama_bulan']) . ' ' . $row['tahun_spp'] ?></td>
                            <td><span class="badge bg-info-subtle text-info text-capitalize"><?= htmlspecialchars($row['metode_pembayaran']) ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['nama_petugas']) ?></td>
                            <td class="text-end fw-bold text-success"><?= rupiah($row['jumlah_bayar']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="9" class="text-end text-uppercase">Total Keseluruhan:</td>
                        <td class="text-end text-success fs-6"><?= rupiah($total_nominal) ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Tidak ada data transaksi pembayaran pada periode atau filter yang dipilih.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
