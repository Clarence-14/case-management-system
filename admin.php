<?php
$pageTitle = 'Officer Dashboard | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';
requireOfficerLogin();
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

// Fetch KPIs
$totalComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$urgentFlags = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE ai_urgency_score >= 80 AND status NOT IN ('Resolved', 'Dismissed')")->fetchColumn();
$pendingReview = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Received', 'Preliminary Triage')")->fetchColumn();
$assignedInvestigators = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Under Investigation', 'Disciplinary Committee')")->fetchColumn();
$resolvedCount = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();

// Detect Repeat Offenders (Advocate name or LSK number appearing > 1 time)
$repeatAdvocates = [];
$repStmt = $pdo->query("SELECT LOWER(TRIM(advocate_name)) as adv, COUNT(*) as cnt FROM complaints GROUP BY LOWER(TRIM(advocate_name)) HAVING cnt > 1");
foreach ($repStmt->fetchAll() as $r) {
    $repeatAdvocates[$r['adv']] = $r['cnt'];
}

// Filters
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM complaints WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (ref_number LIKE ? OR advocate_name LIKE ? OR lsk_number LIKE ? OR category LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($statusFilter !== '') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY ai_urgency_score DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <aside class="col-lg-2 d-none d-lg-block admin-sidebar p-3">
            <div class="small text-uppercase text-white-50 fw-bold mb-3 px-2">Commission Desk</div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="admin.php"><i class="bi bi-inbox-fill me-2"></i>Case Queue</a>
                <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart-line me-2"></i>Integrity Analytics</a>
                <a class="nav-link" href="index.php"><i class="bi bi-globe me-2"></i>Public Portal</a>
                <a class="nav-link" href="track.php"><i class="bi bi-search me-2"></i>Case Tracker</a>
                <hr class="border-secondary my-3">
                <a class="nav-link text-warning" href="setup.php?reset=1" onclick="return confirm('Reset database to the 8 demo cases?')">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Demo Data
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="col-lg-10 p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h1 class="h3 fw-bold mb-0">ACC Officer &amp; Commissioner Dashboard</h1>
                    <p class="text-muted small mb-0">AI-prioritized complaint triage &amp; repeat-offender monitoring</p>
                </div>
                <a href="index.php" class="btn btn-acc-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>New Complaint
                </a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'reset_ok'): ?>
                <div class="alert alert-success alert-dismissible fade show small" role="alert">
                    Database reset to the 8 realistic Kenyan demo cases.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 5 KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl">
                    <div class="acc-card p-3">
                        <div class="small text-muted fw-semibold">Total Complaints</div>
                        <div class="fs-2 fw-bold text-dark"><?= $totalComplaints ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="acc-card p-3 border-danger-subtle">
                        <div class="small text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent Flags</div>
                        <div class="fs-2 fw-bold text-danger"><?= $urgentFlags ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="acc-card p-3">
                        <div class="small text-muted fw-semibold">Pending Review</div>
                        <div class="fs-2 fw-bold text-warning-emphasis"><?= $pendingReview ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl">
                    <div class="acc-card p-3">
                        <div class="small text-muted fw-semibold">Under Investigation</div>
                        <div class="fs-2 fw-bold text-primary"><?= $assignedInvestigators ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl">
                    <div class="acc-card p-3 border-success-subtle">
                        <div class="small text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Resolved</div>
                        <div class="fs-2 fw-bold text-success"><?= $resolvedCount ?></div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <div class="acc-card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Reference (ACC-2026-...), Advocate, LSK No, or Category...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (CASE_STATUSES as $st): ?>
                                <option value="<?= htmlspecialchars($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-acc-primary w-100">Filter</button>
                        <?php if ($search || $statusFilter): ?>
                            <a href="admin.php" class="btn btn-outline-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Case Queue Table -->
            <div class="acc-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th>Ref ID</th>
                                <th>Advocate &amp; LSK No.</th>
                                <th>AI Category</th>
                                <th>AI Urgency</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cases)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No complaints match your filter.</td></tr>
                            <?php else: ?>
                                <?php foreach ($cases as $c): ?>
                                    <?php
                                    $isRepeat = isset($repeatAdvocates[strtolower(trim($c['advocate_name']))]);
                                    $score = (int)$c['ai_urgency_score'];
                                    $scoreClass = $score >= 80 ? 'badge-urgent' : ($score >= 60 ? 'badge-high' : ($score >= 40 ? 'badge-medium' : 'badge-low'));
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="case_view.php?id=<?= $c['id'] ?>" class="case-ref fw-bold text-decoration-none text-dark">
                                                <?= htmlspecialchars($c['ref_number']) ?>
                                            </a>
                                            <div class="small text-muted"><?= htmlspecialchars($c['county']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($c['advocate_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($c['lsk_number'] ?: 'LSK N/A') ?></div>
                                            <?php if ($isRepeat): ?>
                                                <span class="badge badge-urgent mt-1">
                                                    <i class="bi bi-flag-fill me-1"></i>Repeat-Offender Flag (<?= $repeatAdvocates[strtolower(trim($c['advocate_name']))] ?> cases)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="small fw-medium"><?= htmlspecialchars($c['ai_category'] ?: $c['category']) ?></span>
                                            <?php if ($c['amount_involved'] > 0): ?>
                                                <div class="small text-muted">KES <?= number_format($c['amount_involved']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $scoreClass ?> px-2 py-1"><?= $score ?> / 100</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $c['status'] === 'Resolved' ? 'badge-resolved' : 'bg-light text-dark border' ?>">
                                                <?= htmlspecialchars($c['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="case_view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-acc-primary">
                                                <i class="bi bi-folder2-open me-1"></i>Workbench
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
