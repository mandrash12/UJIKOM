<?php
require_once __DIR__ . '/../koneksi.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user_nama'] ?? $_SESSION['siswa_nama'] ?? 'Pengguna';
$userRole = $_SESSION['user_level'] ?? $_SESSION['role'] ?? 'guest';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>SPP-Pay Sekolah</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #0f172a;
            color: #94a3b8;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }

        #sidebar .sidebar-brand {
            padding: 1.5rem 1.25rem;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }

        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 0.2rem 0.75rem;
        }

        #sidebar .nav-link i {
            font-size: 1.15rem;
        }

        #sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.08);
        }

        #sidebar .nav-link.active {
            color: #ffffff;
            background: var(--primary-color);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        #sidebar .menu-header {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 1rem 1.5rem 0.4rem;
            font-weight: 700;
        }

        /* Main Content Layout */
        #main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Top Header Navbar */
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 0.85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* Modern Cards */
        .card-custom {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card {
            border: 1px solid var(--card-border);
            border-radius: 14px;
            background: #ffffff;
            padding: 1.25rem;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.04);
        }

        /* Badges */
        .badge-lunas {
            background-color: #dcfce7;
            color: #166534;
            font-weight: 600;
        }
        .badge-belum {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 600;
        }

        /* Responsive Mobile Sidebar */
        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            #sidebar.show {
                margin-left: 0;
            }
            #main-wrapper {
                margin-left: 0;
            }
        }

        /* Printable styles */
        @media print {
            #sidebar, .top-navbar, .no-print, .btn, .alert {
                display: none !important;
            }
            #main-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div id="main-wrapper">
        <!-- TOPBAR -->
        <header class="top-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light border d-lg-none" id="btnToggleSidebar" type="button">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="fw-semibold text-secondary d-none d-sm-block">
                    <?= isset($pageHeader) ? htmlspecialchars($pageHeader) : 'Sistem Pembayaran SPP' ?>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-3 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <div class="text-start d-none d-md-block">
                            <div class="fw-semibold small leading-none"><?= htmlspecialchars($userName) ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= strtoupper(htmlspecialchars($userRole)) ?></div>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li><h6 class="dropdown-header">Akun: <?= htmlspecialchars($userName) ?></h6></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- FLASH NOTIFICATION -->
        <div class="container-fluid px-4 pt-3">
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill fs-5"></i>
                        <?php endif; ?>
                        <div><?= $flash['message'] ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <main class="container-fluid px-4 py-3 flex-grow-1">
