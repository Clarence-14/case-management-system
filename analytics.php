<?php
$pageTitle = 'Integrity & Analytics | ACC Kenya';
require_once __DIR__ . '/includes/auth.php';
requireOfficerLogin();
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

// Category breakdown
$catRows = $pdo->query("SELECT category, COUNT(*) as cnt FROM complaints GROUP BY category ORDER BY cnt DESC")->fetchAll();
$catLabels = array_column($catRows, 'category');
$catCounts = array_map('intval', array_column($catRows, 'cnt'));

// County breakdown
$countyRows = $pdo->query("SELECT county, COUNT(*) as cnt FROM complaints GROUP BY county ORDER BY cnt DESC LIMIT 6")->fetchAll();
$countyLabels = array_column($countyRows, 'county');
$countyCounts = array_map('intval', array_column($countyRows, 'cnt'));

// Monthly trend
$monthRows = $pdo->query("SELECT strftime('%Y-%m', created_at) as m, COUNT(*) as total, SUM(CASE WHEN status='Resolved' THEN 1 ELSE 0 END) as resolved FROM complaints GROUP BY m ORDER BY m ASC")->fetchAll();
$monthLabels = array_column($monthRows, 'm');
$monthTotal = array_map('intval', array_column($monthRows, 'total'));
$monthResolved = array_map('intval', array_column($monthRows, 'resolved'));

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge acc-badge-gold px-3 py-2 rounded-1 mb-2">INSTITUTIONAL TRANSPARENCY</span>
            <h1 class="h2 fw-bold mb-0">Integrity &amp; Analytics Overview</h1>
        </div>
        <a href="admin.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="acc-card p-4 h-100">
                <h5 class="fw-bold mb-3">Complaints by Misconduct Category</h5>
                <canvas id="categoryChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="acc-card p-4 h-100">
                <h5 class="fw-bold mb-3">Monthly Intake vs. Resolution Trend</h5>
                <canvas id="trendChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-12">
            <div class="acc-card p-4">
                <h5 class="fw-bold mb-3">Top Flagged Counties</h5>
                <canvas id="countyChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const navy = '#0F1E36';
    const gold = '#C59B27';
    const green = '#15803D';
    const crimson = '#B91C1C';

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catCounts) ?>,
                backgroundColor: [navy, gold, crimson, '#2563EB', green, '#64748B', '#D97706']
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [
                { label: 'Total Lodged', data: <?= json_encode($monthTotal) ?>, backgroundColor: navy },
                { label: 'Resolved', data: <?= json_encode($monthResolved) ?>, backgroundColor: green }
            ]
        },
        options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    new Chart(document.getElementById('countyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($countyLabels) ?>,
            datasets: [{
                label: 'Complaints',
                data: <?= json_encode($countyCounts) ?>,
                backgroundColor: gold
            }]
        },
        options: {
            indexAxis: 'y',
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
