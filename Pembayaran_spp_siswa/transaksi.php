<?php
require_once __DIR__ . '/koneksi.php';
require_login();

$pageTitle = 'Entri Transaksi Pembayaran';
$pageHeader = 'Pembayaran & Status Tagihan SPP Siswa';

$isSiswa = is_siswa();
$keyword = trim($_GET['cari'] ?? '');
if ($isSiswa) {
    $keyword = $_SESSION['siswa_nisn'];
}

$hasil_cari_siswa = null;
$tagihan_siswa = [];
$riwayat_siswa = [];
$daftar_siswa_cepat = [];

// PROSES POST PEMBAYARAN TAGIHAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bayar_tagihan') {
    if ($isSiswa) {
        set_flash('danger', 'Siswa hanya dapat melihat tagihan, pembayaran dilakukan melalui Petugas/Kasir.');
        header("Location: transaksi.php");
        exit;
    }

    $id_tagihan = (int)$_POST['id_tagihan'];
    $jumlah_bayar = (float)$_POST['jumlah_bayar'];
    $metode = htmlspecialchars($_POST['metode_pembayaran'] ?? 'tunai');
    $catatan = htmlspecialchars($_POST['catatan'] ?? 'Pembayaran SPP');
    $id_petugas = $_SESSION['user_id'] ?? 1;

    try {
        $pdo->beginTransaction();

        $stmtCek = $pdo->prepare("SELECT * FROM tagihan_spp WHERE id_tagihan = ? FOR UPDATE");
        $stmtCek->execute([$id_tagihan]);
        $tagihan = $stmtCek->fetch();

        if ($tagihan && $tagihan['status'] !== 'lunas') {
            $kode_trx = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);
            
            // Simpan ke pembayaran
            $stmtPay = $pdo->prepare("
                INSERT INTO pembayaran (kode_transaksi, id_tagihan, id_petugas, tgl_bayar, jumlah_bayar, metode_pembayaran, catatan)
                VALUES (?, ?, ?, NOW(), ?, ?, ?)
            ");
            $stmtPay->execute([$kode_trx, $id_tagihan, $id_petugas, $jumlah_bayar, $metode, $catatan]);
            $id_pembayaran_baru = $pdo->lastInsertId();

            // Update tagihan
            $terbayarBaru = $tagihan['terbayar'] + $jumlah_bayar;
            $status = ($terbayarBaru >= $tagihan['nominal_tagihan']) ? 'lunas' : 'belum_lunas';

            $stmtUpdate = $pdo->prepare("
                UPDATE tagihan_spp 
                SET terbayar = ?, status = ?
                WHERE id_tagihan = ?
            ");
            $stmtUpdate->execute([$terbayarBaru, $status, $id_tagihan]);

            $pdo->commit();
            set_flash('success', "Pembayaran berhasil disimpan! Kode: <strong>{$kode_trx}</strong> &nbsp; <a href='cetak_kuitansi.php?id={$id_pembayaran_baru}' target='_blank' class='btn btn-sm btn-dark text-white fw-bold ms-2'><i class='bi bi-printer me-1'></i> Cetak Kuitansi</a>");
            header("Location: transaksi.php?cari=" . urlencode($tagihan['nisn']));
            exit;
        } else {
            $pdo->rollBack();
            set_flash('warning', 'Tagihan tidak ditemukan atau sudah berstatus LUNAS.');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('danger', 'Gagal memproses transaksi: ' . $e->getMessage());
    }
}

// AMBIL DATA SISWA JIKA ADA SEARCH
if (!empty($keyword)) {
    try {
        $stmtSiswa = $pdo->prepare("
            SELECT s.*, k.nama_kelas, k.tingkat, k.jurusan, ts.nominal AS tarif_standar, ts.tahun AS tahun_ajaran
            FROM siswa s
            JOIN kelas k ON s.id_kelas = k.id_kelas
            JOIN tarif_spp ts ON s.id_spp = ts.id_spp
            WHERE s.nisn = ? OR s.nis = ? OR s.nama LIKE ?
            LIMIT 1
        ");
        $stmtSiswa->execute([$keyword, $keyword, "%{$keyword}%"]);
        $hasil_cari_siswa = $stmtSiswa->fetch();

        if ($hasil_cari_siswa) {
            // Ambil seluruh tagihan
            $stmtTagihan = $pdo->prepare("
                SELECT * FROM tagihan_spp 
                WHERE nisn = ? 
                ORDER BY tahun ASC, bulan ASC
            ");
            $stmtTagihan->execute([$hasil_cari_siswa['nisn']]);
            $tagihan_siswa = $stmtTagihan->fetchAll();

            // Ambil riwayat pembayaran siswa
            $stmtRiwayat = $pdo->prepare("
                SELECT p.*, t.nama_bulan, t.tahun AS tahun_spp, pet.nama_petugas
                FROM pembayaran p
                JOIN tagihan_spp t ON p.id_tagihan = t.id_tagihan
                JOIN petugas pet ON p.id_petugas = pet.id_petugas
                WHERE t.nisn = ?
                ORDER BY p.tgl_bayar DESC
            ");
            $stmtRiwayat->execute([$hasil_cari_siswa['nisn']]);
            $riwayat_siswa = $stmtRiwayat->fetchAll();
        }
    } catch (Exception $e) {
        set_flash('danger', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    }
} else {
    // Ambil daftar beberapa siswa untuk kemudahan klik
    $daftar_siswa_cepat = $pdo->query("
        SELECT s.nisn, s.nis, s.nama, k.nama_kelas 
        FROM siswa s 
        JOIN kelas k ON s.id_kelas = k.id_kelas 
        ORDER BY s.nama ASC 
        LIMIT 10
    ")->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<!-- PENCARIAN SISWA (Hanya Petugas / Admin) -->
<?php if (!$isSiswa): ?>
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-person-search me-2 text-primary"></i>Cari Siswa Berdasarkan NISN / NIS / Nama</h6>
        <form method="GET" action="transaksi.php" class="row g-2">
            <div class="col-md-9 col-sm-8">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="cari" class="form-control form-control-lg fs-6" 
                           placeholder="Ketik NISN (10 digit), NIS, atau Nama Siswa..." 
                           value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 fw-semibold">
                    <i class="bi bi-search me-1"></i> Cari Data
                </button>
                <?php if (!empty($_GET['cari'])): ?>
                    <a href="transaksi.php" class="btn btn-outline-secondary btn-lg fs-6">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($keyword) && !empty($daftar_siswa_cepat)): ?>
            <div class="mt-3 pt-3 border-top">
                <div class="small text-muted mb-2"><i class="bi bi-hand-index-thumb me-1"></i>Atau pilih cepat siswa di bawah ini:</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($daftar_siswa_cepat as $sc): ?>
                        <a href="transaksi.php?cari=<?= urlencode($sc['nisn']) ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1">
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($sc['nama']) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary ms-1"><?= htmlspecialchars($sc['nama_kelas']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- HASIL PENCARIAN & DATA RINCIAN -->
<?php if (!empty($keyword)): ?>
    <?php if ($hasil_cari_siswa): ?>
        <!-- PROFIL SISWA CARD -->
        <div class="card card-custom p-4 mb-4 border-start border-4 border-primary">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; font-size: 1.4rem;">
                            <?= strtoupper(substr($hasil_cari_siswa['nama'], 0, 1)) ?>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($hasil_cari_siswa['nama']) ?></h5>
                            <div class="small text-muted">
                                NISN: <span class="fw-semibold text-dark"><?= htmlspecialchars($hasil_cari_siswa['nisn']) ?></span> &bull; 
                                NIS: <span class="fw-semibold text-dark"><?= htmlspecialchars($hasil_cari_siswa['nis']) ?></span> &bull; 
                                Kelas: <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($hasil_cari_siswa['nama_kelas']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div class="small text-muted">Tarif SPP yang Berlaku:</div>
                    <div class="fw-bold fs-5 text-success"><?= rupiah($hasil_cari_siswa['tarif_standar']) ?> <span class="fs-6 fw-normal text-muted">/ bulan</span></div>
                    <div class="small text-muted">Tahun Ajaran: <?= htmlspecialchars($hasil_cari_siswa['tahun_ajaran']) ?></div>
                </div>
            </div>
        </div>

        <!-- DAFTAR TAGIHAN SPP -->
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Daftar Tagihan Bulanan SPP</h6>
                <?php if (!$isSiswa): ?>
                    <a href="generate_tagihan.php?nisn=<?= urlencode($hasil_cari_siswa['nisn']) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-plus-circle me-1"></i> Tambah / Generate Periode Tagihan
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th>Periode Tagihan</th>
                            <th>Jatuh Tempo</th>
                            <th>Nominal</th>
                            <th>Terbayar</th>
                            <th>Sisa Tagihan</th>
                            <th>Status</th>
                            <?php if (!$isSiswa): ?><th class="text-center">Aksi Bayar</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tagihan_siswa)): ?>
                            <?php foreach ($tagihan_siswa as $tgh): 
                                $sisa = $tgh['nominal_tagihan'] - $tgh['terbayar'];
                            ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($tgh['nama_bulan']) . ' ' . $tgh['tahun'] ?></td>
                                    <td class="small text-muted"><?= $tgh['jatuh_tempo'] ? tgl_indo($tgh['jatuh_tempo']) : '-' ?></td>
                                    <td><?= rupiah($tgh['nominal_tagihan']) ?></td>
                                    <td class="text-success fw-medium"><?= rupiah($tgh['terbayar']) ?></td>
                                    <td class="text-danger fw-semibold"><?= rupiah($sisa) ?></td>
                                    <td>
                                        <?php if ($tgh['status'] === 'lunas'): ?>
                                            <span class="badge badge-lunas px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> LUNAS</span>
                                        <?php else: ?>
                                            <span class="badge badge-belum px-2 py-1"><i class="bi bi-clock-history me-1"></i> BELUM LUNAS</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (!$isSiswa): ?>
                                        <td class="text-center">
                                            <?php if ($tgh['status'] !== 'lunas'): ?>
                                                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalBayar"
                                                        data-idtagihan="<?= $tgh['id_tagihan'] ?>"
                                                        data-periode="<?= htmlspecialchars($tgh['nama_bulan'] . ' ' . $tgh['tahun']) ?>"
                                                        data-sisa="<?= $sisa ?>">
                                                    <i class="bi bi-cash me-1"></i> Bayar
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="bi bi-check2-all text-success me-1"></i>Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $isSiswa ? '6' : '7' ?>" class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle fs-3 d-block mb-2 text-secondary"></i>
                                    Belum ada catatan tagihan untuk siswa ini. 
                                    <?php if (!$isSiswa): ?>
                                        <br><a href="generate_tagihan.php?nisn=<?= urlencode($hasil_cari_siswa['nisn']) ?>" class="btn btn-sm btn-primary mt-2">Generate Tagihan Sekarang</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIWAYAT TRANSAKSI SISWA INI -->
        <?php if (!empty($riwayat_siswa)): ?>
            <div class="card card-custom p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Riwayat Pembayaran Siswa Ini</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th>No Transaksi</th>
                                <th>Waktu Bayar</th>
                                <th>Periode Dibayar</th>
                                <th>Jumlah Bayar</th>
                                <th>Metode</th>
                                <th>Petugas Kasir</th>
                                <th class="text-center">Kuitansi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat_siswa as $rw): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($rw['kode_transaksi']) ?></span></td>
                                    <td class="small text-muted"><?= tgl_indo($rw['tgl_bayar'], true) ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($rw['nama_bulan']) . ' ' . $rw['tahun_spp'] ?></td>
                                    <td class="fw-bold text-success"><?= rupiah($rw['jumlah_bayar']) ?></td>
                                    <td><span class="badge bg-info-subtle text-info text-capitalize"><?= htmlspecialchars($rw['metode_pembayaran']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($rw['nama_petugas']) ?></td>
                                    <td class="text-center">
                                        <a href="cetak_kuitansi.php?id=<?= $rw['id_pembayaran'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Kuitansi">
                                            <i class="bi bi-printer me-1"></i> Cetak
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-warning card-custom border-0 p-4 text-center">
            <i class="bi bi-person-x fs-1 text-warning d-block mb-2"></i>
            <h5 class="fw-bold">Data Siswa Tidak Ditemukan</h5>
            <p class="text-muted mb-0">Tidak ada siswa yang cocok dengan kata kunci: "<strong><?= htmlspecialchars($keyword) ?></strong>". Silakan periksa kembali NISN atau ejaan nama.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- MODAL FORM TRANSAKSI BAYAR -->
<?php if (!$isSiswa): ?>
    <div class="modal fade" id="modalBayar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="transaksi.php?cari=<?= urlencode($keyword) ?>" class="modal-content border-0 shadow">
                <input type="hidden" name="action" value="bayar_tagihan">
                <input type="hidden" name="id_tagihan" id="modal_id_tagihan">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-wallet2 me-2"></i>Entri Pembayaran SPP</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Periode Tagihan SPP</label>
                        <input type="text" id="modal_periode" class="form-control fw-bold bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Nominal Pembayaran (Rp)</label>
                        <input type="number" name="jumlah_bayar" id="modal_jumlah" class="form-control form-control-lg fw-bold text-success" required>
                        <div class="form-text small">Dapat dibayar penuh sesuai sisa tagihan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select">
                            <option value="tunai" selected>Tunai / Cash (Di Kasir)</option>
                            <option value="transfer_bank">Transfer Bank</option>
                            <option value="qris">QRIS / E-Wallet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Catatan Pembayaran (Opsional)</label>
                        <input type="text" name="catatan" class="form-control" placeholder="Contoh: Lunas via Kasir">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Transaksi & Lunas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalBayar = document.getElementById('modalBayar');
        if (modalBayar) {
            modalBayar.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('modal_id_tagihan').value = btn.getAttribute('data-idtagihan');
                document.getElementById('modal_periode').value = btn.getAttribute('data-periode');
                document.getElementById('modal_jumlah').value = btn.getAttribute('data-sisa');
            });
        }
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
