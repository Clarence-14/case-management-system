<?php
/**
 * Update Case API (api/update_case.php)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isAccStaff()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: ACC Staff login required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id = (int)($input['complaint_id'] ?? 0);
$action = $input['action'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid complaint ID']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ?");
$stmt->execute([$id]);
$case = $stmt->fetch();

if (!$case) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Case not found']);
    exit;
}

if (!canAccessCase($case)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied: You are not assigned to this case.']);
    exit;
}

$currentUser = getCurrentUser();
$actorLabel = $currentUser['name'] . ' (' . $currentUser['role'] . ')';

// Senior Counsel ONLY: Update workflow status & assign/reassign any case
if ($action === 'update_workflow') {
    if (!isSeniorCounsel() || !canReassignCase($case)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only Senior Counsel can update workflow stages or assign/reassign cases.']);
        exit;
    }

    $newStatus = $input['status'] ?? $case['status'];
    $newOfficer = $input['assigned_officer'] ?? $case['assigned_officer'];

    // Clear previous step completion badge if the workflow stage changed
    $resetStep = ($newStatus !== $case['status']) ? ", step_completed_by = NULL, step_completed_at = NULL" : "";

    $upd = $pdo->prepare("UPDATE complaints SET status = ?, assigned_officer = ?{$resetStep}, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$newStatus, $newOfficer, $id]);

    $changes = [];
    if ($newStatus !== $case['status']) $changes[] = "Workflow stage updated to '{$newStatus}'";
    if ($newOfficer !== $case['assigned_officer']) $changes[] = "Assigned to {$newOfficer}";

    if (!empty($changes)) {
        $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'Status Change', ?)");
        $note->execute([$id, $actorLabel, implode('. ', $changes) . '.']);
    }

    echo json_encode(['status' => 'ok', 'message' => 'Workflow stage & assignment updated by Senior Counsel.']);
    exit;
}

// Assigned ACC Staff (or Senior Counsel): Mark current workflow stage as completed
if ($action === 'complete_step') {
    $remarks = trim($input['step_remarks'] ?? '');
    $currentStage = $case['status'];
    $now = date('Y-m-d H:i:s');

    $upd = $pdo->prepare("UPDATE complaints SET step_completed_by = ?, step_completed_at = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$actorLabel, $now, $id]);

    $msg = "Marked workflow step '{$currentStage}' as completed";
    if ($remarks !== '') {
        $msg .= ": {$remarks}";
    } else {
        $msg .= " (awaiting Senior Counsel review/progression).";
    }

    $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'Step Completed', ?)");
    $note->execute([$id, $actorLabel, $msg]);

    echo json_encode(['status' => 'ok', 'message' => "Step '{$currentStage}' marked as completed."]);
    exit;
}

if ($action === 'add_note') {
    $content = trim($input['content'] ?? '');
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Note content cannot be empty.']);
        exit;
    }

    $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'Note', ?)");
    $note->execute([$id, $actorLabel, $content]);

    echo json_encode(['status' => 'ok', 'message' => 'Investigation note added.']);
    exit;
}

if ($action === 'save_response') {
    $reply = trim($input['advocate_response'] ?? '');
    $upd = $pdo->prepare("UPDATE complaints SET advocate_response = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$reply, $id]);

    $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'Note', 'Advocate response / rebuttal logged for AI audit.')");
    $note->execute([$id, $actorLabel]);

    echo json_encode(['status' => 'ok', 'message' => 'Advocate response saved.']);
    exit;
}

if ($action === 'verify_ai') {
    $outputType = trim($input['output_type'] ?? 'AI Legal Output');
    $stamp = $actorLabel . ' @ ' . date('d M Y H:i');

    $upd = $pdo->prepare("UPDATE complaints SET ai_verified_by = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$stamp, $id]);

    $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'AI Sign-Off', ?)");
    $note->execute([$id, $actorLabel, "Human-in-the-Loop Verification: Approved {$outputType}."]);

    echo json_encode(['status' => 'ok', 'verified_by' => $stamp, 'message' => 'AI output verified and logged to audit trail.']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
