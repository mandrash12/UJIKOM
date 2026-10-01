<?php
require_once __DIR__ . '/koneksi.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe = $_POST['tipe_login'] ?? 'petugas';

    if ($tipe === 'petugas') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($username) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM petugas WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Verifikasi password 
                $valid = password_verify($password, $user['password']) || ($password === $user['password']) || ($password === 'admin123' && $username === 'admin');
                if ($valid) {
                    $_SESSION['user_id'] = $user['id_petugas'];
                    $_SESSION['user_nama'] = $user['nama_petugas'];
                    $_SESSION['user_level'] = $user['level'];
                    $_SESSION['role'] = $user['level'];

                    set_flash('success', "Selamat datang kembali, <strong>" . htmlspecialchars($user['nama_petugas']) . "</strong>!");
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Password yang Anda masukkan salah.';
                }
            } else {
                $error = 'Username tidak ditemukan.';
            }
        } else {
            $error = 'Silakan isi username dan password.';
        }
    } elseif ($tipe === 'siswa') {
        $nisn = trim($_POST['nisn'] ?? '');

        if (!empty($nisn)) {
            $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.id_kelas = k.id_kelas WHERE s.nisn = ? LIMIT 1");
            $stmt->execute([$nisn]);
            $siswa = $stmt->fetch();

            if ($siswa) {
                $_SESSION['siswa_nisn'] = $siswa['nisn'];
                $_SESSION['siswa_nama'] = $siswa['nama'];
                $_SESSION['siswa_kelas'] = $siswa['nama_kelas'];
                $_SESSION['role'] = 'siswa';

                set_flash('success', "Selamat datang, <strong>" . htmlspecialchars($siswa['nama']) . "</strong>!");
                header('Location: transaksi.php');
                exit;
            } else {
                $error = 'NISN tidak terdaftar di sistem.';
            }
        } else {
            $error = 'Silakan masukkan nomor NISN Anda.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pembayaran SPP Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .login-header {
            background: #2563eb;
            color: #ffffff;
            padding: 2.25rem 2rem 1.5rem;
            text-align: center;
        }
        .nav-pills .nav-link {
            color: #64748b;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }
        .nav-pills .nav-link.active {
            background-color: #2563eb;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 shadow-sm mb-3" style="width: 58px; height: 58px;">
            <i class="bi bi-wallet2 fs-3"></i>
        </div>
        <h4 class="fw-bold mb-1">SPP-Pay Sekolah</h4>
        <p class="text-white-50 small mb-0">Portal Pembayaran SPP Online & Administrasi</p>
    </div>

    <div class="p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 rounded-3 border-0 d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- TAB NAVIGATION -->
        <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded-3" id="loginTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active small" id="petugas-tab" data-bs-toggle="pill" data-bs-target="#tab-petugas" type="button">
                    <i class="bi bi-person-badge me-1"></i> Petugas / Admin
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link small" id="siswa-tab" data-bs-toggle="pill" data-bs-target="#tab-siswa" type="button">
                    <i class="bi bi-mortarboard me-1"></i> Siswa
                </button>
            </li>
        </ul>

        <div class="tab-content" id="loginTabsContent">
            <!-- TAB 1: LOGIN PETUGAS / ADMIN -->
            <div class="tab-pane fade show active" id="tab-petugas" role="tabpanel">
                <form method="POST" action="login.php">
                    <input type="hidden" name="tipe_login" value="petugas">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" placeholder="admin" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mt-2 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Petugas
                    </button>
                </form>
            </div>

            <!-- TAB 2: LOGIN SISWA (NISN) -->
            <div class="tab-pane fade" id="tab-siswa" role="tabpanel">
                <form method="POST" action="login.php">
                    <input type="hidden" name="tipe_login" value="siswa">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nomor Induk Siswa Nasional (NISN)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-heading text-muted"></i></span>
                            <input type="text" name="nisn" class="form-control border-start-0" placeholder="Contoh: 0061234567" required>
                        </div>
                        <div class="form-text small">Masukkan 10 digit NISN yang terdaftar di sekolah.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mt-2 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Cek Tagihan Saya
                    </button>
                </form>
            </div>
        </div>

        <!-- DEMO INFO HINT -->
        <div class="mt-4 p-3 bg-light rounded-3 border">
            <div class="small fw-bold text-secondary mb-1"><i class="bi bi-info-circle me-1"></i> Akun Demo Tersedia:</div>
            <ul class="list-unstyled mb-0 small text-muted" style="font-size: 0.8rem;">
                <li>&bull; <strong>Admin:</strong> user: <code>admin</code> | pass: <code>admin123</code> atau <code>password123</code></li>
                <li>&bull; <strong>Petugas:</strong> user: <code>petugas1</code> | pass: <code>password123</code></li>
                <li>&bull; <strong>Siswa (NISN):</strong> <code>0061234567</code> (Ahmad Farhan)</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
