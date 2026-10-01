<?php
require_once __DIR__ . '/koneksi.php';
require_admin();

$pageTitle = 'Data Kelas';
$pageHeader = 'Manajemen Kelas & Jurusan';

// PROSES TAMBAH / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');
    $tingkat = $_POST['tingkat'] ?? '10';
    $jurusan = trim($_POST['jurusan'] ?? '');

    if (!empty($nama_kelas)) {
        try {
            if ($action === 'tambah') {
                $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, tingkat, jurusan) VALUES (?, ?, ?)");
                $stmt->execute([$nama_kelas, $tingkat, $jurusan]);
                set_flash('success', "Kelas <strong>" . htmlspecialchars($nama_kelas) . "</strong> berhasil ditambahkan.");
            } elseif ($action === 'edit') {
                $id_kelas = (int)$_POST['id_kelas'];
                $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ?, jurusan = ? WHERE id_kelas = ?");
                $stmt->execute([$nama_kelas, $tingkat, $jurusan, $id_kelas]);
                set_flash('success', "Data kelas berhasil diperbarui.");
            }
        } catch (Exception $e) {
            set_flash('danger', 'Gagal memproses data kelas: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Nama kelas tidak boleh kosong.');
    }
    header("Location: kelas.php");
    exit;
}

// PROSES HAPUS
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)$_GET['id'];
    try {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id_kelas = ?");
        $stmtCek->execute([$id_hapus]);
        if ($stmtCek->fetchColumn() > 0) {
            set_flash('warning', 'Kelas tidak dapat dihapus karena masih memiliki data siswa di dalamnya.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Kelas berhasil dihapus.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    }
    header("Location: kelas.php");
    exit;
}

// AMBIL DAFTAR KELAS BESERTA JUMLAH SISWA
$stmtKelas = $pdo->query("
    SELECT k.*, COUNT(s.nisn) AS jumlah_siswa
    FROM kelas k
    LEFT JOIN siswa s ON k.id_kelas = s.id_kelas
    GROUP BY k.id_kelas
    ORDER BY k.tingkat ASC, k.nama_kelas ASC
");
$daftar_kelas = $stmtKelas->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <!-- FORM TAMBAH KELAS -->
    <div class="col-lg-4">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Kelas Baru</h6>
            <form method="POST" action="kelas.php">
                <input type="hidden" name="action" value="tambah">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Kelas</label>
                    <input type="text" name="nama_kelas" class="form-control" placeholder="Contoh: X RPL 1, XII IPA 2" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tingkat</label>
                    <select name="tingkat" class="form-select" required>
                        <option value="10">Kelas 10 (SMA/SMK)</option>
                        <option value="11">Kelas 11 (SMA/SMK)</option>
                        <option value="12">Kelas 12 (SMA/SMK)</option>
                        <option value="7">Kelas 7 (SMP/MTs)</option>
                        <option value="8">Kelas 8 (SMP/MTs)</option>
                        <option value="9">Kelas 9 (SMP/MTs)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan / Peminatan</label>
                    <input type="text" name="jurusan" class="form-control" placeholder="Contoh: Rekayasa Perangkat Lunak">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-save me-1"></i> Simpan Kelas
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL DAFTAR KELAS -->
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-building me-2 text-primary"></i>Daftar Kelas Terdaftar</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Tingkat</th>
                            <th>Nama Kelas</th>
                            <th>Jurusan</th>
                            <th>Jumlah Siswa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($daftar_kelas)): ?>
                            <?php foreach ($daftar_kelas as $k): ?>
                                <tr>
                                    <td><span class="badge bg-secondary">Tingkat <?= htmlspecialchars($k['tingkat']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars($k['jurusan'] ?: '-') ?></td>
                                    <td>
                                        <a href="siswa.php?kelas=<?= $k['id_kelas'] ?>" class="badge bg-primary-subtle text-primary text-decoration-none">
                                            <?= $k['jumlah_siswa'] ?> Siswa
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-warning btn-edit-kelas"
                                                    data-id="<?= $k['id_kelas'] ?>"
                                                    data-nama="<?= htmlspecialchars($k['nama_kelas']) ?>"
                                                    data-tingkat="<?= htmlspecialchars($k['tingkat']) ?>"
                                                    data-jurusan="<?= htmlspecialchars($k['jurusan'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalConfirmDelete" 
                                                    data-href="kelas.php?action=hapus&id=<?= $k['id_kelas'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada kelas terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT KELAS -->
<div class="modal fade" id="modalEditKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="kelas.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kelas" id="edit_id_kelas">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Kelas</label>
                    <input type="text" name="nama_kelas" id="edit_nama_kelas" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Tingkat</label>
                    <select name="tingkat" id="edit_tingkat" class="form-select" required>
                        <option value="10">Kelas 10</option>
                        <option value="11">Kelas 11</option>
                        <option value="12">Kelas 12</option>
                        <option value="7">Kelas 7</option>
                        <option value="8">Kelas 8</option>
                        <option value="9">Kelas 9</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <input type="text" name="jurusan" id="edit_jurusan" class="form-control">
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
document.querySelectorAll('.btn-edit-kelas').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id_kelas').value = this.getAttribute('data-id');
        document.getElementById('edit_nama_kelas').value = this.getAttribute('data-nama');
        document.getElementById('edit_tingkat').value = this.getAttribute('data-tingkat');
        document.getElementById('edit_jurusan').value = this.getAttribute('data-jurusan');

        const modal = new bootstrap.Modal(document.getElementById('modalEditKelas'));
        modal.show();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
