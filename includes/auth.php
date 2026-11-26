<?php
/**
 * Officer Authentication Helper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isOfficerLoggedIn(): bool {
    return !empty($_SESSION['acc_officer']);
}

function requireOfficerLogin(): void {
    if (!isOfficerLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentOfficer(): array {
    return $_SESSION['acc_officer'] ?? [
        'name' => 'ACC Officer',
        'email' => 'officer@acc.go.ke',
        'role' => 'Legal Officer'
    ];
}

function logoutOfficer(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
