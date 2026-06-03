<?php
session_start();
if (!isset($_SESSION['userID'])) { header("Location: admin_login.php"); exit(); }
include __DIR__ . '/../connection.php';

$r_pending   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='pending'"))[0]     ?? 0;
$r_progress  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='in-progress'"))[0] ?? 0;
$r_resolved  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='resolved'"))[0]    ?? 0;
$r_total     = $r_pending + $r_progress + $r_resolved;

$recent_reports = executeQuery("SELECT * FROM reports ORDER BY created_at DESC LIMIT 6");
$recent_ann     = executeQuery("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4");

$monthly_data = [];
$monthly_res  = executeQuery("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
           SUM(status='pending') AS pending,
           SUM(status='in-progress') AS inprogress,
           SUM(status='resolved') AS resolved
    FROM reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY ym ORDER BY ym ASC");
if ($monthly_res) while ($row = mysqli_fetch_assoc($monthly_res)) $monthly_data[] = $row;

$category_data = [];
$cat_res = executeQuery("SELECT SUBSTRING_INDEX(category,'|',1) AS cat_group, COUNT(*) AS cnt
                         FROM reports GROUP BY cat_group ORDER BY cnt DESC");
if ($cat_res) while ($row = mysqli_fetch_assoc($cat_res)) $category_data[$row['cat_group']] = (int)$row['cnt'];

// ── Project status counts ──────────────────────────────────────────────────
$p_planned   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]   ?? 0;
$p_ongoing   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]    ?? 0;
$p_completed = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0]  ?? 0;

// ── Turnaround time ────────────────────────────────────────────────────────
// Overall avg minutes to resolve (all time)
$tat_row = mysqli_fetch_assoc(executeQuery(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) AS avg_min
     FROM reports WHERE status='resolved' AND resolved_at IS NOT NULL"));
$avg_tat_min = $tat_row ? (float)($tat_row['avg_min'] ?? 0) : 0;

// This month avg
$tat_month_row = mysqli_fetch_assoc(executeQuery(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) AS avg_min
     FROM reports WHERE status='resolved' AND resolved_at IS NOT NULL
     AND MONTH(resolved_at)=MONTH(NOW()) AND YEAR(resolved_at)=YEAR(NOW())"));
$avg_tat_month = $tat_month_row ? (float)($tat_month_row['avg_min'] ?? 0) : 0;

// Last month avg (for trend arrow)
$tat_last_row = mysqli_fetch_assoc(executeQuery(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) AS avg_min
     FROM reports WHERE status='resolved' AND resolved_at IS NOT NULL
     AND MONTH(resolved_at)=MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
     AND YEAR(resolved_at)=YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))"));
$avg_tat_last = $tat_last_row ? (float)($tat_last_row['avg_min'] ?? 0) : 0;

// Monthly turnaround trend (last 12 months) for chart
$tat_monthly_data = [];
$tat_monthly_res  = executeQuery(
    "SELECT DATE_FORMAT(resolved_at,'%Y-%m') AS ym,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)), 1) AS avg_hours
     FROM reports WHERE status='resolved' AND resolved_at IS NOT NULL
       AND resolved_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY ym ORDER BY ym ASC");
if ($tat_monthly_res) while ($row = mysqli_fetch_assoc($tat_monthly_res)) $tat_monthly_data[] = $row;

// Format helper: minutes → "Xd Yh" or "Xh Ym" or "Xm"
function fmt_tat(float $mins): string {
    if ($mins <= 0) return '—';
    $m = (int)round($mins);
    $d = intdiv($m, 1440); $h = intdiv($m % 1440, 60); $r = $m % 60;
    if ($d > 0) return $d . 'd ' . $h . 'h';
    if ($h > 0) return $h . 'h ' . $r . 'm';
    return $r . 'm';
}
$avg_tat_display       = fmt_tat($avg_tat_min);
$avg_tat_month_display = fmt_tat($avg_tat_month);
// Trend: improving = this month faster than last (lower is better)
$tat_trend = ($avg_tat_last > 0 && $avg_tat_month > 0)
    ? ($avg_tat_month < $avg_tat_last ? 'faster' : ($avg_tat_month > $avg_tat_last ? 'slower' : 'same'))
    : 'none';

$current_page = 'admin_dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Dashboard</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
<script>/* dark mode resets on refresh */</script>
<?php include __DIR__ . '/includes/admin_navbar.php'; ?>

<div class="dash-wrap">

    <!-- Header -->
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Dashboard Overview</h1>
            <p class="dash-sub">
                Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_full_name'] ?? 'Admin') ?></strong>.
                Here's what's happening in your barangay.
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="total-pill">
                <i class="fa fa-bar-chart"></i>
                <?= $r_total ?> total report<?= $r_total !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div><!-- /.dash-header -->

    <!-- Hero Stats: only the 3 report statuses -->
    <?php
    $pct_pending  = $r_total > 0 ? round($r_pending / $r_total * 100) : 0;
    $pct_progress = $r_total > 0 ? round($r_progress / $r_total * 100) : 0;
    $pct_resolved = $r_total > 0 ? round($r_resolved / $r_total * 100) : 0;
    ?>
    <div class="stats-hero">
        <!-- Pending -->
        <div class="stat-hero-card pending">
            <div class="stat-hero-icon"><i class="fa fa-clock-o"></i></div>
            <div class="stat-hero-value"><?= $r_pending ?></div>
            <div class="stat-hero-label">Pending</div>
            <div class="stat-hero-sub">Reports awaiting action</div>
            <div class="stat-hero-bar">
                <div class="stat-hero-bar-track">
                    <div class="stat-hero-bar-fill" style="width:<?= $pct_pending ?>%;"></div>
                </div>
                <div class="stat-hero-bar-label"><?= $pct_pending ?>% of total reports</div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="stat-hero-card inprogress">
            <div class="stat-hero-icon"><i class="fa fa-spinner"></i></div>
            <div class="stat-hero-value"><?= $r_progress ?></div>
            <div class="stat-hero-label">In Progress</div>
            <div class="stat-hero-sub">Currently being handled</div>
            <div class="stat-hero-bar">
                <div class="stat-hero-bar-track">
                    <div class="stat-hero-bar-fill" style="width:<?= $pct_progress ?>%;"></div>
                </div>
                <div class="stat-hero-bar-label"><?= $pct_progress ?>% of total reports</div>
            </div>
        </div>

        <!-- Resolved -->
        <div class="stat-hero-card resolved">
            <div class="stat-hero-icon"><i class="fa fa-check-circle"></i></div>
            <div class="stat-hero-value"><?= $r_resolved ?></div>
            <div class="stat-hero-label">Resolved</div>
            <div class="stat-hero-sub">Successfully resolved</div>
            <div class="stat-hero-bar">
                <div class="stat-hero-bar-track">
                    <div class="stat-hero-bar-fill" style="width:<?= $pct_resolved ?>%;"></div>
                </div>
                <div class="stat-hero-bar-label"><?= $pct_resolved ?>% of total reports</div>
            </div>
        </div>

        <!-- Turnaround Time -->
        <?php
        $trend_class = $tat_trend === 'faster' ? 'tat-trend-faster' : ($tat_trend === 'slower' ? 'tat-trend-slower' : 'tat-trend-same');
        $trend_icon  = $tat_trend === 'faster' ? 'fa-arrow-down' : ($tat_trend === 'slower' ? 'fa-arrow-up' : 'fa-minus');
        $trend_label = $tat_trend === 'faster' ? 'Faster this month' : ($tat_trend === 'slower' ? 'Slower this month' : 'No change');
        ?>
        <div class="stat-hero-card turnaround">
            <div class="stat-hero-icon"><i class="fa fa-hourglass-half"></i></div>
            <div class="stat-hero-value"><?= $avg_tat_display ?></div>
            <div class="stat-hero-label">Avg. Resolution Time</div>
            <div class="stat-hero-sub">
                This month: <strong><?= $avg_tat_month_display ?></strong>
            </div>
            <?php if ($tat_trend !== 'none'): ?>
            <div class="tat-trend-pill <?= $trend_class ?>">
                <i class="fa <?= $trend_icon ?>"></i> <?= $trend_label ?>
            </div>
            <?php endif; ?>
            <div class="stat-hero-bar" style="margin-top:10px;">
                <div class="stat-hero-bar-track">
                    <div class="stat-hero-bar-fill" style="width:<?= $r_resolved > 0 ? min(100, round($r_resolved / max($r_total,1) * 100)) : 0 ?>%;"></div>
                </div>
                <div class="stat-hero-bar-label"><?= $r_resolved ?> of <?= $r_total ?> report<?= $r_total !== 1 ? 's' : '' ?> resolved</div>
            </div>
        </div>
    </div><!-- /.stats-hero -->

    <!-- Chart -->
    <div class="chart-card">
        <div class="chart-card-header">
            <div class="chart-card-title">
                <i class="fa fa-line-chart" style="color:#9b1f1f;"></i>
                Reports Overview
            </div>
            <div class="chart-tabs">
                <button class="chart-tab active" id="tab-month"      onclick="switchChartView('month',this)">Monthly</button>
                <button class="chart-tab"         id="tab-category"  onclick="switchChartView('category',this)">By Category</button>
                <button class="chart-tab"         id="tab-status"    onclick="switchChartView('status',this)">By Status</button>
                <button class="chart-tab"         id="tab-projects"  onclick="switchChartView('projects',this)">Projects</button>
                <button class="chart-tab"         id="tab-turnaround" onclick="switchChartView('turnaround',this)">Turnaround</button>
            </div>
        </div>
        <div style="height:270px; position:relative;" id="chartWrap">
            <canvas id="reportsChart"></canvas>
        </div>
    </div>

    <!-- Bottom row: Recent Reports + Recent Announcements -->
    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="section-card">
                <div class="section-title">
                    <div class="section-title-icon" style="background:#fff3e0; color:#f59c23;">
                        <i class="fa fa-flag"></i>
                    </div>
                    Recent Reports
                </div>
                <?php
                $status_cfg = [
                    'pending'     => ['Pending',     '#fff3e0','#c47200', '#f59c23'],
                    'in-progress' => ['In Progress', '#e8f0fe','#1a56db', '#1a56db'],
                    'resolved'    => ['Resolved',    '#e6faed','#128548', '#22cc77'],
                ];
                if ($recent_reports): while ($r = mysqli_fetch_assoc($recent_reports)):
                    $sc = $status_cfg[$r['status']] ?? $status_cfg['pending'];
                ?>
                <div class="rpt-row">
                    <div class="rpt-dot" style="background:<?= $sc[3] ?>;"></div>
                    <div class="rpt-title"><?= htmlspecialchars($r['title']) ?></div>
                    <span class="rpt-badge" style="background:<?= $sc[1] ?>; color:<?= $sc[2] ?>;"><?= $sc[0] ?></span>
                    <div class="rpt-date"><?= date('M j', strtotime($r['created_at'])) ?></div>
                </div>
                <?php endwhile; endif; ?>
                <a href="admin_report.php" class="view-all-link">View all reports →</a>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="section-card">
                <div class="section-title">
                    <div class="section-title-icon" style="background:#fffbe6; color:#b49500;">
                        <i class="fa fa-bullhorn"></i>
                    </div>
                    Recent Announcements
                </div>
                <?php
                if ($recent_ann): while ($a = mysqli_fetch_assoc($recent_ann)):
                    $is_urgent = !empty($a['is_urgent']);
                ?>
                <div class="ann-row">
                    <div class="ann-icon" style="background:<?= $is_urgent ? '#fff0f0' : '#fdeaea' ?>; color:<?= $is_urgent ? '#c0001a' : '#9b1f1f' ?>;">
                        <i class="fa <?= $is_urgent ? 'fa-exclamation-triangle' : 'fa-info-circle' ?>"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="ann-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars($a['title']) ?>
                        </div>
                        <div class="ann-meta">
                            <?php if ($is_urgent): ?>
                                <span style="color:#c0001a; font-weight:700;">⚠ Urgent</span> ·
                            <?php endif; ?>
                            <?= date('M j, Y', strtotime($a['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; endif; ?>
                <a href="admin_program.php" class="view-all-link">Manage announcements →</a>
            </div>
        </div>
    </div>

</div>

<!-- Logout Modal -->
<div id="logoutModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:18px; padding:28px 28px 22px; max-width:340px; width:90%; box-shadow:0 8px 40px rgba(0,0,0,.18); text-align:center;">
        <div style="width:52px; height:52px; border-radius:50%; background:#fff0f0; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
            <i class="fa fa-sign-out" style="font-size:22px; color:#c0001a;"></i>
        </div>
        <div style="font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px;">Log Out?</div>
        <div style="font-size:13px; color:#8890b8; margin-bottom:20px;">Are you sure you want to log out of the admin panel?</div>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="document.getElementById('logoutModal').style.display='none'"
                    style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0; background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">Cancel</button>
            <a href="admin_logout.php" style="flex:1; padding:10px; border-radius:10px; background:#c0001a; color:#fff; font-weight:700; cursor:pointer; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center;">Log Out</a>
        </div>
    </div>
</div>

<script>
window.MONTHLY_DATA   = <?= json_encode($monthly_data) ?>;
window.CATEGORY_DATA  = <?= json_encode($category_data) ?>;
window.STAT_PENDING   = <?= $r_pending ?>;
window.STAT_PROGRESS  = <?= $r_progress ?>;
window.STAT_RESOLVED  = <?= $r_resolved ?>;
window.STAT_PLANNED   = <?= $p_planned ?>;
window.STAT_ONGOING   = <?= $p_ongoing ?>;
window.STAT_COMPLETED = <?= $p_completed ?>;
window.TAT_MONTHLY    = <?= json_encode($tat_monthly_data) ?>;
window.AVG_TAT_MIN    = <?= round($avg_tat_min, 1) ?>;
</script>
<script src="../assets/js/admin_dashboard.js"></script>

<!-- Admin Dark Mode FAB -->
<button id="adminDarkToggle" onclick="toggleAdminDarkMode()" title="Toggle dark mode" aria-label="Toggle dark mode">
    <svg class="adt-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
    <svg class="adt-sun"  xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
</button>
<script src="../assets/js/admin-dark-mode.js"></script>
</body>
</html>