<?php
require_once __DIR__ . '/koneksi.php';
require_petugas();

$pageTitle = 'Generate Tagihan SPP';
$pageHeader = 'Pembuatan & Manajemen Tagihan SPP Siswa';

// Ambil daftar kelas untuk filter & dropdown
$daftar_kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

// Ambil daftar siswa aktif untuk dropdown pilih siswa tertentu
$daftar_siswa_aktif = $pdo->query("
    SELECT s.nisn, s.nis, s.nama, k.nama_kelas, ts.nominal, ts.tahun AS tahun_spp
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN tarif_spp ts ON s.id_spp = ts.id_spp
    WHERE s.status_siswa = 'aktif'
    ORDER BY k.nama_kelas ASC, s.nama ASC
")->fetchAll();

$nisn_filter = trim($_GET['nisn'] ?? '');

$nama_bulan_arr = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// =============================================================================
// PROSES POST: GENERATE TAGIHAN
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $target = $_POST['target_generate'] ?? 'semua'; // 'semua', 'kelas', 'siswa'
    $id_kelas = (int)($_POST['id_kelas'] ?? 0);
    $nisn_target = trim($_POST['nisn_target'] ?? '');
    $tahun_kalender = (int)($_POST['tahun_kalender'] ?? date('Y'));
    $tipe_periode = $_POST['tipe_periode'] ?? 'akademik'; // 'akademik', 'kalender', 'satu_bulan'
    $bulan_pilihan = (int)($_POST['bulan_pilihan'] ?? date('n'));
    $use_custom_nominal = isset($_POST['use_custom_nominal']);
    $nominal_kustom = (float)($_POST['nominal_kustom'] ?? 0);
    $jatuh_tempo_tgl = (int)($_POST['jatuh_tempo_tgl'] ?? 10);
    if ($jatuh_tempo_tgl < 1 || $jatuh_tempo_tgl > 28) {
        $jatuh_tempo_tgl = 10;
    }

    try {
        // 1. Tentukan target siswa
        if ($target === 'siswa') {
            if (empty($nisn_target)) {
                set_flash('danger', 'Harap pilih salah satu siswa tujuan generate.');
                header("Location: generate_tagihan.php");
                exit;
            }
            $stmtTarget = $pdo->prepare("SELECT nisn, id_spp FROM siswa WHERE nisn = ? AND status_siswa = 'aktif'");
            $stmtTarget->execute([$nisn_target]);
            $list_siswa = $stmtTarget->fetchAll();
        } elseif ($target === 'kelas') {
            if ($id_kelas <= 0) {
                set_flash('danger', 'Harap pilih kelas tujuan generate.');
                header("Location: generate_tagihan.php");
                exit;
            }
            $stmtTarget = $pdo->prepare("SELECT nisn, id_spp FROM siswa WHERE id_kelas = ? AND status_siswa = 'aktif'");
            $stmtTarget->execute([$id_kelas]);
            $list_siswa = $stmtTarget->fetchAll();
        } else {
            // Semua siswa aktif
            $list_siswa = $pdo->query("SELECT nisn, id_spp FROM siswa WHERE status_siswa = 'aktif'")->fetchAll();
        }

        if (empty($list_siswa)) {
            set_flash('warning', 'Tidak ada data siswa aktif yang ditemukan pada target yang dipilih.');
            header("Location: generate_tagihan.php");
            exit;
        }

        // 2. Susun daftar periode (bulan & tahun) yang akan dibuat
        $siklus = [];
        if ($tipe_periode === 'akademik') {
            // 12 Bulan Tahun Ajaran: Juli - Desember (Tahun N), Januari - Juni (Tahun N+1)
            $bln_akademik = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];
            foreach ($bln_akademik as $bln) {
                $thn = ($bln >= 7) ? $tahun_kalender : ($tahun_kalender + 1);
                $siklus[] = ['bulan' => $bln, 'tahun' => $thn];
            }
        } elseif ($tipe_periode === 'kalender') {
            // 12 Bulan Tahun Kalender Masehi: Januari s/d Desember (Tahun N)
            for ($b = 1; $b <= 12; $b++) {
                $siklus[] = ['bulan' => $b, 'tahun' => $tahun_kalender];
            }
        } elseif ($tipe_periode === 'satu_bulan') {
            // 1 Bulan Tertentu saja
            $siklus[] = ['bulan' => $bulan_pilihan, 'tahun' => $tahun_kalender];
        }

        // 3. Query tarif siswa & query insert ignore
        $stmtTarif = $pdo->prepare("SELECT nominal FROM tarif_spp WHERE id_spp = ?");
        $stmtInsert = $pdo->prepare("
            INSERT IGNORE INTO tagihan_spp (nisn, id_spp, bulan, nama_bulan, tahun, nominal_tagihan, terbayar, status, jatuh_tempo)
            VALUES (?, ?, ?, ?, ?, ?, 0.00, 'belum_lunas', ?)
        ");

        $total_dibuat = 0;
        $total_dilewati = 0;
        $pdo->beginTransaction();

        foreach ($list_siswa as $sw) {
            $nominal = 0;
            if ($use_custom_nominal && $nominal_kustom > 0) {
                $nominal = $nominal_kustom;
            } else {
                $stmtTarif->execute([$sw['id_spp']]);
                $nominal = (float)$stmtTarif->fetchColumn();
            }

            foreach ($siklus as $sk) {
                $nama_bln = $nama_bulan_arr[$sk['bulan']];
                $jatuh_tempo = sprintf('%04d-%02d-%02d', $sk['tahun'], $sk['bulan'], $jatuh_tempo_tgl);

                $stmtInsert->execute([
                    $sw['nisn'],
                    $sw['id_spp'],
                    $sk['bulan'],
                    $nama_bln,
                    $sk['tahun'],
                    $nominal,
                    $jatuh_tempo
                ]);

                if ($stmtInsert->rowCount() > 0) {
                    $total_dibuat++;
                } else {
                    $total_dilewati++;
                }
            }
        }

        $pdo->commit();
        set_flash('success', "Proses generate selesai! Berhasil membuat <strong>{$total_dibuat}</strong> record tagihan baru. (" . ($total_dilewati > 0 ? "{$total_dilewati} tagihan dilewati otomatis karena sudah ada sebelumnya" : "Tidak ada duplikasi") . ").");
        header("Location: generate_tagihan.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('danger', 'Gagal memproses generate tagihan: ' . $e->getMessage());
        header("Location: generate_tagihan.php");
        exit;
    }
}

// =============================================================================
// PROSES HAPUS SATUAN TAGIHAN (JIKA BELUM ADA PEMBAYARAN)
// =============================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus_tagihan') {
    $id_tagihan_hapus = (int)($_GET['id'] ?? 0);
    try {
        // Pastikan belum pernah dibayar (terbayar == 0)
        $stmtCek = $pdo->prepare("SELECT id_tagihan, terbayar, status FROM tagihan_spp WHERE id_tagihan = ?");
        $stmtCek->execute([$id_tagihan_hapus]);
        $tgh = $stmtCek->fetch();

        if ($tgh) {
            if ($tgh['terbayar'] > 0 || $tgh['status'] === 'lunas') {
                set_flash('warning', 'Tagihan tidak dapat dihapus karena sudah memiliki riwayat pembayaran.');
            } else {
                $stmtDel = $pdo->prepare("DELETE FROM tagihan_spp WHERE id_tagihan = ?");
                $stmtDel->execute([$id_tagihan_hapus]);
                set_flash('success', 'Tagihan yang belum dibayar berhasil dihapus.');
            }
        } else {
            set_flash('danger', 'Data tagihan tidak ditemukan.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Gagal menghapus tagihan: ' . $e->getMessage());
    }
    header("Location: generate_tagihan.php");
    exit;
}

// =============================================================================
// FILTER & DAFTAR TAGIHAN MONITORING
// =============================================================================
$filter_kls = (int)($_GET['f_kelas'] ?? 0);
$filter_status = $_GET['f_status'] ?? 'semua';
$filter_cari = trim($_GET['f_cari'] ?? '');

$queryList = "
    SELECT t.*, s.nama AS nama_siswa, s.nis, k.nama_kelas
    FROM tagihan_spp t
    JOIN siswa s ON t.nisn = s.nisn
    JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE 1=1
";
$paramsList = [];

if ($filter_kls > 0) {
    $queryList .= " AND s.id_kelas = ?";
    $paramsList[] = $filter_kls;
}
if ($filter_status === 'lunas' || $filter_status === 'belum_lunas') {
    $queryList .= " AND t.status = ?";
    $paramsList[] = $filter_status;
}
if (!empty($filter_cari)) {
    $queryList .= " AND (s.nisn LIKE ? OR s.nama LIKE ? OR t.nama_bulan LIKE ?)";
    $paramsList[] = "%{$filter_cari}%";
    $paramsList[] = "%{$filter_cari}%";
    $paramsList[] = "%{$filter_cari}%";
}

$queryList .= " ORDER BY t.id_tagihan DESC LIMIT 30";
$stmtList = $pdo->prepare($queryList);
$stmtList->execute($paramsList);
$daftar_tagihan_terbaru = $stmtList->fetchAll();

// Total Statistik Tagihan
$total_semua_tagihan = $pdo->query("SELECT COUNT(*) FROM tagihan_spp")->fetchColumn();
$total_tagihan_lunas = $pdo->query("SELECT COUNT(*) FROM tagihan_spp WHERE status = 'lunas'")->fetchColumn();
$total_tagihan_belum = $pdo->query("SELECT COUNT(*) FROM tagihan_spp WHERE status = 'belum_lunas'")->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<!-- RINGKASAN DATA TAGIHAN -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <span class="text-muted small fw-medium">Total Seluruh Tagihan Dibuat</span>
            <h4 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_semua_tagihan) ?> <span class="fs-6 fw-normal text-muted">record</span></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <span class="text-muted small fw-medium">Tagihan Sudah Lunas</span>
            <h4 class="fw-bold text-success mt-1 mb-0"><?= number_format($total_tagihan_lunas) ?> <span class="fs-6 fw-normal text-muted">record</span></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <span class="text-muted small fw-medium">Tagihan Belum Lunas (Tunggakan)</span>
            <h4 class="fw-bold text-danger mt-1 mb-0"><?= number_format($total_tagihan_belum) ?> <span class="fs-6 fw-normal text-muted">record</span></h4>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- FORM GENERATE TAGIHAN -->
    <div class="col-lg-5">
        <div class="card card-custom p-4">
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                    <i class="bi bi-calendar-plus fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Form Generate Tagihan</h5>
                    <span class="text-muted small">Buat tagihan SPP massal atau individu</span>
                </div>
            </div>

            <form method="POST" action="generate_tagihan.php">
                <input type="hidden" name="action" value="generate">

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Penerima Tagihan</label>
                    <select name="target_generate" id="target_generate" class="form-select" onchange="toggleTargetFields()" required>
                        <option value="semua" <?= empty($nisn_filter) ? 'selected' : '' ?>>Semua Siswa Aktif (Satu Sekolah)</option>
                        <option value="kelas">Berdasarkan Kelas Tertentu</option>
                        <option value="siswa" <?= !empty($nisn_filter) ? 'selected' : '' ?>>Siswa Tertentu (Pilih Siswa)</option>
                    </select>
                </div>

                <!-- PILIH KELAS -->
                <div class="mb-3" id="field_kelas" style="display: none;">
                    <label class="form-label small fw-semibold">Pilih Kelas Tujuan</label>
                    <select name="id_kelas" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($daftar_kelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?> (<?= htmlspecialchars($k['jurusan'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PILIH SISWA -->
                <div class="mb-3" id="field_siswa" style="<?= !empty($nisn_filter) ? '' : 'display: none;' ?>">
                    <label class="form-label small fw-semibold">Pilih Nama Siswa</label>
                    <select name="nisn_target" class="form-select">
                        <option value="">-- Cari / Pilih Siswa --</option>
                        <?php foreach ($daftar_siswa_aktif as $swa): ?>
                            <option value="<?= $swa['nisn'] ?>" <?= ($nisn_filter === $swa['nisn']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($swa['nama']) ?> (NISN: <?= $swa['nisn'] ?> - <?= htmlspecialchars($swa['nama_kelas']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PILIHAN PERIODE -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Pilihan Periode Pembuatan</label>
                    <select name="tipe_periode" id="tipe_periode" class="form-select" onchange="togglePeriodeFields()">
                        <option value="akademik" selected>12 Bulan Tahun Ajaran (Juli s.d Juni Tahun Depan)</option>
                        <option value="kalender">12 Bulan Tahun Kalender (Januari s.d Desember)</option>
                        <option value="satu_bulan">Satu Bulan Saja (Bulan Tertentu)</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold">Tahun</label>
                        <input type="number" name="tahun_kalender" class="form-control" value="<?= date('Y') ?>" min="2020" max="2035" required>
                    </div>
                    <div class="col-sm-6" id="field_satu_bulan" style="display: none;">
                        <label class="form-label small fw-semibold">Pilih Bulan</label>
                        <select name="bulan_pilihan" class="form-select">
                            <?php foreach ($nama_bulan_arr as $m_num => $m_name): ?>
                                <option value="<?= $m_num ?>" <?= (date('n') == $m_num) ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tanggal Jatuh Tempo Tiap Bulan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">Tgl</span>
                        <input type="number" name="jatuh_tempo_tgl" class="form-control" value="10" min="1" max="28" required>
                        <span class="input-group-text bg-light text-muted small">setiap bulannya</span>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="use_custom_nominal" id="use_custom_nominal" onchange="toggleCustomNominal()">
                    <label class="form-check-label small fw-medium" for="use_custom_nominal">
                        Gunakan Nominal Manual Khusus (Abaikan tarif siswa)
                    </label>
                </div>

                <div class="mb-3" id="field_custom_nominal" style="display: none;">
                    <label class="form-label small fw-semibold">Nominal Khusus (Rp)</label>
                    <input type="number" name="nominal_kustom" class="form-control" placeholder="Contoh: 250000" min="0">
                </div>

                <div class="alert alert-light border small text-muted mb-4">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Sistem otomatis menerapkan <strong>proteksi anti-duplikasi</strong>. Tagihan pada bulan dan tahun yang sama tidak akan terbuat dobel.
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-gear-wide-connected me-1"></i> Jalankan Generate Tagihan
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL MONITORING DAFTAR TAGIHAN -->
    <div class="col-lg-7">
        <div class="card card-custom p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Riwayat Tagihan Terbaru</h6>
                    <span class="text-muted small">30 record tagihan yang telah dibuat</span>
                </div>
            </div>

            <!-- FILTER PENCARIAN CEPAT TAGIHAN -->
            <form method="GET" action="generate_tagihan.php" class="row g-2 mb-3">
                <div class="col-sm-5">
                    <input type="text" name="f_cari" class="form-control form-control-sm" placeholder="Cari NISN/Nama/Bulan..." value="<?= htmlspecialchars($filter_cari) ?>">
                </div>
                <div class="col-sm-4">
                    <select name="f_kelas" class="form-select form-select-sm">
                        <option value="0">-- Semua Kelas --</option>
                        <?php foreach ($daftar_kelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>" <?= ($filter_kls == $k['id_kelas']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    <?php if (!empty($filter_cari) || $filter_kls > 0): ?>
                        <a href="generate_tagihan.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Siswa</th>
                            <th>Periode</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($daftar_tagihan_terbaru)): ?>
                            <?php foreach ($daftar_tagihan_terbaru as $tg): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($tg['nama_siswa']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($tg['nama_kelas']) ?> &bull; <?= $tg['nisn'] ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($tg['nama_bulan']) . ' ' . $tg['tahun'] ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Tempo: <?= $tg['jatuh_tempo'] ? date('d/m/Y', strtotime($tg['jatuh_tempo'])) : '-' ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= rupiah($tg['nominal_tagihan']) ?></div>
                                        <?php if ($tg['terbayar'] > 0): ?>
                                            <div class="text-success small">Bayar: <?= rupiah($tg['terbayar']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tg['status'] === 'lunas'): ?>
                                            <span class="badge badge-lunas px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> LUNAS</span>
                                        <?php else: ?>
                                            <span class="badge badge-belum px-2 py-1"><i class="bi bi-clock-history me-1"></i> BELUM LUNAS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($tg['status'] !== 'lunas' && $tg['terbayar'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalConfirmDelete" 
                                                    data-href="generate_tagihan.php?action=hapus_tagihan&id=<?= $tg['id_tagihan'] ?>"
                                                    title="Hapus Tagihan yang Belum Dibayar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small" title="Sudah ada transaksi pembayaran, tidak dapat dihapus"><i class="bi bi-lock-fill"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data tagihan yang sesuai dengan filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTargetFields() {
    const val = document.getElementById('target_generate').value;
    document.getElementById('field_kelas').style.display = (val === 'kelas') ? 'block' : 'none';
    document.getElementById('field_siswa').style.display = (val === 'siswa') ? 'block' : 'none';
}

function togglePeriodeFields() {
    const val = document.getElementById('tipe_periode').value;
    document.getElementById('field_satu_bulan').style.display = (val === 'satu_bulan') ? 'block' : 'none';
}

function toggleCustomNominal() {
    const checked = document.getElementById('use_custom_nominal').checked;
    document.getElementById('field_custom_nominal').style.display = checked ? 'block' : 'none';
}

// Inisialisasi saat load
toggleTargetFields();
togglePeriodeFields();
toggleCustomNominal();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
