<?php
$pageTitle = 'Officer Login | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';

if (isOfficerLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';
// Demo officer accounts
$officers = [
    'officer@acc.go.ke' => [
        'password' => 'ACC2026!',
        'name' => 'Adv. Wanjiku Mwangi',
        'role' => 'Senior Counsel'
    ],
    'commissioner@acc.go.ke' => [
        'password' => 'ACC2026!',
        'name' => 'Commissioner Ochieng Otieno',
        'role' => 'Commission Secretary'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (isset($officers[$email]) && $officers[$email]['password'] === $password) {
        $_SESSION['acc_officer'] = [
            'email' => $email,
            'name' => $officers[$email]['name'],
            'role' => $officers[$email]['role']
        ];
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid officer email or password.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container" style="max-width: 460px;">
        <div class="acc-card p-4 p-md-5 mt-3">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-2 mb-3" style="width: 56px; height: 56px; background: var(--acc-navy); color: var(--acc-gold);">
                    <i class="bi bi-shield-lock-fill fs-3"></i>
                </div>
                <h1 class="h3 fw-bold mb-1">ACC Officer Portal</h1>
                <p class="text-muted small mb-0">Sign in to access the Case Triage &amp; AI Workbench</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Official Email</label>
                    <input type="email" class="form-control" name="email" id="loginEmail" placeholder="officer@acc.go.ke" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" class="form-control" name="password" id="loginPassword" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-acc-primary w-100 py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="border-top pt-3 text-center">
                <span class="small text-muted d-block mb-2">Demo Credentials: <code>officer@acc.go.ke</code> / <code>ACC2026!</code></span>
                <button type="button" class="btn btn-sm btn-outline-dark" id="fillDemoBtn">
                    <i class="bi bi-lightning-charge me-1"></i>Fill Demo Credentials
                </button>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('fillDemoBtn').addEventListener('click', () => {
    document.getElementById('loginEmail').value = 'officer@acc.go.ke';
    document.getElementById('loginPassword').value = 'ACC2026!';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
