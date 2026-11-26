<?php
$pageTitle = 'Sign In | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isUserLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

/**
 * Demo LSK accounts — only LSK numbers that also exist in acc_staff
 * get Admin access; everyone else is treated as an ordinary citizen.
 *
 * ACC Staff (Admin access):        password ACC2026!
 *   LSK/2014/01001 — Adv. Wanjiku Mwangi (Senior Counsel)
 *   LSK/2012/00450 — Commissioner Ochieng Otieno (Commission Secretary)
 *   LSK/2016/05512 — Adv. Ochieng Otieno (Investigations)
 *   LSK/2018/07234 — Adv. Fatuma Hassan (Compliance)
 *   LSK/2015/04891 — Adv. Kiprop Bett (Disciplinary)
 *
 * Non-staff LSK advocate (Citizen access only): password LSK2026!
 *   LSK/2019/08842 — Adv. Brian Kiprop (Private Practice)
 */
$lskAccounts = [
    'LSK/2014/01001' => ['password' => 'ACC2026!', 'name' => 'Adv. Wanjiku Mwangi'],
    'LSK/2012/00450' => ['password' => 'ACC2026!', 'name' => 'Commissioner Ochieng Otieno'],
    'LSK/2016/05512' => ['password' => 'ACC2026!', 'name' => 'Adv. Ochieng Otieno'],
    'LSK/2018/07234' => ['password' => 'ACC2026!', 'name' => 'Adv. Fatuma Hassan'],
    'LSK/2015/04891' => ['password' => 'ACC2026!', 'name' => 'Adv. Kiprop Bett'],
    'LSK/2019/08842' => ['password' => 'LSK2026!', 'name' => 'Adv. Brian Kiprop'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lskNumber = strtoupper(trim($_POST['lsk_number'] ?? ''));
    $password  = $_POST['password'] ?? '';

    if (isset($lskAccounts[$lskNumber]) && $lskAccounts[$lskNumber]['password'] === $password) {
        $staffRecord = verifyAccStaff($lskNumber);
        $_SESSION['user'] = [
            'name'         => $staffRecord ? $staffRecord['full_name'] : $lskAccounts[$lskNumber]['name'],
            'lsk_number'   => $lskNumber,
            'login_method' => 'lsk',
            'is_acc_staff' => $staffRecord !== null,
            'role'         => $staffRecord ? $staffRecord['role'] : 'Citizen (LSK Member)',
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid LSK Practice Number or password. Please try again.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container" style="max-width: 480px;">
        <div class="acc-card p-4 p-md-5 mt-2">

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-2 mb-3"
                     style="width: 56px; height: 56px; background: var(--acc-navy); color: var(--acc-gold);">
                    <i class="bi bi-shield-lock-fill fs-3"></i>
                </div>
                <h1 class="h3 fw-bold mb-1">Sign In to ACC Kenya</h1>
                <p class="text-muted small mb-0">Enter your Law Society of Kenya (LSK) credentials</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger small py-2" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">LSK Practice Number</label>
                    <input type="text" class="form-control" name="lsk_number" id="lskInput"
                           placeholder="e.g. LSK/2014/01001" required autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" class="form-control" name="password" id="lskPassInput"
                           placeholder="Enter password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-acc-primary w-100 py-2 mb-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In with LSK Credentials
                </button>
            </form>

            <!-- Demo Quick-Fill Buttons -->
            <div class="border-top pt-3">
                <p class="small text-muted text-center mb-2 fw-semibold">Demo Accounts</p>
                <div class="row g-2">
                    <div class="col-12">
                        <p class="text-muted mb-1" style="font-size:0.72rem; text-transform:uppercase; letter-spacing:.5px;">
                            <i class="bi bi-shield-check text-warning me-1"></i>ACC Staff — full Admin access
                        </p>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 demo-btn"
                                data-lsk="LSK/2014/01001" data-pw="ACC2026!">
                            Wanjiku Mwangi
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 demo-btn"
                                data-lsk="LSK/2012/00450" data-pw="ACC2026!">
                            Ochieng (Secretary)
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 demo-btn"
                                data-lsk="LSK/2016/05512" data-pw="ACC2026!">
                            Ochieng (Invest.)
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 demo-btn"
                                data-lsk="LSK/2018/07234" data-pw="ACC2026!">
                            Fatuma Hassan
                        </button>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-dark w-100 demo-btn"
                                data-lsk="LSK/2015/04891" data-pw="ACC2026!">
                            Kiprop Bett (Disciplinary)
                        </button>
                    </div>

                    <div class="col-12 mt-2">
                        <p class="text-muted mb-1" style="font-size:0.72rem; text-transform:uppercase; letter-spacing:.5px;">
                            <i class="bi bi-person me-1"></i>LSK Member — Citizen access only
                        </p>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 demo-btn"
                                data-lsk="LSK/2019/08842" data-pw="LSK2026!">
                            Adv. Brian Kiprop (Private Practice)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.demo-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('lskInput').value    = btn.dataset.lsk;
        document.getElementById('lskPassInput').value = btn.dataset.pw;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
