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
    <style>
        body { background: #f0f2f8; min-height: 100vh; }

        .dash-wrap { max-width: 1200px; margin: 0 auto; padding: 28px 24px; }

        /* ── Page header ── */
        .dash-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 28px; }
        .dash-title  { font-family: 'Sora', sans-serif; font-size: 30px; font-weight: 800; color: #0d0e2e; margin: 0 0 3px; }
        .dash-sub    { font-size: 16px; color: #8890b8; margin: 0; }
        @keyframes fa-spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }

        /* ── Hero stat cards ── */
        .stats-hero { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        @media (max-width: 900px) { .stats-hero { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 500px) { .stats-hero { grid-template-columns: 1fr; } }

        .stat-hero-card {
            position: relative; overflow: hidden;
            border-radius: 18px; padding: 24px 22px 20px;
            border: 1.5px solid transparent;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            transition: transform .2s, box-shadow .2s;
        }
        .stat-hero-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.12); }

        /* Pending — amber */
        .stat-hero-card.pending {
            background: linear-gradient(135deg, #fff8ed 0%, #fff3dc 100%);
            border-color: #fcd97a;
        }
        /* In Progress — blue */
        .stat-hero-card.inprogress {
            background: linear-gradient(135deg, #f0f4ff 0%, #e5ecff 100%);
            border-color: #a3bdf7;
        }
        /* Resolved — green */
        .stat-hero-card.resolved {
            background: linear-gradient(135deg, #f0fdf5 0%, #e3faea 100%);
            border-color: #86e0a9;
        }

        .stat-hero-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 16px;
        }
        .pending   .stat-hero-icon { background: #f59c23; color: #fff; box-shadow: 0 4px 14px rgba(245,156,35,.35); }
        .inprogress .stat-hero-icon { background: #1a56db; color: #fff; box-shadow: 0 4px 14px rgba(26,86,219,.35); }
        .resolved  .stat-hero-icon { background: #22cc77; color: #fff; box-shadow: 0 4px 14px rgba(34,204,119,.35); }

        .stat-hero-value {
            font-family: 'Sora', sans-serif; font-size: 56px; font-weight: 800; line-height: 1;
            margin-bottom: 6px;
        }
        .pending   .stat-hero-value  { color: #c47200; }
        .inprogress .stat-hero-value { color: #1a56db; }
        .resolved  .stat-hero-value  { color: #128548; }

        .stat-hero-label { font-size: 17px; font-weight: 700; color: #0d0e2e; margin-bottom: 3px; }
        .stat-hero-sub   { font-size: 14px; color: #8890b8; }

        /* Decorative blob */
        .stat-hero-card::after {
            content: '';
            position: absolute; bottom: -18px; right: -18px;
            width: 90px; height: 90px; border-radius: 50%;
            opacity: .12;
        }
        .pending::after    { background: #f59c23; }
        .inprogress::after { background: #1a56db; }
        .resolved::after   { background: #22cc77; }
        /* Turnaround — purple */
        .stat-hero-card.turnaround {
            background: linear-gradient(135deg, #f6f0ff 0%, #ede5ff 100%);
            border-color: #c4a0f7;
        }
        .turnaround .stat-hero-icon { background: #8b00c7; color: #fff; box-shadow: 0 4px 14px rgba(139,0,199,.35); }
        .turnaround .stat-hero-value { color: #6b00a0; font-size: 36px; line-height: 1.1; padding-top: 4px; }
        .turnaround::after { background: #8b00c7; }
        .turnaround .stat-hero-bar-fill { background: #8b00c7; }
        .tat-trend-pill {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 800; padding: 2px 9px;
            border-radius: 20px; margin-top: 6px;
        }
        .tat-trend-faster { background: #e3faee; color: #128548; border: 1px solid #a3e8c0; }
        .tat-trend-slower { background: #fff0f0; color: #c0001a; border: 1px solid #f7a0aa; }
        .tat-trend-same   { background: #f0f2f8; color: #8890b8; border: 1px solid #d0d4e8; }

        /* Progress bar under stats */
        .stat-hero-bar { margin-top: 14px; }
        .stat-hero-bar-track { height: 5px; border-radius: 99px; background: rgba(0,0,0,.08); overflow: hidden; }
        .stat-hero-bar-fill  { height: 100%; border-radius: 99px; transition: width .6s ease; }
        .pending   .stat-hero-bar-fill  { background: #f59c23; }
        .inprogress .stat-hero-bar-fill { background: #1a56db; }
        .resolved  .stat-hero-bar-fill  { background: #22cc77; }
        .stat-hero-bar-label { font-size: 13px; color: #8890b8; margin-top: 5px; }

        /* ── Chart card ── */
        .chart-card {
            background: #fff; border: 1.5px solid #e8eaf0;
            border-radius: 16px; padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
            margin-bottom: 24px;
        }
        .chart-card-header {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px; margin-bottom: 18px;
        }
        .chart-card-title {
            font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800;
            color: #0d0e2e; display: flex; align-items: center; gap: 8px;
        }
        .chart-tabs { display: flex; gap: 4px; background: #f0f2f8; border-radius: 10px; padding: 3px; }
        .chart-tab {
            padding: 5px 14px; border-radius: 7px; font-size: 14px; font-weight: 700;
            border: none; cursor: pointer; color: #8890b8; background: transparent;
            transition: background .18s, color .18s;
        }
        .chart-tab.active { background: #fff; color: #0800a0; box-shadow: 0 1px 4px rgba(0,0,0,.1); }

        /* ── Bottom row ── */
        .section-card {
            background: #fff; border: 1.5px solid #e8eaf0;
            border-radius: 16px; padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
            height: 100%;
        }
        .section-title {
            font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 800;
            color: #0d0e2e; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-title-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }

        .rpt-row {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 0; border-bottom: 1px solid #f4f5fb;
        }
        .rpt-row:last-child { border-bottom: none; }

        .rpt-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .rpt-title {
            font-size: 16px; font-weight: 600; color: #0d0e2e;
            flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .rpt-badge {
            display: inline-flex; align-items: center;
            padding: 3px 12px; border-radius: 20px;
            font-size: 13px; font-weight: 700; flex-shrink: 0;
        }
        .rpt-date { font-size: 13px; color: #b0b8d8; white-space: nowrap; flex-shrink: 0; }

        .view-all-link {
            display: block; text-align: right;
            margin-top: 14px; font-size: 15px;
            color: #1a56db; font-weight: 700; text-decoration: none;
        }
        .view-all-link:hover { text-decoration: underline; }

        /* Announcement pill */
        .ann-row {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 9px 0; border-bottom: 1px solid #f4f5fb;
        }
        .ann-row:last-child { border-bottom: none; }
        .ann-icon {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .ann-title { font-size: 16px; font-weight: 600; color: #0d0e2e; margin-bottom: 2px; }
        .ann-meta  { font-size: 13px; color: #8890b8; }

        /* Total badge in header */
        .total-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 14px; border-radius: 20px;
            background: #e8f0fe; color: #1a56db;
            font-size: 14px; font-weight: 700;
        }

        /* ── Admin Dark Mode Toggle ── */
        #adminDarkToggle {
            position: fixed;
            bottom: 28px;
            left: 28px;
            z-index: 9001;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid rgba(245, 204, 0, 0.35);
            background: linear-gradient(135deg, #0800a0, #04005a);
            color: #f5cc00;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(8, 0, 160, 0.40);
            transition: transform  0.22s cubic-bezier(0.22, 0.68, 0, 1.2),
                        box-shadow 0.22s ease,
                        background 0.22s ease,
                        border-color 0.22s ease;
            -webkit-tap-highlight-color: transparent;
            font-size: 18px;
        }
        #adminDarkToggle:hover {
            transform: scale(1.12) rotate(-15deg);
            box-shadow: 0 10px 30px rgba(8, 0, 160, 0.55);
        }
        #adminDarkToggle:active { transform: scale(0.94); }
        body.dark-mode #adminDarkToggle {
            background: linear-gradient(135deg, #f5cc00, #d4a800);
            color: #04005a;
            border-color: rgba(245, 204, 0, 0.65);
            box-shadow: 0 6px 20px rgba(245, 204, 0, 0.35);
        }
        body.dark-mode #adminDarkToggle:hover {
            box-shadow: 0 10px 30px rgba(245, 204, 0, 0.50);
        }
        #adminDarkToggle .adt-moon { display: block; }
        #adminDarkToggle .adt-sun  { display: none;  }
        body.dark-mode #adminDarkToggle .adt-moon { display: none;  }
        body.dark-mode #adminDarkToggle .adt-sun  { display: block; }
        .dark-mode-fab { display: none !important; }

        /* ── Dark Mode overrides ── */
        body.dark-mode {
            background: #0c0d1e !important;
            color: #e0e2f5;
        }
        body.dark-mode .dash-title       { color: #e8eaf5; }
        body.dark-mode .dash-sub         { color: #6a72a8; }
        body.dark-mode .refresh-btn      { background: #181934; border-color: #2a2d52; color: #9ca3d4; }
        body.dark-mode .refresh-btn:hover{ border-color: #5b5fd4; color: #a0a8f0; }
        body.dark-mode .total-pill       { background: #1a1d3a; color: #7b8ef7; }

        body.dark-mode .stat-hero-card.pending    { background: linear-gradient(135deg,#1e1400 0%,#201700 100%); border-color: #5a3a00; }
        body.dark-mode .stat-hero-card.inprogress { background: linear-gradient(135deg,#0b1330 0%,#0d1640 100%); border-color: #1e3a7a; }
        body.dark-mode .stat-hero-card.resolved   { background: linear-gradient(135deg,#001810 0%,#001e14 100%); border-color: #0e4d28; }
        body.dark-mode .stat-hero-card.turnaround { background: linear-gradient(135deg,#140020 0%,#1a002a 100%); border-color: #4a007a; }
        body.dark-mode .turnaround .stat-hero-value { color: #c97aff; }
        body.dark-mode .turnaround .stat-hero-icon  { background: #6a00a8; box-shadow: 0 4px 14px rgba(139,0,199,.5); }
        body.dark-mode .turnaround .stat-hero-bar-fill { background: #a040e8; }
        body.dark-mode .turnaround::after           { background: #a040e8; opacity: .18; }

        /* Trend pills — dark mode */
        body.dark-mode .tat-trend-faster { background: #0a2e1a; color: #4ade80; border: 1px solid #166534; }
        body.dark-mode .tat-trend-slower { background: #2e0a0a; color: #f87171; border: 1px solid #7f1d1d; }
        body.dark-mode .tat-trend-same   { background: #1a1d38; color: #8890b8; border: 1px solid #2a2d52; }

        body.dark-mode .stat-hero-label           { color: #cdd0ef; }
        body.dark-mode .stat-hero-sub             { color: #5a6090; }
        body.dark-mode .stat-hero-bar-label       { color: #5a6090; }

        body.dark-mode .chart-card,
        body.dark-mode .section-card { background: #12142a; border-color: #1e2040; box-shadow: 0 2px 12px rgba(0,0,0,.4); }
        body.dark-mode .chart-card-title,
        body.dark-mode .section-title { color: #c8cbed; }
        body.dark-mode .chart-tabs    { background: #0c0d1e; }
        body.dark-mode .chart-tab     { color: #4a5290; }
        body.dark-mode .chart-tab.active { background: #1a1d3c; color: #8890f8; box-shadow: 0 1px 4px rgba(0,0,0,.4); }

        body.dark-mode .rpt-row       { border-bottom-color: #1a1d38; }
        body.dark-mode .rpt-title     { color: #c8cbed; }
        body.dark-mode .rpt-date      { color: #3e4468; }
        body.dark-mode .ann-row       { border-bottom-color: #1a1d38; }
        body.dark-mode .ann-title     { color: #c8cbed; }
        body.dark-mode .ann-meta      { color: #4a5290; }
        body.dark-mode .view-all-link { color: #6a7aef; }

        body.dark-mode #logoutModal > div { background: #12142a; }
        body.dark-mode #logoutModal div[style*="font-size:16px"] { color: #e0e2f5 !important; }
        body.dark-mode #logoutModal div[style*="font-size:13px"] { color: #6a72a8 !important; }
        body.dark-mode #logoutModal button { background: #1a1d38 !important; border-color: #2a2d52 !important; color: #8890c8 !important; }
    </style>
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
                <i class="fa fa-line-chart" style="color:#0800a0;"></i>
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
        <div style="height:270px; position:relative;">
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
                    <div class="ann-icon" style="background:<?= $is_urgent ? '#fff0f0' : '#f0f4ff' ?>; color:<?= $is_urgent ? '#c0001a' : '#0800a0' ?>;">
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
<script>
// ── Admin Dark Mode Controller ─────────────────────────────────────────────
// Uses sessionStorage so dark mode persists when navigating between admin
// pages (dashboard → report → program) but resets when browser is closed.
// Resident side controls it via BroadcastChannel('kbc-theme').
// ──────────────────────────────────────────────────────────────────────────
(function () {
    var SESSION_KEY = 'kbc_admin_dark';

    function applyDark(on) {
        document.body.classList.toggle('dark-mode', on);
        try { sessionStorage.setItem(SESSION_KEY, on ? '1' : '0'); } catch(e) {}
    }

    // Apply saved session state immediately on load
    try {
        if (sessionStorage.getItem(SESSION_KEY) === '1') {
            document.body.classList.add('dark-mode');
        }
    } catch(e) {}

    // Manual toggle from FAB
    window.toggleAdminDarkMode = function () {
        applyDark(!document.body.classList.contains('dark-mode'));
    };
    window.toggleDarkMode = window.toggleAdminDarkMode;

    // Listen for resident-side broadcasts
    try {
        var ch = new BroadcastChannel('kbc-theme');
        ch.onmessage = function (e) {
            if (e.data && typeof e.data.dark !== 'undefined') {
                applyDark(!!e.data.dark);
            }
        };
    } catch(e) {}
})();
</script>
</body>
</html>