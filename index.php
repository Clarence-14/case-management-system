<?php
$pageTitle = 'Lodge a Complaint | ACC Kenya';
require_once __DIR__ . '/includes/db.php';
getDB(); // Ensure DB is initialized on first visit
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="acc-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge acc-badge-gold px-3 py-2 rounded-1 mb-3">
                    ACCOUNTABILITY &amp; JUSTICE REBOOTED
                </span>
                <h1 class="display-5 fw-bold mb-3">Advocates Complaints Commission</h1>
                <p class="lead text-white-50 mb-4" style="max-width: 680px;">
                    Strengthening legal integrity in Kenya. Lodge a formal complaint against an advocate for professional misconduct, track your case in real time, and receive AI-assisted preliminary guidance.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#complaintFormSection" class="btn btn-acc-gold px-4 py-2">
                        <i class="bi bi-pencil-square me-2"></i>Lodge a Complaint
                    </a>
                    <?php if (isUserLoggedIn()): ?>
                    <a href="track.php" class="btn btn-outline-light px-4 py-2">
                        <i class="bi bi-search me-2"></i>Track Existing Case
                    </a>
                    <?php else: ?>
                    <a href="login.php?redirect=track.php" class="btn btn-outline-light px-4 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Track Case
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-end">
                <div class="p-4 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(197,155,39,0.3);">
                    <i class="bi bi-shield-check text-warning display-4 mb-2 d-block"></i>
                    <h5 class="text-white mb-1">Cap. 16 Statutory Mandate</h5>
                    <p class="small text-white-50 mb-0">
                        Receiving, investigating, and prosecuting complaints of professional misconduct against advocates of the High Court of Kenya.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Complaint Form & Live AI Assistant -->
<section id="complaintFormSection" class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Left: Complaint Form -->
            <div class="col-lg-8">
                <div class="acc-card p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <div>
                            <h2 class="h3 mb-1">Lodge a Formal Complaint</h2>
                            <p class="text-muted small mb-0">All submissions are confidential and assigned a unique tracking reference.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="anonymousToggle">
                            <label class="form-check-label fw-semibold small" for="anonymousToggle">
                                <i class="bi bi-incognito me-1"></i>Anonymous / Whistleblower
                            </label>
                        </div>
                    </div>

                    <form id="complaintForm" enctype="multipart/form-data">
                        <input type="hidden" name="is_anonymous" id="isAnonymousInput" value="0">
                        <input type="hidden" name="ai_category" id="aiCategoryInput" value="">
                        <input type="hidden" name="ai_urgency_score" id="aiUrgencyInput" value="55">
                        <input type="hidden" name="detected_language" id="detectedLangInput" value="English">
                        <input type="hidden" name="normalized_narrative" id="normalizedNarrativeInput" value="">
                        <input type="hidden" name="recommended_division" id="recommendedDivisionInput" value="Review & Investigation">
                        <input type="hidden" name="receipt_ocr_json" id="receiptOcrInput" value="">

                        <!-- Complainant Details -->
                        <div id="complainantFields" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="complainant_name" id="complainantName" placeholder="e.g. Jane Wanjiku" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Email Address</label>
                                <input type="email" class="form-control" name="complainant_email" placeholder="jane@example.co.ke">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Phone Number</label>
                                <input type="text" class="form-control" name="complainant_phone" placeholder="+254 7XX XXX XXX">
                            </div>
                        </div>

                        <!-- Advocate Details -->
                        <h6 class="text-uppercase text-muted fw-bold small mb-3">Respondent Advocate Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Advocate / Law Firm Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="advocate_name" placeholder="e.g. Adv. Kamau Njoroge" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">LSK Practice Number (if known)</label>
                                <input type="text" class="form-control" name="lsk_number" placeholder="e.g. LSK/2014/04821">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Incident Date</label>
                                <input type="date" class="form-control" name="incident_date">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">County</label>
                                <select class="form-select" name="county">
                                    <option value="Nairobi">Nairobi</option>
                                    <option value="Mombasa">Mombasa</option>
                                    <option value="Kisumu">Kisumu</option>
                                    <option value="Nakuru">Nakuru</option>
                                    <option value="Kiambu">Kiambu</option>
                                    <option value="Uasin Gishu">Uasin Gishu</option>
                                    <option value="Machakos">Machakos</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Amount Involved (KES)</label>
                                <input type="number" class="form-control" name="amount_involved" id="amountInvolved" placeholder="e.g. 500000" min="0">
                            </div>
                        </div>

                        <!-- Misconduct & Narrative -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Category of Misconduct <span class="text-danger">*</span></label>
                            <select class="form-select" name="category" id="categorySelect" required>
                                <?php foreach (MISCONDUCT_CATEGORIES as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Narrative Description <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(Describe what happened, dates, and amounts in KES)</span>
                            </label>
                            <textarea class="form-control" name="narrative" id="narrativeInput" rows="5" placeholder="Explain the sequence of events clearly..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Supporting Evidence / M-Pesa or Bank Receipt (PDF, JPG, PNG — Max 5MB)</label>
                            <input type="file" class="form-control" name="evidence" id="evidenceInput" accept=".pdf,.jpg,.jpeg,.png">
                            <div id="ocrResultBox" class="mt-2 d-none p-2 rounded-2 border bg-light small"></div>
                        </div>

                        <button type="submit" class="btn btn-acc-primary btn-lg w-100" id="submitBtn">
                            <i class="bi bi-send-check me-2"></i>Submit Formal Complaint
                        </button>
                    </form>

                    <!-- Submission Success Banner (Hidden by default) -->
                    <div id="submissionSuccess" class="d-none text-center py-4">
                        <i class="bi bi-check-circle-fill text-success display-3 mb-3"></i>
                        <h3 class="mb-2">Complaint Successfully Lodged</h3>
                        <p class="text-muted mb-3">Your complaint has been recorded and queued for AI-assisted preliminary triage.</p>
                        <div class="p-3 bg-light rounded-3 border d-inline-block mb-4">
                            <span class="small text-muted d-block">YOUR REFERENCE NUMBER</span>
                            <span class="case-ref fs-3 fw-bold text-dark" id="generatedRef">ACC-2026-XXXX</span>
                        </div>
                        <div class="d-flex justify-content-center gap-2">
                            <?php if (isUserLoggedIn()): ?>
                            <a id="trackNowLink" href="track.php" class="btn btn-acc-primary">
                                <i class="bi bi-search me-1"></i>Track Case Progress
                            </a>
                            <?php else: ?>
                            <a id="trackNowLink" href="login.php?redirect=track.php" class="btn btn-acc-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Sign In to Track Case
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                Lodge Another
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Live AI Intake Assistant -->
            <div class="col-lg-4">
                <div class="acc-card ai-assistant-card p-4 sticky-top" style="top: 85px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="ai-pulse"></span>
                            <h5 class="mb-0 fw-bold">AI Intake Assistant</h5>
                        </div>
                        <span class="badge bg-dark text-warning small">LIVE</span>
                    </div>
                    <p class="small text-muted mb-2">
                        Type below (or in the main narrative box) for real-time AI classification, urgency scoring, and missing-detail prompts.
                    </p>
                    <textarea class="form-control form-control-sm mb-3" id="aiQuickInput" rows="3" placeholder="Type or paste your complaint details here..."></textarea>

                    <div id="aiWaiting" class="text-center py-3 text-muted small border rounded-3 bg-white">
                        <i class="bi bi-robot fs-3 d-block mb-1 text-secondary"></i>
                        <p class="mb-2">Waiting for at least 15 characters...</p>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="loadSampleBtn">
                            <i class="bi bi-lightning-charge me-1"></i>Try Sample Narrative
                        </button>
                    </div>

                    <div id="aiResults" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Language: <strong id="aiLangBadge" class="text-dark">English</strong></span>
                            <span class="badge bg-light text-dark border" id="aiDivisionBadge">Review</span>
                        </div>
                        <div class="mb-3">
                            <span class="small text-muted d-block">Suggested Category</span>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <strong class="text-dark" id="aiSuggestedCat">-</strong>
                                <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2" id="applyCatBtn">Apply</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="small text-muted d-block">Estimated Severity &amp; Urgency</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge" id="aiSeverityBadge">-</span>
                                <span class="small fw-bold" id="aiUrgencyText">-</span>
                            </div>
                        </div>

                        <div id="aiPromptsBox" class="mb-3 d-none">
                            <span class="small text-muted d-block mb-1">Recommended Additions</span>
                            <ul class="list-unstyled small mb-0" id="aiPromptsList"></ul>
                        </div>

                        <div class="pt-2 border-top small text-muted">
                            <i class="bi bi-cpu me-1"></i>Engine: <span id="aiEngineName">ACC Legal AI</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const anonToggle = document.getElementById('anonymousToggle');
    const complainantFields = document.getElementById('complainantFields');
    const complainantName = document.getElementById('complainantName');
    const isAnonInput = document.getElementById('isAnonymousInput');

    anonToggle.addEventListener('change', () => {
        const isAnon = anonToggle.checked;
        isAnonInput.value = isAnon ? '1' : '0';
        complainantFields.classList.toggle('d-none', isAnon);
        complainantName.required = !isAnon;
    });

    // Live AI Intake Assistant (Debounced & Two-Way Synced)
    const narrativeInput = document.getElementById('narrativeInput');
    const aiQuickInput = document.getElementById('aiQuickInput');
    const amountInput = document.getElementById('amountInvolved');
    let debounceTimer;

    narrativeInput.addEventListener('input', () => {
        aiQuickInput.value = narrativeInput.value;
        triggerAIAssess();
    });
    aiQuickInput.addEventListener('input', () => {
        narrativeInput.value = aiQuickInput.value;
        triggerAIAssess();
    });
    amountInput.addEventListener('input', triggerAIAssess);

    document.getElementById('loadSampleBtn').addEventListener('click', () => {
        const sample = 'I engaged Adv. Kamau Njoroge in June 2026 for a land sale in Nairobi. The buyer paid KES 2,500,000 into the advocate\'s client account, but the advocate has withheld the proceeds and ignored my calls.';
        narrativeInput.value = sample;
        aiQuickInput.value = sample;
        amountInput.value = '2500000';
        triggerAIAssess();
    });

    function triggerAIAssess() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const narrative = narrativeInput.value.trim();
            if (narrative.length < 15) {
                document.getElementById('aiWaiting').classList.remove('d-none');
                document.getElementById('aiResults').classList.add('d-none');
                return;
            }
            try {
                const res = await fetch('api/ai_analyze.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'intake_assess',
                        narrative: narrative,
                        amount_involved: amountInput.value || 0
                    })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    document.getElementById('aiWaiting').classList.add('d-none');
                    document.getElementById('aiResults').classList.remove('d-none');
                    document.getElementById('aiSuggestedCat').textContent = data.suggested_category;
                    document.getElementById('aiCategoryInput').value = data.suggested_category;
                    document.getElementById('aiUrgencyInput').value = data.urgency_score;
                    document.getElementById('aiUrgencyText').textContent = `${data.urgency_score}/100`;
                    document.getElementById('aiEngineName').textContent = data.engine || 'ACC Legal AI';
                    document.getElementById('aiLangBadge').textContent = data.detected_language || 'English';
                    document.getElementById('aiDivisionBadge').textContent = data.recommended_division || 'Review & Investigation';
                    document.getElementById('detectedLangInput').value = data.detected_language || 'English';
                    document.getElementById('normalizedNarrativeInput').value = data.normalized_narrative || narrative;
                    document.getElementById('recommendedDivisionInput').value = data.recommended_division || 'Review & Investigation';
                    window._jurisdictionStatus = data.jurisdiction_status || 'Within Mandate';
                    window._jurisdictionNote = data.jurisdiction_note || '';

                    const badge = document.getElementById('aiSeverityBadge');
                    badge.textContent = data.severity_label;
                    badge.className = 'badge ' + (
                        data.severity_label === 'Critical' ? 'badge-urgent' :
                        data.severity_label === 'High' ? 'badge-high' :
                        data.severity_label === 'Medium' ? 'badge-medium' : 'badge-low'
                    );

                    const promptsBox = document.getElementById('aiPromptsBox');
                    const promptsList = document.getElementById('aiPromptsList');
                    promptsList.innerHTML = '';
                    if (data.missing_info_prompts && data.missing_info_prompts.length > 0) {
                        promptsBox.classList.remove('d-none');
                        data.missing_info_prompts.forEach(p => {
                            promptsList.innerHTML += `<li class="text-warning-emphasis mb-1"><i class="bi bi-exclamation-circle me-1"></i>${p}</li>`;
                        });
                    } else {
                        promptsBox.classList.add('d-none');
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }, 500);
    }

    narrativeInput.addEventListener('input', triggerAIAssess);
    amountInput.addEventListener('input', triggerAIAssess);

    document.getElementById('loadSampleBtn').addEventListener('click', () => {
        narrativeInput.value = 'I engaged Adv. Kamau Njoroge in June 2026 for a land sale in Nairobi. The buyer paid KES 2,500,000 into the advocate\'s client account, but the advocate has withheld the proceeds and ignored my calls.';
        amountInput.value = '2500000';
        triggerAIAssess();
    });

    document.getElementById('applyCatBtn').addEventListener('click', () => {
        const suggested = document.getElementById('aiSuggestedCat').textContent;
        document.getElementById('categorySelect').value = suggested;
        showToast(`Applied category: ${suggested}`);
    });

    // Receipt / M-Pesa OCR Verification
    document.getElementById('evidenceInput').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        const ocrBox = document.getElementById('ocrResultBox');
        if (!file || !/\.(jpg|jpeg|png)$/i.test(file.name)) {
            ocrBox.classList.add('d-none');
            return;
        }
        ocrBox.classList.remove('d-none');
        ocrBox.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning receipt with AI Vision...';

        const fd = new FormData();
        fd.append('action', 'ocr_receipt');
        fd.append('receipt', file);
        fd.append('claimed_amount', amountInput.value || 0);
        fd.append('advocate_name', document.querySelector('[name="advocate_name"]').value || '');

        try {
            const res = await fetch('api/ai_analyze.php', { method: 'POST', body: fd });
            const ocr = await res.json();
            if (ocr.status === 'ok') {
                document.getElementById('receiptOcrInput').value = JSON.stringify(ocr);
                const badgeCls = ocr.verification_status === 'Verified Match' ? 'bg-success' : 'bg-danger';
                ocrBox.innerHTML = `<span class="badge ${badgeCls} me-2">${ocr.verification_status}</span>`
                    + `Extracted: <strong>KES ${Number(ocr.receipt_amount).toLocaleString()}</strong> &middot; ${ocr.transaction_date} (${ocr.reference_code})`;
            } else {
                ocrBox.textContent = ocr.message || 'Could not scan receipt.';
            }
        } catch (err) {
            ocrBox.textContent = 'Receipt OCR unavailable.';
        }
    });

    // Form Submission with Jurisdiction Pre-Screener
    let bypassJurisdiction = false;
    document.getElementById('complaintForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!bypassJurisdiction && window._jurisdictionStatus === 'Advisory') {
            document.getElementById('jurisdictionModalBody').textContent = window._jurisdictionNote;
            new bootstrap.Modal(document.getElementById('jurisdictionModal')).show();
            return;
        }
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        try {
            const formData = new FormData(e.target);
            const res = await fetch('api/submit_complaint.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'ok') {
                e.target.classList.add('d-none');
                document.getElementById('submissionSuccess').classList.remove('d-none');
                document.getElementById('generatedRef').textContent = data.ref_number;
                const isLoggedIn = <?= isUserLoggedIn() ? 'true' : 'false' ?>;
                document.getElementById('trackNowLink').href = isLoggedIn
                    ? ('track.php?ref=' + encodeURIComponent(data.ref_number))
                    : ('login.php?redirect=' + encodeURIComponent('track.php?ref=' + data.ref_number));
                showToast('Complaint submitted successfully!');
            } else {
                showToast(data.message || 'Submission failed.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-check me-2"></i>Submit Formal Complaint';
            }
        } catch (err) {
            showToast('Network error occurred.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-check me-2"></i>Submit Formal Complaint';
        }
    });
});
</script>

<!-- Jurisdiction Advisory Modal -->
<div class="modal fade" id="jurisdictionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill text-warning me-2"></i>ACC Mandate &amp; Jurisdiction Advisory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="jurisdictionModalBody" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Edit Complaint</button>
                <button type="button" class="btn btn-acc-primary" onclick="document.getElementById('complaintForm').dispatchEvent(new Event('submit'));" id="proceedAnywayBtn">Proceed Anyway</button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('proceedAnywayBtn')?.addEventListener('click', () => {
    window._jurisdictionStatus = 'Within Mandate';
    bootstrap.Modal.getInstance(document.getElementById('jurisdictionModal'))?.hide();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
