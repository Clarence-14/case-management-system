<?php
/**
 * Update Case API (api/update_case.php)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

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

if ($action === 'update_workflow') {
    $newStatus = $input['status'] ?? $case['status'];
    $newOfficer = $input['assigned_officer'] ?? $case['assigned_officer'];

    $upd = $pdo->prepare("UPDATE complaints SET status = ?, assigned_officer = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
    $upd->execute([$newStatus, $newOfficer, $id]);

    $changes = [];
    if ($newStatus !== $case['status']) $changes[] = "Status updated to '{$newStatus}'";
    if ($newOfficer !== $case['assigned_officer']) $changes[] = "Assigned to {$newOfficer}";

    if (!empty($changes)) {
        $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, 'ACC Commissioner Desk', 'Status Change', ?)");
        $note->execute([$id, implode('. ', $changes) . '.']);
    }

    echo json_encode(['status' => 'ok', 'message' => 'Workflow updated successfully.']);
    exit;
}

if ($action === 'add_note') {
    $content = trim($input['content'] ?? '');
    $author = trim($input['author'] ?? 'Investigating Officer');
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Note content cannot be empty.']);
        exit;
    }

    $note = $pdo->prepare("INSERT INTO case_notes (complaint_id, author, note_type, content) VALUES (?, ?, 'Note', ?)");
    $note->execute([$id, $author, $content]);

    echo json_encode(['status' => 'ok', 'message' => 'Investigation note added.']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
