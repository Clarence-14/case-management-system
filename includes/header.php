<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <!-- Google Fonts: Cormorant Garamond & DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg acc-navbar sticky-top no-print">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-bank2 text-warning fs-4"></i>
                <div>
                    <span class="d-block lh-1" style="font-size: 1.05rem;">ACC Kenya</span>
                    <small class="d-block text-white-50" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.68rem; font-weight: 500;">Advocates Complaints Commission</small>
                </div>
            </a>
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#accNav">
                <i class="bi bi-list fs-2"></i>
            </button>
            <div class="collapse navbar-collapse" id="accNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-file-earmark-plus me-1"></i> Lodge Complaint
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'track.php' ? 'active' : '' ?>" href="track.php">
                            <i class="bi bi-search me-1"></i> Track Case
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'admin.php' || $currentPage === 'case_view.php' ? 'active' : '' ?>" href="admin.php">
                            <i class="bi bi-shield-lock me-1"></i> Officer Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                            <i class="bi bi-bar-chart-line me-1"></i> Integrity Analytics
                        </a>
                    </li>
                    <?php if (isOfficerLoggedIn()): ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-sm btn-outline-warning" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3 no-print" style="z-index: 1080;"></div>