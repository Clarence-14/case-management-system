<?php
$pageTitle = 'Track Your Case | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';
requireLogin('login.php?redirect=track.php');
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$ref = trim($_GET['ref'] ?? '');
$case = null;
$notes = [];
$searched = !empty($ref);

if ($searched) {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE UPPER(ref_number) = UPPER(?)");
    $stmt->execute([$ref]);
    $case = $stmt->fetch();

    if ($case) {
        $nStmt = $pdo->prepare("SELECT * FROM case_notes WHERE complaint_id = ? AND note_type IN ('Status Change', 'Step Completed') ORDER BY created_at ASC");
        $nStmt->execute([$case['id']]);
        $notes = $nStmt->fetchAll();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container" style="max-width: 860px;">
        <div class="text-center mb-4">
            <span class="badge acc-badge-gold px-3 py-2 rounded-1 mb-2">AUTHENTICATED CASE TRACKER</span>
            <h1 class="h2 fw-bold">Track Complaint Progress</h1>
            <p class="text-muted">Enter an ACC Reference Number to view real-time workflow milestones and status updates.</p>
        </div>

        <!-- Search Box -->
        <div class="acc-card p-4 mb-4">
            <form method="GET" action="track.php" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control" name="ref" value="<?= htmlspecialchars($ref) ?>" placeholder="Enter Reference Number (e.g. ACC-2026-1001)" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-acc-primary btn-lg w-100">Track Case</button>
                </div>
            </form>
            <div class="mt-3 small text-muted">
                Demo References:
                <a href="track.php?ref=ACC-2026-1001" class="badge bg-light text-dark border text-decoration-none me-1">ACC-2026-1001</a>
                <a href="track.php?ref=ACC-2026-1003" class="badge bg-light text-dark border text-decoration-none me-1">ACC-2026-1003</a>
                <a href="track.php?ref=ACC-2026-1005" class="badge bg-light text-dark border text-decoration-none">ACC-2026-1005</a>
            </div>
        </div>

        <?php if ($searched && !$case): ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>No complaint found with reference number <strong><?= htmlspecialchars($ref) ?></strong>. Please verify and try again.</div>
            </div>
        <?php elseif ($case): ?>
            <?php
            $stages = ['Received', 'Preliminary Triage', 'Under Investigation', 'Disciplinary Committee', 'Resolved'];
            $currentIndex = array_search($case['status'], $stages);
            if ($currentIndex === false) $currentIndex = 0;
            ?>
            <div class="acc-card p-4 p-md-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pb-3 mb-4 border-bottom">
                    <div>
                        <span class="small text-muted text-uppercase fw-bold">Case Reference</span>
                        <h2 class="case-ref h3 mb-1"><?= htmlspecialchars($case['ref_number']) ?></h2>
                        <p class="text-muted small mb-0">
                            Against: <strong><?= htmlspecialchars($case['advocate_name']) ?></strong>
                            <?php if (!empty($case['lsk_number'])): ?>
                                (<?= htmlspecialchars($case['lsk_number']) ?>)
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-md-end">
                        <span class="small text-muted d-block">Current Status</span>
                        <span class="badge <?= $case['status'] === 'Resolved' ? 'badge-resolved' : 'bg-dark text-warning' ?> fs-6 px-3 py-2 mt-1">
                            <?= htmlspecialchars($case['status']) ?>
                        </span>
                    </div>
                </div>

                <!-- Visual Progress Stepper -->
                <div class="mb-5">
                    <div class="d-flex justify-content-between position-relative mb-2">
                        <?php foreach ($stages as $idx => $stage): ?>
                            <?php $done = $idx <= $currentIndex; ?>
                            <div class="text-center flex-fill">
                                <div class="mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center fw-bold small"
                                     style="width: 32px; height: 32px; background: <?= $done ? 'var(--acc-navy)' : '#E2E8F0' ?>; color: <?= $done ? 'var(--acc-gold)' : '#64748B' ?>;">
                                    <?= $done ? '<i class="bi bi-check-lg"></i>' : ($idx + 1) ?>
                                </div>
                                <div class="small <?= $done ? 'fw-bold text-dark' : 'text-muted' ?>" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($stage) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Case Details -->
                <div class="row g-3 mb-4 bg-light p-3 rounded-3">
                    <div class="col-md-4">
                        <span class="small text-muted d-block">Category</span>
                        <strong class="small"><?= htmlspecialchars($case['category']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="small text-muted d-block">Assigned Officer</span>
                        <strong class="small"><?= htmlspecialchars($case['assigned_officer']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="small text-muted d-block">Date Lodged</span>
                        <strong class="small"><?= htmlspecialchars(date('d M Y', strtotime($case['created_at']))) ?></strong>
                    </div>
                </div>

                <!-- Timeline -->
                <h5 class="fw-bold mb-3">Case Activity Timeline</h5>
                <div class="acc-timeline">
                    <?php foreach ($notes as $n): ?>
                        <div class="acc-timeline-item">
                            <span class="acc-timeline-dot"></span>
                            <div class="small text-muted mb-1"><?= htmlspecialchars(date('d M Y, H:i', strtotime($n['created_at']))) ?></div>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($n['content']) ?></div>
                            <div class="small text-muted">Updated by: <?= htmlspecialchars($n['author']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
