<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_level'] ?? $_SESSION['role'] ?? 'guest';
?>
<aside id="sidebar">
    <a href="index.php" class="sidebar-brand">
        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
            <i class="bi bi-wallet2 fs-5"></i>
        </div>
        <div class="d-flex flex-column">
            <span class="lh-1 fw-bold text-white">SPP-PAY</span>
            <span class="text-white-50" style="font-size: 0.7rem; font-weight: 500;">SEKOLAH APP</span>
        </div>
    </a>

    <div class="d-flex flex-column justify-content-between" style="min-height: calc(100vh - 80px);">
        <nav class="nav flex-column py-3">
            <div class="menu-header">Menu Utama</div>
            <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

            <?php if ($role === 'admin' || $role === 'petugas'): ?>
                <div class="menu-header">Transaksi SPP</div>
                <a class="nav-link <?= ($currentPage === 'transaksi.php') ? 'active' : '' ?>" href="transaksi.php">
                    <i class="bi bi-cash-coin"></i>
                    <span>Entri Pembayaran</span>
                </a>
                <a class="nav-link <?= ($currentPage === 'generate_tagihan.php') ? 'active' : '' ?>" href="generate_tagihan.php">
                    <i class="bi bi-calendar-plus"></i>
                    <span>Generate Tagihan</span>
                </a>
                <a class="nav-link <?= ($currentPage === 'laporan.php' || $currentPage === 'cetak_laporan.php') ? 'active' : '' ?>" href="laporan.php">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Laporan Pembayaran</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="menu-header">Data Master</div>
                <a class="nav-link <?= ($currentPage === 'siswa.php') ? 'active' : '' ?>" href="siswa.php">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Siswa</span>
                </a>
                <a class="nav-link <?= ($currentPage === 'kelas.php') ? 'active' : '' ?>" href="kelas.php">
                    <i class="bi bi-building"></i>
                    <span>Data Kelas</span>
                </a>
                <a class="nav-link <?= ($currentPage === 'spp.php') ? 'active' : '' ?>" href="spp.php">
                    <i class="bi bi-tags-fill"></i>
                    <span>Tarif SPP & Tahun</span>
                </a>
                <a class="nav-link <?= ($currentPage === 'petugas.php') ? 'active' : '' ?>" href="petugas.php">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Data Petugas</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'siswa'): ?>
                <div class="menu-header">Portal Siswa</div>
                <a class="nav-link <?= ($currentPage === 'transaksi.php') ? 'active' : '' ?>" href="transaksi.php">
                    <i class="bi bi-receipt"></i>
                    <span>Tagihan & Riwayat Saya</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-3">
            <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-25 text-center">
                <div class="small text-white-50">Masuk sebagai:</div>
                <div class="fw-bold text-light text-truncate"><?= htmlspecialchars($_SESSION['user_nama'] ?? $_SESSION['siswa_nama'] ?? 'Pengguna') ?></div>
                <div class="badge bg-primary text-uppercase mt-1" style="font-size: 0.65rem;"><?= htmlspecialchars($role) ?></div>
                <hr class="border-secondary opacity-25 my-2">
                <a href="logout.php" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </div>
</aside>
