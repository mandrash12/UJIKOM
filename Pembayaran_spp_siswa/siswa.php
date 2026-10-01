<?php
require_once __DIR__ . '/koneksi.php';
require_admin();

$pageTitle = 'Data Siswa';
$pageHeader = 'Manajemen Data Siswa';

// Ambil kelas & tarif spp untuk dropdown
$daftar_kelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC, nama_kelas ASC")->fetchAll();
$daftar_spp = $pdo->query("
    SELECT ts.*, ts.tahun AS tahun_ajaran 
    FROM tarif_spp ts 
    ORDER BY ts.tahun DESC
")->fetchAll();

// PROSES TAMBAH / EDIT / HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $nisn = trim($_POST['nisn'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $id_kelas = (int)($_POST['id_kelas'] ?? 0);
        $id_spp = (int)($_POST['id_spp'] ?? 0);
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $nama_wali = trim($_POST['nama_wali'] ?? '');
        $no_telp_wali = trim($_POST['no_telp_wali'] ?? '');
        $status_siswa = $_POST['status_siswa'] ?? 'aktif';

        if (empty($nisn) || empty($nis) || empty($nama) || $id_kelas <= 0 || $id_spp <= 0) {
            set_flash('danger', 'Harap lengkapi semua kolom wajib (NISN, NIS, Nama, Kelas, Tarif SPP).');
        } else {
            try {
                // Cek duplikasi NISN / NIS
                $stmtCek = $pdo->prepare("SELECT nisn FROM siswa WHERE nisn = ? OR nis = ?");
                $stmtCek->execute([$nisn, $nis]);
                if ($stmtCek->rowCount() > 0) {
                    set_flash('warning', 'NISN atau NIS sudah terdaftar untuk siswa lain.');
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO siswa (nisn, nis, nama, jenis_kelamin, id_kelas, id_spp, alamat, no_telp, nama_wali, no_telp_wali, status_siswa)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nisn, $nis, $nama, $jk, $id_kelas, $id_spp, $alamat, $no_telp, $nama_wali, $no_telp_wali, $status_siswa]);
                    set_flash('success', "Data siswa <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.");
                }
            } catch (Exception $e) {
                set_flash('danger', 'Terjadi kesalahan database: ' . $e->getMessage());
            }
        }
        header("Location: siswa.php");
        exit;
    } elseif ($action === 'edit') {
        $nisn_lama = trim($_POST['nisn_lama'] ?? $_POST['nisn'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $jk = $_POST['jenis_kelamin'] ?? 'L';
        $id_kelas = (int)($_POST['id_kelas'] ?? 0);
        $id_spp = (int)($_POST['id_spp'] ?? 0);
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $nama_wali = trim($_POST['nama_wali'] ?? '');
        $no_telp_wali = trim($_POST['no_telp_wali'] ?? '');
        $status_siswa = $_POST['status_siswa'] ?? 'aktif';

        if (empty($nisn_lama) || empty($nis) || empty($nama) || $id_kelas <= 0 || $id_spp <= 0) {
            set_flash('danger', 'Harap lengkapi semua kolom wajib saat mengedit siswa (NIS, Nama, Kelas, Tarif SPP).');
        } else {
            try {
                // Cek apakah NIS digunakan oleh siswa lain
                $stmtCek = $pdo->prepare("SELECT nisn FROM siswa WHERE nis = ? AND nisn != ?");
                $stmtCek->execute([$nis, $nisn_lama]);
                if ($stmtCek->rowCount() > 0) {
                    set_flash('warning', "NIS <strong>" . htmlspecialchars($nis) . "</strong> sudah digunakan oleh siswa lain.");
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE siswa 
                        SET nis = ?, nama = ?, jenis_kelamin = ?, id_kelas = ?, id_spp = ?, alamat = ?, no_telp = ?, nama_wali = ?, no_telp_wali = ?, status_siswa = ?
                        WHERE nisn = ?
                    ");
                    $stmt->execute([$nis, $nama, $jk, $id_kelas, $id_spp, $alamat, $no_telp, $nama_wali, $no_telp_wali, $status_siswa, $nisn_lama]);
                    set_flash('success', "Perubahan data siswa <strong>" . htmlspecialchars($nama) . "</strong> berhasil disimpan.");
                }
            } catch (Exception $e) {
                set_flash('danger', 'Gagal memperbarui data siswa: ' . $e->getMessage());
            }
        }
        header("Location: siswa.php");
        exit;
    }
}

// PROSES HAPUS (VIA GET)
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $nisn_hapus = $_GET['nisn'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM siswa WHERE nisn = ?");
        $stmt->execute([$nisn_hapus]);
        set_flash('success', 'Data siswa berhasil dihapus.');
    } catch (Exception $e) {
        set_flash('danger', 'Gagal menghapus siswa karena memiliki riwayat tagihan/transaksi.');
    }
    header("Location: siswa.php");
    exit;
}

// FILTER & PENCARIAN
$search = trim($_GET['q'] ?? '');
$filter_kelas = (int)($_GET['kelas'] ?? 0);

$query = "
    SELECT s.*, k.nama_kelas, ts.nominal AS tarif_spp, ts.tahun AS tahun_ajaran
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN tarif_spp ts ON s.id_spp = ts.id_spp
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.nisn LIKE ? OR s.nis LIKE ? OR s.nama LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($filter_kelas > 0) {
    $query .= " AND s.id_kelas = ?";
    $params[] = $filter_kelas;
}

$query .= " ORDER BY s.nama ASC";
$stmtSiswa = $pdo->prepare($query);
$stmtSiswa->execute($params);
$daftar_siswa = $stmtSiswa->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">Daftar Seluruh Siswa</h5>
            <span class="text-muted small">Kelola data siswa, tingkat kelas, dan penetapan tarif SPP</span>
        </div>
        <button type="button" class="btn btn-primary px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Siswa Baru
        </button>
    </div>

    <!-- FILTER & PENCARIAN -->
    <form method="GET" action="siswa.php" class="row g-2 mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Cari NISN, NIS, atau Nama Siswa..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="kelas" class="form-select" onchange="this.form.submit()">
                <option value="0">-- Semua Kelas --</option>
                <?php foreach ($daftar_kelas as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= ($filter_kelas == $k['id_kelas']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100">Filter</button>
            <?php if (!empty($search) || $filter_kelas > 0): ?>
                <a href="siswa.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TABEL DATA SISWA -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase text-muted">
                <tr>
                    <th>NISN / NIS</th>
                    <th>Nama Lengkap</th>
                    <th>L/P</th>
                    <th>Kelas</th>
                    <th>Tarif SPP</th>
                    <th>No. Telepon / Wali</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($daftar_siswa)): ?>
                    <?php foreach ($daftar_siswa as $sw): ?>
                        <tr>
                            <td>
                                <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($sw['nisn']) ?></div>
                                <div class="text-muted small">NIS: <?= htmlspecialchars($sw['nis']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($sw['nama']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($sw['alamat'] ?? '-') ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $sw['jenis_kelamin'] ?></span></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($sw['nama_kelas']) ?></span></td>
                            <td>
                                <div class="fw-bold text-success"><?= rupiah($sw['tarif_spp']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($sw['tahun_ajaran']) ?></div>
                            </td>
                            <td class="small">
                                <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($sw['no_telp'] ?: '-') ?></div>
                                <div class="text-muted">Wali: <?= htmlspecialchars($sw['nama_wali'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge <?= ($sw['status_siswa'] === 'aktif') ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                    <?= htmlspecialchars($sw['status_siswa']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="transaksi.php?cari=<?= urlencode($sw['nisn']) ?>" class="btn btn-sm btn-outline-success" title="Cek Tagihan & Bayar">
                                        <i class="bi bi-wallet2"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-warning btn-edit-siswa"
                                            data-nisn="<?= htmlspecialchars($sw['nisn']) ?>"
                                            data-nis="<?= htmlspecialchars($sw['nis']) ?>"
                                            data-nama="<?= htmlspecialchars($sw['nama']) ?>"
                                            data-jk="<?= htmlspecialchars($sw['jenis_kelamin']) ?>"
                                            data-kelas="<?= $sw['id_kelas'] ?>"
                                            data-spp="<?= $sw['id_spp'] ?>"
                                            data-alamat="<?= htmlspecialchars($sw['alamat'] ?? '') ?>"
                                            data-telp="<?= htmlspecialchars($sw['no_telp'] ?? '') ?>"
                                            data-wali="<?= htmlspecialchars($sw['nama_wali'] ?? '') ?>"
                                            data-telpwali="<?= htmlspecialchars($sw['no_telp_wali'] ?? '') ?>"
                                            data-status="<?= htmlspecialchars($sw['status_siswa']) ?>"
                                            title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalConfirmDelete" 
                                            data-href="siswa.php?action=hapus&nisn=<?= urlencode($sw['nisn']) ?>"
                                            title="Hapus Data">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada data siswa yang cocok.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH SISWA -->
<div class="modal fade" id="modalTambahSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="siswa.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Tambah Siswa Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">NISN (10 Digit) <span class="text-danger">*</span></label>
                        <input type="text" name="nisn" class="form-control" maxlength="10" placeholder="Contoh: 0061234567" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">NIS (Nomor Induk Siswa) <span class="text-danger">*</span></label>
                        <input type="text" name="nis" class="form-control" maxlength="8" placeholder="Contoh: 25261001" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama lengkap siswa" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kelas <span class="text-danger">*</span></label>
                        <select name="id_kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($daftar_kelas as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tarif SPP & Angkatan <span class="text-danger">*</span></label>
                        <select name="id_spp" class="form-select" required>
                            <option value="">-- Pilih Tarif SPP --</option>
                            <?php foreach ($daftar_spp as $sp): ?>
                                <option value="<?= $sp['id_spp'] ?>">
                                    <?= htmlspecialchars($sp['tahun_ajaran']) ?> &mdash; <?= rupiah($sp['nominal']) ?> (<?= htmlspecialchars($sp['keterangan'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat Tempat Tinggal</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap siswa..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Telepon Siswa / WA</label>
                        <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Orang Tua / Wali</label>
                        <input type="text" name="nama_wali" class="form-control" placeholder="Nama ayah/ibu/wali">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">No. Telp Orang Tua / Wali</label>
                        <input type="text" name="no_telp_wali" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status Siswa</label>
                        <select name="status_siswa" class="form-select">
                            <option value="aktif" selected>Aktif</option>
                            <option value="lulus">Lulus</option>
                            <option value="pindah">Pindah</option>
                            <option value="keluar">Keluar</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Siswa</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT SISWA -->
<div class="modal fade" id="modalEditSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="siswa.php" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="nisn_lama" id="edit_nisn_lama">
            <input type="hidden" name="nisn" id="edit_nisn_hidden">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">NISN (Tidak dapat diubah)</label>
                        <input type="text" id="edit_nisn_display" class="form-control bg-light" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">NIS</label>
                        <input type="text" name="nis" id="edit_nis" class="form-control" maxlength="8" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nama Lengkap Siswa</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="edit_jk" class="form-select" required>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kelas</label>
                        <select name="id_kelas" id="edit_kelas" class="form-select" required>
                            <?php foreach ($daftar_kelas as $k): ?>
                                <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tarif SPP & Angkatan</label>
                        <select name="id_spp" id="edit_spp" class="form-select" required>
                            <?php foreach ($daftar_spp as $sp): ?>
                                <option value="<?= $sp['id_spp'] ?>">
                                    <?= htmlspecialchars($sp['tahun_ajaran']) ?> &mdash; <?= rupiah($sp['nominal']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">No. Telepon Siswa</label>
                        <input type="text" name="no_telp" id="edit_telp" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Wali</label>
                        <input type="text" name="nama_wali" id="edit_wali" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">No. Telp Wali</label>
                        <input type="text" name="no_telp_wali" id="edit_telpwali" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status Siswa</label>
                        <select name="status_siswa" id="edit_status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="lulus">Lulus</option>
                            <option value="pindah">Pindah</option>
                            <option value="keluar">Keluar</option>
                        </select>
                    </div>
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
document.querySelectorAll('.btn-edit-siswa').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const nisn = this.getAttribute('data-nisn') || '';
        document.getElementById('edit_nisn_lama').value = nisn;
        document.getElementById('edit_nisn_hidden').value = nisn;
        document.getElementById('edit_nisn_display').value = nisn;
        document.getElementById('edit_nis').value = this.getAttribute('data-nis') || '';
        document.getElementById('edit_nama').value = this.getAttribute('data-nama') || '';
        document.getElementById('edit_jk').value = this.getAttribute('data-jk') || 'L';
        document.getElementById('edit_kelas').value = this.getAttribute('data-kelas') || '';
        document.getElementById('edit_spp').value = this.getAttribute('data-spp') || '';
        document.getElementById('edit_alamat').value = this.getAttribute('data-alamat') || '';
        document.getElementById('edit_telp').value = this.getAttribute('data-telp') || '';
        document.getElementById('edit_wali').value = this.getAttribute('data-wali') || '';
        document.getElementById('edit_telpwali').value = this.getAttribute('data-telpwali') || '';
        document.getElementById('edit_status').value = this.getAttribute('data-status') || 'aktif';

        const modal = new bootstrap.Modal(document.getElementById('modalEditSiswa'));
        modal.show();
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
