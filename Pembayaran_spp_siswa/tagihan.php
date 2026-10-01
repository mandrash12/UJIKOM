<?php
require_once __DIR__ . '/koneksi.php';
require_petugas();

$pageTitle = 'Tagihan SPP';
$pageHeader = 'Pembuatan Tagihan SPP Bulanan Siswa';

// Ambil daftar kelas untuk filter
$daftar_kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();

$nisn_filter = trim($_GET['nisn'] ?? '');

// PROSES GENERATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $target = $_POST['target_generate'] ?? 'semua'; // 'semua', 'kelas', 'siswa'
    $id_kelas = (int)($_POST['id_kelas'] ?? 0);
    $nisn_target = trim($_POST['nisn'] ?? '');
    $tahun_kalender = (int)$_POST['tahun_kalender']; // contoh 2025
    $tipe_siklus = $_POST['tipe_siklus'] ?? 'akademik'; // 'akademik' (Juli - Juni) atau 'kalender' (Jan - Des)

    try {
        // Tentukan siswa yang akan dibuatkan tagihan
        if ($target === 'siswa' && !empty($nisn_target)) {
            $stmtTarget = $pdo->prepare("SELECT nisn, id_spp FROM siswa WHERE nisn = ? AND status_siswa = 'aktif'");
            $stmtTarget->execute([$nisn_target]);
            $list_siswa = $stmtTarget->fetchAll();
        } elseif ($target === 'kelas' && $id_kelas > 0) {
            $stmtTarget = $pdo->prepare("SELECT nisn, id_spp FROM siswa WHERE id_kelas = ? AND status_siswa = 'aktif'");
            $stmtTarget->execute([$id_kelas]);
            $list_siswa = $stmtTarget->fetchAll();
        } else {
            // Semua siswa aktif
            $list_siswa = $pdo->query("SELECT nisn, id_spp FROM siswa WHERE status_siswa = 'aktif'")->fetchAll();
        }

        if (empty($list_siswa)) {
            set_flash('warning', 'Tidak ada data siswa aktif yang ditemukan untuk kriteria yang dipilih.');
            header("Location: generate_tagihan.php");
            exit;
        }

        // Tentukan daftar bulan
        $nama_bulan_arr = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if ($tipe_siklus === 'akademik') {
            // Juli - Desember (Tahun N), Januari - Juni (Tahun N+1)
            $siklus = [
                ['bulan' => 7, 'tahun' => $tahun_kalender],
                ['bulan' => 8, 'tahun' => $tahun_kalender],
                ['bulan' => 9, 'tahun' => $tahun_kalender],
                ['bulan' => 10, 'tahun' => $tahun_kalender],
                ['bulan' => 11, 'tahun' => $tahun_kalender],
                ['bulan' => 12, 'tahun' => $tahun_kalender],
                ['bulan' => 1, 'tahun' => $tahun_kalender + 1],
                ['bulan' => 2, 'tahun' => $tahun_kalender + 1],
                ['bulan' => 3, 'tahun' => $tahun_kalender + 1],
                ['bulan' => 4, 'tahun' => $tahun_kalender + 1],
                ['bulan' => 5, 'tahun' => $tahun_kalender + 1],
                ['bulan' => 6, 'tahun' => $tahun_kalender + 1],
            ];
        } else {
            // Januari - Desember (Tahun N)
            $siklus = [];
            for ($b = 1; $b <= 12; $b++) {
                $siklus[] = ['bulan' => $b, 'tahun' => $tahun_kalender];
            }
        }

        $stmtTarif = $pdo->prepare("SELECT nominal FROM tarif_spp WHERE id_spp = ?");
        $stmtInsert = $pdo->prepare("
            INSERT INTO tagihan_spp (nisn, id_spp, bulan, nama_bulan, tahun, nominal_tagihan, terbayar, status, jatuh_tempo)
            VALUES (?, ?, ?, ?, ?, ?, 0.00, 'belum_lunas', ?)
            ON DUPLICATE KEY UPDATE id_tagihan = id_tagihan
        ");

        $total_dibuat = 0;
        $pdo->beginTransaction();

        foreach ($list_siswa as $sw) {
            $stmtTarif->execute([$sw['id_spp']]);
            $nominal = (float)$stmtTarif->fetchColumn();

            foreach ($siklus as $sk) {
                $nama_bln = $nama_bulan_arr[$sk['bulan']];
                $jatuh_tempo = sprintf('%04d-%02d-10', $sk['tahun'], $sk['bulan']); // Jatuh tempo tanggal 10 tiap bulan

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
                }
            }
        }

        $pdo->commit();
        set_flash('success', "Proses selesai! Berhasil membuat <strong>{$total_dibuat}</strong> tagihan SPP baru (tagihan yang sudah ada dilewati otomatis).");
        header("Location: generate_tagihan.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('danger', 'Gagal membuat tagihan: ' . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card card-custom p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="bg-primary text-white p-3 rounded-4">
                    <i class="bi bi-calendar-plus fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Generate Tagihan SPP Bulanan</h5>
                    <p class="text-muted small mb-0">Buat kewajiban tagihan SPP 12 bulan secara otomatis untuk siswa atau satu kelas.</p>
                </div>
            </div>

            <form method="POST" action="generate_tagihan.php">
                <input type="hidden" name="action" value="generate">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Target Siswa</label>
                    <select name="target_generate" id="target_generate" class="form-select form-select-lg fs-6" onchange="toggleTargetFields()">
                        <option value="semua" <?= empty($nisn_filter) ? 'selected' : '' ?>>Semua Siswa Aktif (Satu Sekolah)</option>
                        <option value="kelas">Berdasarkan Kelas Tertentu</option>
                        <option value="siswa" <?= !empty($nisn_filter) ? 'selected' : '' ?>>Siswa Tertentu (Berdasarkan NISN)</option>
                    </select>
                </div>

                <div class="mb-3" id="field_kelas" style="display: none;">
                    <label class="form-label fw-semibold">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select">
                        <?php foreach ($daftar_kelas as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?> (<?= htmlspecialchars($k['jurusan'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="field_siswa" style="<?= !empty($nisn_filter) ? '' : 'display: none;' ?>">
                    <label class="form-label fw-semibold">NISN Siswa</label>
                    <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($nisn_filter) ?>" placeholder="Contoh: 0061234567">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tahun Awal</label>
                        <input type="number" name="tahun_kalender" class="form-control" value="<?= date('Y') ?>" min="2020" max="2035" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Siklus Kalender</label>
                        <select name="tipe_siklus" class="form-select">
                            <option value="akademik" selected>Tahun Ajaran (Juli s.d Juni Tahun Depan)</option>
                            <option value="kalender">Tahun Masehi (Januari s.d Desember)</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 small mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Catatan:</strong> Sistem otomatis mendeteksi tarif SPP siswa berdasarkan angkatannya dan tidak akan membuat tagihan ganda jika tagihan bulan tersebut sudah pernah dibuat sebelumnya.
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-light border px-4">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="bi bi-gear-wide-connected me-1"></i> Mulai Generate Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTargetFields() {
    const val = document.getElementById('target_generate').value;
    document.getElementById('field_kelas').style.display = (val === 'kelas') ? 'block' : 'none';
    document.getElementById('field_siswa').style.display = (val === 'siswa') ? 'block' : 'none';
}
// Jalankan saat load
toggleTargetFields();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
