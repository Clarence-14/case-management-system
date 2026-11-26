<?php
/**
 * Submit Complaint API (api/submit_complaint.php)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDB();

    $isAnonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] == '1' ? 1 : 0;
    $complainantName = $isAnonymous ? 'Anonymous Whistleblower' : trim($_POST['complainant_name'] ?? '');
    $complainantEmail = $isAnonymous ? '' : trim($_POST['complainant_email'] ?? '');
    $complainantPhone = $isAnonymous ? '' : trim($_POST['complainant_phone'] ?? '');
    $advocateName = trim($_POST['advocate_name'] ?? '');
    $lskNumber = trim($_POST['lsk_number'] ?? '');
    $incidentDate = trim($_POST['incident_date'] ?? '');
    $county = trim($_POST['county'] ?? 'Nairobi');
    $category = trim($_POST['category'] ?? 'Other Misconduct');
    $narrative = trim($_POST['narrative'] ?? '');
    $amountInvolved = (float)str_replace(',', '', $_POST['amount_involved'] ?? '0');

    if (empty($advocateName) || empty($narrative) || (!$isAnonymous && empty($complainantName))) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Handle optional evidence upload (PDF/JPG/PNG)
    $evidencePath = null;
    if (!empty($_FILES['evidence']['name']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF, JPG, and PNG are allowed.']);
            exit;
        }
        if ($_FILES['evidence']['size'] > MAX_UPLOAD_SIZE) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'File exceeds 5MB limit.']);
            exit;
        }
        $fileName = 'evidence_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['evidence']['tmp_name'], UPLOAD_DIR . $fileName)) {
            $evidencePath = 'uploads/' . $fileName;
        }
    }

    // Generate Reference Number: ACC-2026-XXXX
    $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM complaints")->fetchColumn();
    $refNumber = 'ACC-2026-' . (1001 + $maxId);

    // Run internal AI triage
    $aiCategory = trim($_POST['ai_category'] ?? '') ?: $category;
    $aiUrgency = (int)($_POST['ai_urgency_score'] ?? 55);
    if ($aiUrgency < 1 || $aiUrgency > 100) $aiUrgency = 55;
    $aiSummary = mb_substr($narrative, 0, 160) . (mb_strlen($narrative) > 160 ? '...' : '');

    $stmt = $pdo->prepare("
        INSERT INTO complaints (
            ref_number, is_anonymous, complainant_name, complainant_email, complainant_phone,
            advocate_name, lsk_number, incident_date, county, category, ai_category,
            ai_urgency_score, ai_summary, narrative, amount_involved, evidence_path, status
        ) VALUES (
            :ref_number, :is_anonymous, :complainant_name, :complainant_email, :complainant_phone,
            :advocate_name, :lsk_number, :incident_date, :county, :category, :ai_category,
            :ai_urgency_score, :ai_summary, :narrative, :amount_involved, :evidence_path, 'Received'
        )
    ");

    $stmt->execute([
        'ref_number' => $refNumber,
        'is_anonymous' => $isAnonymous,
        'complainant_name' => $complainantName,
        'complainant_email' => $complainantEmail,
        'complainant_phone' => $complainantPhone,
        'advocate_name' => $advocateName,
        'lsk_number' => $lskNumber,
        'incident_date' => $incidentDate,
        'county' => $county,
        'category' => $category,
        'ai_category' => $aiCategory,
        'ai_urgency_score' => $aiUrgency,
        'ai_summary' => $aiSummary,
        'narrative' => $narrative,
        'amount_involved' => $amountInvolved,
        'evidence_path' => $evidencePath
    ]);

    $complaintId = $pdo->lastInsertId();
    $noteStmt = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, 'ACC Intake System', 'Status Change', ?)");
    $noteStmt->execute([$complaintId, "Complaint lodged online and assigned reference {$refNumber}."]);

    echo json_encode([
        'status' => 'ok',
        'ref_number' => $refNumber,
        'message' => 'Complaint submitted successfully.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
