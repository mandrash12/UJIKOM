<?php
require_once __DIR__ . '/koneksi.php';
require_admin();

$pageTitle = 'Data Petugas & Pengguna';
$pageHeader = 'Manajemen Petugas & Hak Akses Pengguna';

// PROSES TAMBAH / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nama = trim($_POST['nama_petugas'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $level = $_POST['level'] ?? 'petugas';

    if ($action === 'tambah') {
        $password = trim($_POST['password'] ?? '');
        if (!empty($nama) && !empty($username) && !empty($password)) {
            // Cek duplikasi username
            $stmtCek = $pdo->prepare("SELECT id_petugas FROM petugas WHERE username = ?");
            $stmtCek->execute([$username]);
            if ($stmtCek->rowCount() > 0) {
                set_flash('warning', 'Username sudah digunakan, silakan pilih username lain.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO petugas (username, password, nama_petugas, level) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $nama, $level]);
                set_flash('success', "Petugas baru <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.");
            }
        } else {
            set_flash('danger', 'Harap isi semua kolom wajib.');
        }
    } elseif ($action === 'edit') {
        $id_petugas = (int)$_POST['id_petugas'];
        $password = trim($_POST['password'] ?? '');

        if (!empty($nama) && !empty($username)) {
            if (!empty($password)) {
                // Ganti password
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE petugas SET username = ?, password = ?, nama_petugas = ?, level = ? WHERE id_petugas = ?");
                $stmt->execute([$username, $hash, $nama, $level, $id_petugas]);
            } else {
                // Tanpa ganti password
                $stmt = $pdo->prepare("UPDATE petugas SET username = ?, nama_petugas = ?, level = ? WHERE id_petugas = ?");
                $stmt->execute([$username, $nama, $level, $id_petugas]);
            }
            set_flash('success', "Data petugas <strong>" . htmlspecialchars($nama) . "</strong> berhasil diperbarui.");
        }
    }
    header("Location: petugas.php");
    exit;
}

// PROSES HAPUS
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $id_hapus = (int)$_GET['id'];
    if ($id_hapus === (int)$_SESSION['user_id']) {
        set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk!');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM petugas WHERE id_petugas = ?");
            $stmt->execute([$id_hapus]);
            set_flash('success', 'Petugas berhasil dihapus.');
        } catch (Exception $e) {
            set_flash('danger', 'Petugas tidak dapat dihapus karena tercatat dalam riwayat transaksi pembayaran.');
        }
    }
    header("Location: petugas.php");
    exit;
}

// AMBIL DAFTAR PETUGAS
$daftar_petugas = $pdo->query("SELECT * FROM petugas ORDER BY level ASC, nama_petugas ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <!-- FORM TAMBAH PETUGAS -->
    <div class="col-lg-4">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah Petugas Baru</h6>
            <form method="POST" action="petugas.php">
                <input type="hidden" name="action" value="tambah">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap Petugas</label>
                    <input type="text" name="nama_petugas" class="form-control" placeholder="Nama Petugas" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username Login</label>
                    <input type="text" name="username" class="form-control" placeholder="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Hak Akses / Level</label>
                    <select name="level" class="form-select" required>
                        <option value="petugas" selected>Petugas (Kasir Transaksi SPP)</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                        <option value="kepala_sekolah">Kepala Sekolah (Laporan & Monitoring)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-person-check me-1"></i> Simpan Petugas
                </button>
            </form>
        </div>
    </div>

    <!-- TABEL DAFTAR PETUGAS -->
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-people-fill me-2 text-primary"></i>Daftar Petugas Sistem</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th>Nama Petugas</th>
                            <th>Username</th>
                            <th>Level / Role</th>
                            <th>Terdaftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_petugas as $p): ?>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <?= htmlspecialchars($p['nama_petugas']) ?>
                                    <?php if ($p['id_petugas'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-light text-primary border ms-1">Saya</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($p['username']) ?></code></td>
                                <td>
                                    <?php if ($p['level'] === 'admin'): ?>
                                        <span class="badge bg-primary text-uppercase">Admin</span>
                                    <?php elseif ($p['level'] === 'petugas'): ?>
                                        <span class="badge bg-info-subtle text-info text-uppercase">Petugas</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-uppercase">Kepsek</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= tgl_indo($p['created_at']) ?></td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-edit-petugas"
                                                data-id="<?= $p['id_petugas'] ?>"
                                                data-nama="<?= htmlspecialchars($p['nama_petugas']) ?>"
                                                data-user="<?= htmlspecialchars($p['username']) ?>"
                                                data-level="<?= htmlspecialchars($p['level']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($p['id_petugas'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalConfirmDelete" 
                                                    data-href="petugas.php?action=hapus&id=<?= $p['id_petugas'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDIT PETUGAS -->
<div class="modal fade" id="modalEditPetugas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="petugas.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_petugas" id="edit_id_petugas">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Petugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap</label>
                    <input type="text" name="nama_petugas" id="edit_nama_petugas" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="form-control" placeholder="Isi untuk ganti password">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Hak Akses / Level</label>
                    <select name="level" id="edit_level" class="form-select" required>
                        <option value="petugas">Petugas (Kasir SPP)</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                        <option value="kepala_sekolah">Kepala Sekolah (Monitoring)</option>
                    </select>
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
document.querySelectorAll('.btn-edit-petugas').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id_petugas').value = this.getAttribute('data-id');
        document.getElementById('edit_nama_petugas').value = this.getAttribute('data-nama');
        document.getElementById('edit_username').value = this.getAttribute('data-user');
        document.getElementById('edit_level').value = this.getAttribute('data-level');

        const modal = new bootstrap.Modal(document.getElementById('modalEditPetugas'));
        modal.show();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
