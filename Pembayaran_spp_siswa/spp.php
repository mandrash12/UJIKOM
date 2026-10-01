<?php
require_once __DIR__ . '/koneksi.php';
require_admin();

$pageTitle = 'Tarif SPP & Tahun Ajaran';
$pageHeader = 'Manajemen Tarif SPP per Angkatan';

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // TAMBAH TARIF SPP
    if ($action === 'tambah_spp') {
        $tahun = trim($_POST['tahun'] ?? '');
        $nominal = (float)$_POST['nominal'];
        $ket = trim($_POST['keterangan'] ?? '');

        if (!empty($tahun) && $nominal > 0) {
            $stmt = $pdo->prepare("INSERT INTO tarif_spp (tahun, nominal, keterangan) VALUES (?, ?, ?)");
            $stmt->execute([$tahun, $nominal, $ket]);
            set_flash('success', "Tarif SPP tahun <strong>{$tahun}</strong> sebesar " . rupiah($nominal) . " berhasil disimpan.");
        } else {
            set_flash('danger', 'Harap isi tahun ajaran dan nominal tarif SPP dengan benar.');
        }
    }
    // EDIT TARIF SPP
    elseif ($action === 'edit_spp') {
        $id_spp = (int)$_POST['id_spp'];
        $tahun = trim($_POST['tahun'] ?? '');
        $nominal = (float)$_POST['nominal'];
        $ket = trim($_POST['keterangan'] ?? '');

        if (!empty($tahun) && $nominal > 0) {
            $stmt = $pdo->prepare("UPDATE tarif_spp SET tahun = ?, nominal = ?, keterangan = ? WHERE id_spp = ?");
            $stmt->execute([$tahun, $nominal, $ket, $id_spp]);
            set_flash('success', "Perubahan tarif SPP berhasil disimpan.");
        }
    }
    header("Location: spp.php");
    exit;
}

// HAPUS TARIF SPP
if (isset($_GET['action']) && $_GET['action'] === 'hapus_spp') {
    $id = (int)$_GET['id'];
    try {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id_spp = ?");
        $stmtCek->execute([$id]);
        if ($stmtCek->fetchColumn() > 0) {
            set_flash('warning', 'Tarif SPP ini sedang digunakan oleh siswa terdaftar dan tidak dapat dihapus.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM tarif_spp WHERE id_spp = ?");
            $stmt->execute([$id]);
            set_flash('success', 'Tarif SPP berhasil dihapus.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Gagal menghapus: ' . $e->getMessage());
    }
    header("Location: spp.php");
    exit;
}

// AMBIL DATA TARIF SPP
$tarif_spp_list = $pdo->query("
    SELECT ts.*, COUNT(s.nisn) AS total_siswa
    FROM tarif_spp ts
    LEFT JOIN siswa s ON ts.id_spp = s.id_spp
    GROUP BY ts.id_spp
    ORDER BY ts.tahun DESC, ts.nominal ASC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <!-- PANEL KIRI: FORM INPUT -->
    <div class="col-lg-4">
        <!-- FORM TARIF SPP -->
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-tag-fill me-2 text-primary"></i>Tambah Tarif SPP Baru</h6>
            <form method="POST" action="spp.php">
                <input type="hidden" name="action" value="tambah_spp">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tahun Pelajaran / Angkatan</label>
                    <input type="text" name="tahun" class="form-control" placeholder="Contoh: 2025/2026 atau 2025" required>
                    <div class="form-text small">Masukkan tahun ajaran berlakunya tarif.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nominal SPP per Bulan (Rp)</label>
                    <input type="number" name="nominal" class="form-control" placeholder="Contoh: 250000" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Tarif Angkatan Masuk 2025">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-save me-1"></i> Simpan Tarif SPP
                </button>
            </form>
        </div>
    </div>

    <!-- PANEL KANAN: TABEL TARIF SPP -->
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-primary"></i>Daftar Master Tarif SPP</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Tahun Ajaran</th>
                            <th>Nominal per Bulan</th>
                            <th>Keterangan</th>
                            <th>Siswa Terikat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tarif_spp_list)): ?>
                            <?php foreach ($tarif_spp_list as $sp): ?>
                                <tr>
                                    <td><span class="badge bg-primary-subtle text-primary fs-6"><?= htmlspecialchars($sp['tahun']) ?></span></td>
                                    <td class="fw-bold text-success fs-6"><?= rupiah($sp['nominal']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($sp['keterangan'] ?: '-') ?></td>
                                    <td><span class="badge bg-secondary"><?= $sp['total_siswa'] ?> Siswa</span></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-warning btn-edit-spp"
                                                    data-id="<?= $sp['id_spp'] ?>"
                                                    data-tahun="<?= htmlspecialchars($sp['tahun']) ?>"
                                                    data-nominal="<?= (int)$sp['nominal'] ?>"
                                                    data-ket="<?= htmlspecialchars($sp['keterangan'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalConfirmDelete" 
                                                    data-href="spp.php?action=hapus_spp&id=<?= $sp['id_spp'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada tarif SPP.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT TARIF SPP -->
<div class="modal fade" id="modalEditSpp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="spp.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit_spp">
            <input type="hidden" name="id_spp" id="edit_id_spp">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Tarif SPP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tahun Pelajaran / Angkatan</label>
                    <input type="text" name="tahun" id="edit_tahun" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nominal per Bulan (Rp)</label>
                    <input type="number" name="nominal" id="edit_nominal" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Keterangan</label>
                    <input type="text" name="keterangan" id="edit_keterangan" class="form-control">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning px-4 fw-semibold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-spp').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id_spp').value = this.getAttribute('data-id');
        document.getElementById('edit_tahun').value = this.getAttribute('data-tahun');
        document.getElementById('edit_nominal').value = this.getAttribute('data-nominal');
        document.getElementById('edit_keterangan').value = this.getAttribute('data-ket');

        const modal = new bootstrap.Modal(document.getElementById('modalEditSpp'));
        modal.show();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
