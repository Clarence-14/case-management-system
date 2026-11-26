<?php
/**
 * Database Setup & Demo Reset Utility
 * Visiting setup.php?reset=1 will drop and re-seed the 8 demo Kenyan legal cases.
 */
require_once __DIR__ . '/includes/db.php';

$pdo = getDB();
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $pdo->exec("DROP TABLE IF EXISTS case_notes; DROP TABLE IF EXISTS complaints;");
    initSchema($pdo);
    seedDatabase($pdo);
    header('Location: admin.php?msg=reset_ok');
    exit;
}

$count = (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
echo json_encode([
    'status' => 'ok',
    'database' => DB_PATH,
    'complaints_count' => $count
]);
