<?php
/**
 * Authentication & Role-Based Access Control (RBAC) Helper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isUserLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function isAccStaff(): bool {
    return !empty($_SESSION['user']['is_acc_staff']);
}

// Backwards-compatible alias
function isOfficerLoggedIn(): bool {
    return isAccStaff();
}

function requireAccStaff(): void {
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if (!isAccStaff()) {
        http_response_code(403);
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

// Backwards-compatible alias
function requireOfficerLogin(): void {
    requireAccStaff();
}

function getCurrentUser(): array {
    return $_SESSION['user'] ?? [
        'name' => 'Guest',
        'role' => 'Citizen',
        'login_method' => 'none',
        'is_acc_staff' => false
    ];
}

function getCurrentOfficer(): array {
    return getCurrentUser();
}

function logoutUser(): void {
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

function logoutOfficer(): void {
    logoutUser();
}
