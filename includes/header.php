<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <small class="d-block text-white-50" style="font-family: 'DM Sans', sans-serif; font-size: 0.68rem; font-weight: 500;">Advocates Complaints Commission</small>
            </div>
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#accNav">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="accNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <!-- Available to everyone -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-file-earmark-plus me-1"></i>Lodge Complaint
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'track.php' ? 'active' : '' ?>" href="track.php">
                        <i class="bi bi-search me-1"></i>Track Case
                    </a>
                </li>

                <!-- Admin tab: only shown AND enforced server-side for ACC staff -->
                <?php if (isAccStaff()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['admin.php', 'case_view.php', 'analytics.php']) ? 'active' : '' ?>" href="admin.php">
                        <i class="bi bi-shield-lock me-1"></i>Admin
                    </a>
                </li>
                <?php endif; ?>

                <!-- Auth state -->
                <?php if (isUserLoggedIn()): ?>
                <li class="nav-item ms-lg-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-md-inline"><?= htmlspecialchars($user['name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="px-3 py-2 small">
                                    <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($user['role']) ?></div>
                                    <?php if (!empty($user['lsk_number'])): ?>
                                        <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($user['lsk_number']) ?></div>
                                    <?php endif; ?>
                                    <div class="mt-1">
                                        <?php if ($user['is_acc_staff']): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-shield-check me-1"></i>ACC Staff</span>
                                        <?php elseif (!empty($user['lsk_number'])): ?>
                                            <span class="badge bg-secondary">LSK Member</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Citizen</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                </li>
                <?php else: ?>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-acc-gold" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3 no-print" style="z-index: 1080;"></div>
