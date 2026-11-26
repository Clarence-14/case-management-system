<?php
$pageTitle = 'Case Workbench | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';
requireOfficerLogin();
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->execute([$id]);
$case = $stmt->fetch();

if (!$case) {
    header('Location: admin.php');
    exit;
}

// Check repeat offender count
$repStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE LOWER(TRIM(advocate_name)) = LOWER(TRIM(?))");
$repStmt->execute([$case['advocate_name']]);
$advocateCaseCount = (int)$repStmt->fetchColumn();

// Fetch notes
$nStmt = $pdo->prepare("SELECT * FROM case_notes WHERE complaint_id = ? ORDER BY created_at DESC");
$nStmt->execute([$id]);
$notes = $nStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <div>
            <a href="admin.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Case Queue</a>
            <h1 class="h2 fw-bold mb-0 mt-1">
                <span class="case-ref"><?= htmlspecialchars($case['ref_number']) ?></span>
                <?php if ($advocateCaseCount > 1): ?>
                    <span class="badge badge-urgent fs-6 align-middle ms-2">
                        <i class="bi bi-flag-fill me-1"></i>Repeat Offender (<?= $advocateCaseCount ?> Complaints)
                    </span>
                <?php endif; ?>
            </h1>
        </div>
        <button type="button" class="btn btn-acc-gold" id="generateBriefBtn">
            <i class="bi bi-stars me-2"></i>Generate AI Case Brief &amp; Notice to Show Cause
        </button>
    </div>

    <div class="row g-4">
        <!-- Left Column: Case Details & AI Workbench -->
        <div class="col-lg-8">
            <!-- Complaint Overview -->
            <div class="acc-card p-4 mb-4 no-print">
                <div class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-md-6">
                        <span class="small text-muted d-block">Respondent Advocate</span>
                        <strong class="fs-5"><?= htmlspecialchars($case['advocate_name']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($case['lsk_number'] ?: 'LSK Number Not Provided') ?></div>
                    </div>
                    <div class="col-md-6">
                        <span class="small text-muted d-block">Complainant</span>
                        <strong><?= htmlspecialchars($case['complainant_name']) ?></strong>
                        <?php if (!$case['is_anonymous']): ?>
                            <div class="small text-muted"><?= htmlspecialchars($case['complainant_email']) ?> &middot; <?= htmlspecialchars($case['complainant_phone']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <span class="small text-muted d-block">Category</span>
                        <span class="fw-semibold"><?= htmlspecialchars($case['category']) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <span class="small text-muted d-block">Amount Involved</span>
                        <span class="fw-semibold"><?= $case['amount_involved'] > 0 ? 'KES ' . number_format($case['amount_involved'], 2) : 'Unspecified' ?></span>
                    </div>
                    <div class="col-sm-4">
                        <span class="small text-muted d-block">AI Urgency Score</span>
                        <span class="badge <?= $case['ai_urgency_score'] >= 80 ? 'badge-urgent' : 'badge-high' ?>"><?= $case['ai_urgency_score'] ?> / 100</span>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase small text-muted mt-4">Citizen Narrative</h6>
                <p class="bg-light p-3 rounded-3 border mb-3"><?= nl2br(htmlspecialchars($case['narrative'])) ?></p>

                <?php if (!empty($case['evidence_path'])): ?>
                    <a href="<?= htmlspecialchars($case['evidence_path']) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-paperclip me-1"></i>View Uploaded Evidence
                    </a>
                <?php endif; ?>
            </div>

            <!-- AI Case Brief & Notice to Show Cause Output -->
            <div id="aiBriefContainer" class="acc-card ai-assistant-card p-4 mb-4 d-none print-only-letter">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom no-print">
                    <h5 class="fw-bold mb-0"><i class="bi bi-cpu me-2 text-warning"></i>AI Legal Brief &amp; Statutory Analysis</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark" id="copyLetterBtn"><i class="bi bi-clipboard me-1"></i>Copy Letter</button>
                        <button type="button" class="btn btn-sm btn-acc-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Notice</button>
                    </div>
                </div>

                <div class="no-print mb-4">
                    <h6 class="fw-bold small text-uppercase text-muted">Executive Summary</h6>
                    <p id="briefSummary" class="mb-3"></p>

                    <h6 class="fw-bold small text-uppercase text-muted">Relevant Sections — Advocates Act (Cap. 16, Laws of Kenya)</h6>
                    <ul id="briefCitations" class="small mb-3"></ul>
                </div>

                <h6 class="fw-bold small text-uppercase text-muted no-print">Draft Notice to Show Cause</h6>
                <pre id="briefLetter" class="bg-white p-4 border rounded-3 mb-0" style="white-space: pre-wrap; font-family: 'Times New Roman', Georgia, serif; font-size: 0.95rem;"></pre>
            </div>
        </div>

        <!-- Right Column: Workflow & Investigation Notes -->
        <div class="col-lg-4 no-print">
            <div class="acc-card p-4 mb-4">
                <h5 class="fw-bold mb-3">Workflow &amp; Assignment</h5>
                <form id="workflowForm">
                    <input type="hidden" name="complaint_id" value="<?= $case['id'] ?>">
                    <input type="hidden" name="action" value="update_workflow">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Case Status</label>
                        <select class="form-select" name="status">
                            <?php foreach (CASE_STATUSES as $st): ?>
                                <option value="<?= htmlspecialchars($st) ?>" <?= $case['status'] === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assigned Legal Officer</label>
                        <select class="form-select" name="assigned_officer">
                            <?php foreach (LEGAL_OFFICERS as $off): ?>
                                <option value="<?= htmlspecialchars($off) ?>" <?= $case['assigned_officer'] === $off ? 'selected' : '' ?>><?= htmlspecialchars($off) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-acc-primary w-100">Update Workflow</button>
                </form>
            </div>

            <div class="acc-card p-4">
                <h5 class="fw-bold mb-3">Investigation Notes</h5>
                <form id="noteForm" class="mb-4">
                    <input type="hidden" name="complaint_id" value="<?= $case['id'] ?>">
                    <input type="hidden" name="action" value="add_note">
                    <textarea class="form-control mb-2" name="content" rows="3" placeholder="Add internal investigation note..." required></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-dark w-100">Add Note</button>
                </form>

                <div class="acc-timeline">
                    <?php foreach ($notes as $n): ?>
                        <div class="acc-timeline-item">
                            <span class="acc-timeline-dot"></span>
                            <div class="small text-muted"><?= htmlspecialchars(date('d M Y, H:i', strtotime($n['created_at']))) ?></div>
                            <div class="small fw-semibold"><?= htmlspecialchars($n['content']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($n['author']) ?> (<?= htmlspecialchars($n['note_type']) ?>)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const generateBtn = document.getElementById('generateBriefBtn');
    generateBtn.addEventListener('click', async () => {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating Brief...';

        try {
            const res = await fetch('api/ai_analyze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'case_brief',
                    ref_number: <?= json_encode($case['ref_number']) ?>,
                    advocate_name: <?= json_encode($case['advocate_name']) ?>,
                    lsk_number: <?= json_encode($case['lsk_number']) ?>,
                    complainant_name: <?= json_encode($case['complainant_name']) ?>,
                    category: <?= json_encode($case['category']) ?>,
                    amount_involved: <?= json_encode($case['amount_involved']) ?>,
                    incident_date: <?= json_encode($case['incident_date']) ?>,
                    narrative: <?= json_encode($case['narrative']) ?>
                })
            });
            const data = await res.json();
            if (data.status === 'ok') {
                document.getElementById('aiBriefContainer').classList.remove('d-none');
                document.getElementById('briefSummary').textContent = data.executive_summary;
                const citList = document.getElementById('briefCitations');
                citList.innerHTML = '';
                data.legal_citations.forEach(c => {
                    citList.innerHTML += `<li>${c}</li>`;
                });
                document.getElementById('briefLetter').textContent = data.draft_letter;
                showToast('AI Case Brief & Notice to Show Cause generated!');
            }
        } catch (e) {
            showToast('Failed to generate AI brief.', 'error');
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="bi bi-stars me-2"></i>Generate AI Case Brief & Notice to Show Cause';
        }
    });

    document.getElementById('copyLetterBtn').addEventListener('click', () => {
        const text = document.getElementById('briefLetter').textContent;
        copyText(text, 'Notice to Show Cause copied!');
    });

    document.getElementById('workflowForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('api/update_case.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'ok') {
            showToast(data.message);
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    });

    document.getElementById('noteForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('api/update_case.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'ok') {
            showToast(data.message);
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message, 'error');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
