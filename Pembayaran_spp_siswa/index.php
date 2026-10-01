<?php
require_once __DIR__ . '/koneksi.php';
require_login();

// Jika siswa login, arahkan langsung ke halaman portal tagihan siswa
if (is_siswa()) {
    header('Location: transaksi.php');
    exit;
}

$pageTitle = 'Dashboard';
$pageHeader = 'Dashboard Administrator & Petugas';

// Hitung data statistik
$total_siswa = 0;
$total_pemasukan_hari_ini = 0;
$total_pemasukan_bulan_ini = 0;
$total_tunggakan = 0;
$transaksi_terbaru = [];

try {
    // Total Siswa
    $total_siswa = $pdo->query("SELECT COUNT(*) FROM siswa WHERE status_siswa = 'aktif'")->fetchColumn();

    // Pemasukan Hari Ini
    $total_pemasukan_hari_ini = $pdo->query("
        SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran WHERE DATE(tgl_bayar) = CURDATE()
    ")->fetchColumn();

    // Pemasukan Bulan Ini
    $total_pemasukan_bulan_ini = $pdo->query("
        SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran 
        WHERE MONTH(tgl_bayar) = MONTH(CURDATE()) AND YEAR(tgl_bayar) = YEAR(CURDATE())
    ")->fetchColumn();

    // Tagihan Menunggak
    $total_tunggakan = $pdo->query("SELECT COUNT(*) FROM tagihan_spp WHERE status = 'belum_lunas'")->fetchColumn();

    // 8 Transaksi Terakhir
    $stmtTrx = $pdo->query("
        SELECT p.id_pembayaran, p.kode_transaksi, p.tgl_bayar, p.jumlah_bayar, p.metode_pembayaran,
               s.nama AS nama_siswa, s.nisn, k.nama_kelas, t.nama_bulan, t.tahun AS tahun_spp,
               pet.nama_petugas
        FROM pembayaran p
        JOIN tagihan_spp t ON p.id_tagihan = t.id_tagihan
        JOIN siswa s ON t.nisn = s.nisn
        JOIN kelas k ON s.id_kelas = k.id_kelas
        JOIN petugas pet ON p.id_petugas = pet.id_petugas
        ORDER BY p.tgl_bayar DESC
        LIMIT 8
    ");
    $transaksi_terbaru = $stmtTrx->fetchAll();
} catch (Exception $e) {
    set_flash('danger', 'Gagal memuat ringkasan data: ' . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- KARTU STATISTIK RINGKASAN -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Siswa Aktif</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_siswa) ?></h3>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;"><i class="bi bi-mortarboard text-primary me-1"></i>Terdaftar di sistem</div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Pemasukan Hari Ini</span>
                    <h3 class="fw-bold text-success mt-1 mb-0"><?= rupiah($total_pemasukan_hari_ini) ?></h3>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;"><i class="bi bi-clock-history text-success me-1"></i><?= date('d M Y') ?></div>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-4">
                    <i class="bi bi-calendar2-check-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Pemasukan Bulan Ini</span>
                    <h3 class="fw-bold text-primary mt-1 mb-0"><?= rupiah($total_pemasukan_bulan_ini) ?></h3>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;"><i class="bi bi-graph-up-arrow text-primary me-1"></i>Bulan <?= date('F Y') ?></div>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Tagihan Menunggak</span>
                    <h3 class="fw-bold text-danger mt-1 mb-0"><?= number_format($total_tunggakan) ?> <span class="fs-6 fw-normal text-muted">Bln</span></h3>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;"><i class="bi bi-exclamation-circle text-danger me-1"></i>Belum diselesaikan</div>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-4">
                    <i class="bi bi-exclamation-octagon-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SHORTCUT PENCARIAN CEPAT & AKSI -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card card-custom h-100 p-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-search me-2 text-primary"></i>Pencarian Cepat Pembayaran Siswa</h6>
            <p class="text-muted small mb-3">Ketik NISN, NIS, atau Nama siswa untuk langsung memproses pembayaran atau mengecek tunggakan.</p>
            <form method="GET" action="transaksi.php" class="row g-2">
                <div class="col-sm-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-bounding-box text-muted"></i></span>
                        <input type="text" name="cari" class="form-control form-control-lg fs-6" placeholder="Masukkan NISN / Nama Siswa..." required>
                    </div>
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 fw-semibold">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-custom h-100 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Aksi Cepat</h6>
            <div class="d-grid gap-2">
                <a href="transaksi.php" class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                    <span class="small fw-semibold"><i class="bi bi-cash-coin me-2"></i>Entri Pembayaran Baru</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="generate_tagihan.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                    <span class="small fw-semibold"><i class="bi bi-calendar-plus me-2"></i>Generate Tagihan Siswa</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="laporan.php" class="btn btn-outline-success text-start d-flex align-items-center justify-content-between p-2 rounded-3">
                    <span class="small fw-semibold"><i class="bi bi-printer me-2"></i>Cetak Laporan Rekapitulasi</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- TABEL 8 TRANSAKSI TERBARU -->
<div class="card card-custom p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Pembayaran Terbaru</h6>
            <span class="text-muted small">Transaksi SPP yang baru saja dicatat oleh petugas kasir</span>
        </div>
        <a href="laporan.php" class="btn btn-sm btn-light border fw-semibold">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th>Kode Trx</th>
                    <th>Waktu Bayar</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Periode SPP</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                    <th>Petugas</th>
                    <th class="text-center">Kuitansi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transaksi_terbaru)): ?>
                    <?php foreach ($transaksi_terbaru as $trx): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($trx['kode_transaksi']) ?></span>
                            </td>
                            <td class="small text-muted"><?= tgl_indo($trx['tgl_bayar'], true) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($trx['nama_siswa']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;">NISN: <?= htmlspecialchars($trx['nisn']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($trx['nama_kelas']) ?></span></td>
                            <td><span class="fw-medium"><?= htmlspecialchars($trx['nama_bulan']) . ' ' . $trx['tahun_spp'] ?></span></td>
                            <td class="fw-bold text-success"><?= rupiah($trx['jumlah_bayar']) ?></td>
                            <td><span class="badge bg-info-subtle text-info text-capitalize"><?= htmlspecialchars($trx['metode_pembayaran']) ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($trx['nama_petugas']) ?></td>
                            <td class="text-center">
                                <a href="cetak_kuitansi.php?id=<?= $trx['id_pembayaran'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Kuitansi">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat transaksi pembayaran SPP.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
