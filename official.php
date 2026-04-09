<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-official">

<?php
require_once 'connection.php';

// ── Dashboard stats
$r_pending    = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='pending'"))[0]     ?? 0;
$r_progress   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='in-progress'"))[0] ?? 0;
$r_resolved   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='resolved'"))[0]    ?? 0;
$r_total      = $r_pending + $r_progress + $r_resolved;

$p_planned    = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]    ?? 0;
$p_ongoing    = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]    ?? 0;
$p_completed  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0]  ?? 0;

$ann_total    = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements"))[0] ?? 0;
$ann_urgent   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements WHERE is_urgent=1"))[0] ?? 0;

// ── Recent 5 reports
$recent_reports = executeQuery("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5");

// ── Recent 3 announcements
$recent_ann = executeQuery("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");

// ── Monthly reports for chart (last 12 months)
$monthly_data = [];
$monthly_res = executeQuery("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as ym,
           COUNT(*) as total,
           SUM(status='pending') as pending,
           SUM(status='in-progress') as inprogress,
           SUM(status='resolved') as resolved
    FROM reports
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY ym ORDER BY ym ASC
");
if ($monthly_res) {
    while ($mrow = mysqli_fetch_assoc($monthly_res)) $monthly_data[] = $mrow;
}

// ── Users table (officials) — safe check first
$users_list = [];
$table_check = executeQuery("SHOW TABLES LIKE 'officials'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $users_res = executeQuery("SELECT * FROM officials ORDER BY created_at DESC");
    if ($users_res) {
        while ($urow = mysqli_fetch_assoc($users_res)) $users_list[] = $urow;
    }
}

// ── Category breakdown for chart
$category_data = [];
$cat_res = executeQuery("SELECT category, COUNT(*) as cnt FROM reports GROUP BY category ORDER BY cnt DESC");
if ($cat_res) {
    while ($crow = mysqli_fetch_assoc($cat_res)) $category_data[$crow['category']] = (int)$crow['cnt'];
}
?>

    <div class="container-fluid p-3 p-md-4">
        <div class="row g-3">

            <!-- SIDEBAR -->
            <div class="col-12 col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-top">
                        <div class="sidebar-logo-wrap">
                            <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
                        </div>
                        <div>
                            <div class="sidebar-admin">Admin Panel</div>
                            <div class="sidebar-name" id="sidebarUsername">San Bartolome</div>
                        </div>
                    </div>
                    <div class="sidebar-footer">
                        <a href="#" class="sidebar-btn profile"><i class="fa fa-user"></i> Profile</a>
                        <a href="index.html" class="sidebar-btn logout" onclick="return confirmLogout()"><i class="fa fa-sign-out"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-12 col-lg-9">
                <div class="main-card">

                    <!-- Top Nav -->
                    <div class="top-nav">
                        <div class="top-nav-left">
                            <a href="index.html" class="back-btn light">&#8592; Back</a>
                            <span class="top-nav-title">Dashboard Overview</span>
                        </div>
                        <div class="top-nav-pills">
                            <a href="official.php"      class="nav-pill active">Dashboard</a>
                            <a href="report_admin.php"  class="nav-pill">Report</a>
                            <a href="admin_program.php" class="nav-pill">Programs</a>
                        </div>
                    </div>

                    <!-- Stats: Reports -->
                    <div class="stats-row">
                        <div class="stat-box" style="border-left-color:#f59c23;">
                            <div class="stat-label">Pending</div>
                            <div class="stat-num" style="color:#f59c23;"><?= $r_pending ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:var(--blue-main);">
                            <div class="stat-label">In Progress</div>
                            <div class="stat-num"><?= $r_progress ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:#22cc77;">
                            <div class="stat-label">Resolved</div>
                            <div class="stat-num" style="color:#22cc77;"><?= $r_resolved ?></div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="content-area">

                        <!-- Overview mini-stats row -->
                        <div class="content-header">
                            <span class="content-eyebrow">Overview</span>
                            <div class="content-line"></div>
                        </div>

                        <!-- Summary cards -->
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">

                            <div style="flex:1; min-width:140px; background:var(--faint); border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">Total Reports</div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px; font-weight:800; color:var(--blue-main);"><?= $r_total ?></div>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint); border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">Active Programs</div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px; font-weight:800; color:#7c3aed;"><?= $p_ongoing + $p_planned ?></div>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint); border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">Announcements</div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px; font-weight:800; color:#c47200;"><?= $ann_total ?></div>
                                <?php if ($ann_urgent > 0): ?>
                                <div style="font-size:11px; color:#c0001a; margin-top:3px;"><?= $ann_urgent ?> urgent</div>
                                <?php endif; ?>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint); border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">Completed Programs</div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px; font-weight:800; color:#128548;"><?= $p_completed ?></div>
                            </div>

                        </div>

                        <!-- Two-column: Recent Reports + Recent Announcements -->
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">

                            <!-- Recent Reports -->
                            <div style="flex:1; min-width:260px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                                    <i class="fa fa-flag" style="color:var(--blue-main);"></i> Recent Reports
                                    <a href="report_admin.php" style="margin-left:auto; font-size:11px; color:var(--blue-main); text-decoration:none;">View All →</a>
                                </div>
                                <?php
                                $st_colors = [
                                    'pending'     => ['bg'=>'#fff3e0','color'=>'#c47200'],
                                    'in-progress' => ['bg'=>'#e8f0fe','color'=>'#1a56db'],
                                    'resolved'    => ['bg'=>'#e6faed','color'=>'#128548'],
                                ];
                                $st_labels = ['pending'=>'Pending','in-progress'=>'In Progress','resolved'=>'Resolved'];
                                if ($recent_reports && mysqli_num_rows($recent_reports) > 0):
                                    while ($rpt = mysqli_fetch_assoc($recent_reports)):
                                        $sc = $st_colors[$rpt['status']] ?? $st_colors['pending'];
                                        $sl = $st_labels[$rpt['status']] ?? 'Pending';
                                        $reporter_disp = $rpt['is_anonymous'] ? 'Anonymous' : htmlspecialchars($rpt['reporter']);
                                ?>
                                <div style="background:#fff; border:1.5px solid var(--border); border-radius:12px; padding:12px 14px; margin-bottom:8px;">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:4px;">
                                        <span style="font-size:12px; font-weight:600; color:var(--text); flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($rpt['title']) ?>
                                        </span>
                                        <span style="font-size:10px; font-weight:700; padding:2px 10px; border-radius:20px; flex-shrink:0; background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>;">
                                            <?= $sl ?>
                                        </span>
                                    </div>
                                    <div style="font-size:11px; color:var(--muted);">
                                        <i class="fa fa-user" style="margin-right:3px;"></i><?= $reporter_disp ?>
                                        &nbsp;·&nbsp;
                                        <i class="fa fa-tag" style="margin-right:3px;"></i><?= htmlspecialchars($rpt['category']) ?>
                                    </div>
                                </div>
                                <?php endwhile; else: ?>
                                <div style="text-align:center; padding:20px; color:var(--muted); font-size:13px;">No reports yet.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Recent Announcements -->
                            <div style="flex:1; min-width:260px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                                    <i class="fa fa-bell" style="color:#c47200;"></i> Recent Announcements
                                    <a href="announcement.php" style="margin-left:auto; font-size:11px; color:var(--blue-main); text-decoration:none;">View All →</a>
                                </div>
                                <?php
                                if ($recent_ann && mysqli_num_rows($recent_ann) > 0):
                                    while ($ann = mysqli_fetch_assoc($recent_ann)):
                                        $date_fmt = date('M j, Y', strtotime($ann['created_at']));
                                ?>
                                <div style="background:#fff; border:1.5px solid var(--border); border-radius:12px; padding:12px 14px; margin-bottom:8px;">
                                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:4px;">
                                        <span style="font-size:12px; font-weight:600; color:var(--text); flex:1; min-width:0;">
                                            <?= htmlspecialchars($ann['title']) ?>
                                        </span>
                                        <?php if ($ann['is_urgent']): ?>
                                        <span style="font-size:10px; font-weight:700; padding:2px 10px; border-radius:20px; flex-shrink:0; background:#fff0f0; color:#c0001a;">URGENT</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--muted);">
                                        <i class="fa fa-user" style="margin-right:3px;"></i><?= htmlspecialchars($ann['posted_by']) ?>
                                        &nbsp;·&nbsp;
                                        <?= $date_fmt ?>
                                    </div>
                                </div>
                                <?php endwhile; else: ?>
                                <div style="text-align:center; padding:20px; color:var(--muted); font-size:13px;">No announcements yet.</div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- CHART: Reports Over Time -->
                        <div style="margin-top:20px;">
                            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:14px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <i class="fa fa-bar-chart" style="color:var(--blue-main);"></i> Reports Over Time
                                <div style="display:flex; gap:6px; margin-left:auto;">
                                    <button class="chart-view-btn active" data-view="month" onclick="switchChartView('month',this)" style="font-size:10px;padding:3px 10px;border-radius:20px;border:1.5px solid var(--border);background:var(--blue-main);color:#fff;cursor:pointer;font-weight:700;">Monthly</button>
                                    <button class="chart-view-btn" data-view="category" onclick="switchChartView('category',this)" style="font-size:10px;padding:3px 10px;border-radius:20px;border:1.5px solid var(--border);background:var(--faint);color:var(--muted);cursor:pointer;font-weight:700;">By Category</button>
                                    <button class="chart-view-btn" data-view="status" onclick="switchChartView('status',this)" style="font-size:10px;padding:3px 10px;border-radius:20px;border:1.5px solid var(--border);background:var(--faint);color:var(--muted);cursor:pointer;font-weight:700;">By Status</button>
                                </div>
                            </div>
                            <div style="background:#fff; border:1.5px solid var(--border); border-radius:14px; padding:16px; position:relative; height:260px;">
                                <canvas id="reportsChart"></canvas>
                            </div>
                        </div>

                        <!-- USERS TABLE -->
                        <div style="margin-top:20px;">
                            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                <i class="fa fa-users" style="color:#7c3aed;"></i> Officials / Users
                            </div>
                            <div style="background:#fff; border:1.5px solid var(--border); border-radius:14px; overflow:hidden;">
                                <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                                    <thead>
                                        <tr style="background:var(--faint); border-bottom:1.5px solid var(--border);">
                                            <th style="padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.06em;">#</th>
                                            <th style="padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.06em;">Name</th>
                                            <th style="padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.06em;">Position</th>
                                            <th style="padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.06em;">Username</th>
                                            <th style="padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.06em;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users_list)): $unum = 1; foreach ($users_list as $u): ?>
                                        <tr style="border-bottom:1px solid var(--border);">
                                            <td style="padding:10px 14px; color:var(--muted);"><?= $unum++ ?></td>
                                            <td style="padding:10px 14px; font-weight:600; color:var(--text);">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <div style="width:28px;height:28px;border-radius:50%;background:var(--blue-main);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                                        <?= strtoupper(substr($u['full_name'] ?? $u['username'] ?? '?', 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($u['full_name'] ?? $u['username'] ?? '—') ?>
                                                </div>
                                            </td>
                                            <td style="padding:10px 14px; color:var(--text);"><?= htmlspecialchars($u['position'] ?? '—') ?></td>
                                            <td style="padding:10px 14px; color:var(--muted); font-family:monospace;"><?= htmlspecialchars($u['username'] ?? '—') ?></td>
                                            <td style="padding:10px 14px;">
                                                <span style="font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;background:#e6faed;color:#128548;">Active</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="5" style="padding:24px; text-align:center; color:var(--muted); font-size:13px;">No officials found. Create an <code>officials</code> table to populate this list.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:18px;padding:28px 28px 22px;max-width:340px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,0.18);text-align:center;">
            <div style="width:52px;height:52px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fa fa-sign-out" style="font-size:22px;color:#c0001a;"></i>
            </div>
            <div style="font-size:16px;font-weight:800;color:#1a2340;margin-bottom:6px;">Log Out?</div>
            <div style="font-size:13px;color:#8890b8;margin-bottom:20px;">Are you sure you want to log out of the admin panel?</div>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button onclick="document.getElementById('logoutModal').style.display='none'" style="flex:1;padding:10px;border-radius:10px;border:1.5px solid #e0e4f0;background:#f7f8fc;color:#4a5280;font-weight:700;cursor:pointer;font-size:13px;">Cancel</button>
                <a href="index.html" onclick="sessionStorage.clear()" style="flex:1;padding:10px;border-radius:10px;background:#c0001a;color:#fff;font-weight:700;cursor:pointer;font-size:13px;text-decoration:none;display:flex;align-items:center;justify-content:center;">Log Out</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    // Show username from sessionStorage
    const adminUser = sessionStorage.getItem('adminUser');
    if (adminUser) {
        const el = document.getElementById('sidebarUsername');
        if (el) el.textContent = adminUser.charAt(0).toUpperCase() + adminUser.slice(1);
    }

    function confirmLogout() {
        document.getElementById('logoutModal').style.display = 'flex';
        return false;
    }

    // ── Chart data from PHP
    const monthlyData = <?= json_encode($monthly_data) ?>;

    // Build last-12-month labels even if some months have no data
    const monthLabels = [], totals = [], pendingArr = [], progressArr = [], resolvedArr = [];
    const now = new Date();
    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const ym = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0');
        const label = d.toLocaleDateString('en-US',{month:'short',year:'2-digit'});
        monthLabels.push(label);
        const found = monthlyData.find(x => x.ym === ym);
        totals.push(found ? parseInt(found.total) : 0);
        pendingArr.push(found ? parseInt(found.pending) : 0);
        progressArr.push(found ? parseInt(found.inprogress) : 0);
        resolvedArr.push(found ? parseInt(found.resolved) : 0);
    }

    let chartInstance = null;

    function buildMonthChart() {
        return {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    { label: 'Pending',     data: pendingArr,  backgroundColor: '#f59c23', borderRadius: 4, stack: 'a' },
                    { label: 'In Progress', data: progressArr, backgroundColor: '#3b7ef8', borderRadius: 4, stack: 'a' },
                    { label: 'Resolved',    data: resolvedArr, backgroundColor: '#22cc77', borderRadius: 4, stack: 'a' },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        };
    }

    function buildCategoryChart() {
        const cats = <?= json_encode($category_data) ?>;
        const labels = Object.keys(cats), data = Object.values(cats);
        const colors = ['#3b7ef8','#f59c23','#22cc77','#c0001a','#8b00c7'];
        return {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors.slice(0, labels.length), borderWidth: 2 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } }
            }
        };
    }

    function buildStatusChart() {
        return {
            type: 'bar',
            data: {
                labels: ['Pending', 'In Progress', 'Resolved'],
                datasets: [{
                    label: 'Reports',
                    data: [<?= $r_pending ?>, <?= $r_progress ?>, <?= $r_resolved ?>],
                    backgroundColor: ['#f59c23','#3b7ef8','#22cc77'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } }, y: { grid: { display: false } } }
            }
        };
    }

    function switchChartView(view, btn) {
        document.querySelectorAll('.chart-view-btn').forEach(b => {
            b.style.background = 'var(--faint)'; b.style.color = 'var(--muted)';
        });
        btn.style.background = 'var(--blue-main)'; btn.style.color = '#fff';
        if (chartInstance) chartInstance.destroy();
        const ctx = document.getElementById('reportsChart').getContext('2d');
        const cfg = view === 'month' ? buildMonthChart() : view === 'category' ? buildCategoryChart() : buildStatusChart();
        chartInstance = new Chart(ctx, cfg);
    }

    // Init chart on load
    window.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('reportsChart').getContext('2d');
        chartInstance = new Chart(ctx, buildMonthChart());
    });
    </script>
</body>
</html>