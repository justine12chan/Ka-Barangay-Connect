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

// --- Stats ---
$r_pending   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='pending'"))[0]     ?? 0;
$r_progress  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='in-progress'"))[0] ?? 0;
$r_resolved  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='resolved'"))[0]    ?? 0;
$r_total     = $r_pending + $r_progress + $r_resolved;

$p_planned   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]    ?? 0;
$p_ongoing   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]    ?? 0;
$p_completed = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0]  ?? 0;

$ann_total   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements"))[0]                  ?? 0;
$ann_urgent  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements WHERE is_urgent=1"))[0] ?? 0;

// --- Recent records ---
$recent_reports = executeQuery("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5");
$recent_ann     = executeQuery("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");

// --- Monthly chart data (last 12 months) ---
$monthly_data = [];
$monthly_res  = executeQuery("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
           COUNT(*) AS total,
           SUM(status='pending')     AS pending,
           SUM(status='in-progress') AS inprogress,
           SUM(status='resolved')    AS resolved
    FROM reports
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY ym ORDER BY ym ASC
");
if ($monthly_res) {
    while ($row = mysqli_fetch_assoc($monthly_res)) $monthly_data[] = $row;
}

// --- Category breakdown ---
$category_data = [];
$cat_res = executeQuery("SELECT category, COUNT(*) AS cnt FROM reports GROUP BY category ORDER BY cnt DESC");
if ($cat_res) {
    while ($row = mysqli_fetch_assoc($cat_res)) $category_data[$row['category']] = (int) $row['cnt'];
}
?>

    <div class="container-fluid p-3 p-md-4">
        <div class="row g-3">

            <!-- SIDEBAR -->
            <div class="col-12 col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-top">
                        <div class="sidebar-logo-wrap">
                            <img src="assets/img/logo.png" alt="Logo"
                                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
                        </div>
                        <div>
                            <div class="sidebar-admin">Admin Panel</div>
                            <div class="sidebar-name" id="sidebarUsername">San Bartolome</div>
                        </div>
                    </div>
                    <div class="sidebar-footer">
                        <a href="#" class="sidebar-btn profile"><i class="fa fa-user"></i> Profile</a>
                        <a href="index.html" class="sidebar-btn logout"
                           onclick="return confirmLogout()"><i class="fa fa-sign-out"></i> Logout</a>
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

                    <!-- Stats -->
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

                    <!-- Content Area -->
                    <div class="content-area">

                        <!-- Overview header -->
                        <div class="content-header">
                            <span class="content-eyebrow">Overview</span>
                            <div class="content-line"></div>
                        </div>

                        <!-- Summary mini-cards -->
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">

                            <div style="flex:1; min-width:140px; background:var(--faint);
                                        border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted);
                                            text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">
                                    Total Reports
                                </div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px;
                                            font-weight:800; color:var(--blue-main);"><?= $r_total ?></div>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint);
                                        border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted);
                                            text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">
                                    Active Programs
                                </div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px;
                                            font-weight:800; color:#7c3aed;"><?= $p_ongoing + $p_planned ?></div>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint);
                                        border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted);
                                            text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">
                                    Announcements
                                </div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px;
                                            font-weight:800; color:#c47200;"><?= $ann_total ?></div>
                                <?php if ($ann_urgent > 0): ?>
                                    <div style="font-size:11px; color:#c0001a; margin-top:3px;">
                                        <?= $ann_urgent ?> urgent
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="flex:1; min-width:140px; background:var(--faint);
                                        border:1.5px solid var(--border); border-radius:14px; padding:16px 18px;">
                                <div style="font-size:11px; font-weight:700; color:var(--muted);
                                            text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px;">
                                    Completed Programs
                                </div>
                                <div style="font-family:'Sora',sans-serif; font-size:26px;
                                            font-weight:800; color:#128548;"><?= $p_completed ?></div>
                            </div>

                        </div>

                           
                        <!-- Chart: Reports Over Time -->
                        <div style="margin-top:20px;">
                            <div style="font-size:11px; font-weight:700; color:var(--muted);
                                        text-transform:uppercase; letter-spacing:.08em; margin-bottom:14px;
                                        display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <i class="fa fa-bar-chart" style="color:var(--blue-main);"></i> Reports Over Time
                                <div style="display:flex; gap:6px; margin-left:auto;">
                                    <button class="chart-view-btn active" data-view="month"
                                            onclick="switchChartView('month',this)"
                                            style="font-size:10px; padding:3px 10px; border-radius:20px;
                                                   border:1.5px solid var(--border); background:var(--blue-main);
                                                   color:#fff; cursor:pointer; font-weight:700;">Monthly</button>
                                    <button class="chart-view-btn" data-view="category"
                                            onclick="switchChartView('category',this)"
                                            style="font-size:10px; padding:3px 10px; border-radius:20px;
                                                   border:1.5px solid var(--border); background:var(--faint);
                                                   color:var(--muted); cursor:pointer; font-weight:700;">By Category</button>
                                    <button class="chart-view-btn" data-view="status"
                                            onclick="switchChartView('status',this)"
                                            style="font-size:10px; padding:3px 10px; border-radius:20px;
                                                   border:1.5px solid var(--border); background:var(--faint);
                                                   color:var(--muted); cursor:pointer; font-weight:700;">By Status</button>
                                </div>
                            </div>
                            <div style="background:#fff; border:1.5px solid var(--border);
                                        border-radius:14px; padding:16px; position:relative; height:260px;">
                                <canvas id="reportsChart"></canvas>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- FAB -->
    <button class="rpt-fab" title="Create New" onclick="openUnifiedModal()">
        <i class="fa fa-plus"></i>
    </button>

    <!-- ══ UNIFIED CREATE MODAL ══ -->
    <div class="rpt-modal-overlay" id="unifiedModal"
         onclick="if(event.target===this) closeUnifiedModal()">
        <div class="rpt-modal">

            <!-- Step 1: Type picker -->
            <div id="uStep1">
                <div class="rpt-modal-header">
                    <div>
                        <div class="rpt-modal-eyebrow">Admin Action</div>
                        <div class="rpt-modal-title">What would you like to create?</div>
                    </div>
                    <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="rpt-modal-body" style="padding-bottom:24px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px;">
                        <button type="button" class="u-type-tile" onclick="chooseType('announcement')">
                            <div class="u-type-icon" style="background:#fff3cd; color:#856404;">
                                <i class="fa fa-bullhorn"></i>
                            </div>
                            <div class="u-type-label">Announcement</div>
                            <div class="u-type-sub">Post an official notice to residents</div>
                        </button>
                        <button type="button" class="u-type-tile" onclick="chooseType('program')">
                            <div class="u-type-icon" style="background:#e8f0fe; color:#1a56db;">
                                <i class="fa fa-briefcase"></i>
                            </div>
                            <div class="u-type-label">Project</div>
                            <div class="u-type-sub">Add a new barangay project</div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2a: Announcement form -->
            <div id="uStep2Ann" style="display:none;">
                <div class="rpt-modal-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" class="u-back-btn" onclick="backToTypePicker()">
                            <i class="fa fa-arrow-left"></i>
                        </button>
                        <div>
                            <div class="rpt-modal-eyebrow">Admin Action</div>
                            <div class="rpt-modal-title">Post Announcement</div>
                        </div>
                    </div>
                    <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="report_admin.php" enctype="multipart/form-data">
                    <input type="hidden" name="add_announcement" value="1">
                    <div class="rpt-modal-body">
                        <div class="field-group">
                            <label class="field-label">Title</label>
                            <input type="text" class="field-input" name="ann_title"
                                   placeholder="Announcement title..." required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Message</label>
                            <textarea class="field-input" name="ann_body" rows="4"
                                      placeholder="Write the full announcement here..."
                                      required style="resize:vertical;"></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Posted By</label>
                            <input type="text" class="field-input" name="posted_by"
                                   placeholder="e.g. Barangay Admin" value="Barangay Admin">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Image (optional)</label>
                            <div onclick="document.getElementById('annFileInput').click()"
                                 style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                                        border:1.5px dashed var(--border); border-radius:10px;
                                        cursor:pointer; background:var(--faint);">
                                <i class="fa fa-image" style="font-size:18px; color:var(--muted);"></i>
                                <span style="font-size:13px; color:var(--muted);" id="annFileLabel">Choose image...</span>
                            </div>
                            <input type="file" id="annFileInput" name="ann_image" accept="image/*"
                                   style="display:none;"
                                   onchange="document.getElementById('annFileLabel').textContent =
                                       this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                            <input type="hidden" name="is_urgent" value="0" id="urgentHidden">
                            <button type="button" id="urgentBtn" onclick="toggleUrgent()"
                                style="display:flex; align-items:center; gap:7px; padding:8px 14px;
                                       border-radius:8px; border:1.5px solid #ffd580; background:#fff3e0;
                                       color:#c47200; font-size:12px; font-weight:700; cursor:pointer;">
                                <i class="fa fa-exclamation-triangle"></i>
                                <span id="urgentBtnLabel">Mark as Urgent</span>
                            </button>
                            <span style="font-size:11.5px; color:var(--muted);">Toggle to flag as urgent</span>
                        </div>
                    </div>
                    <div class="rpt-modal-footer">
                        <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                        <button type="submit" class="rpt-modal-submit">
                            <i class="fa fa-paper-plane"></i> Post Announcement
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 2b: Program form -->
            <div id="uStep2Prog" style="display:none;">
                <div class="rpt-modal-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" class="u-back-btn" onclick="backToTypePicker()">
                            <i class="fa fa-arrow-left"></i>
                        </button>
                        <div>
                            <div class="rpt-modal-eyebrow">Admin Action</div>
                            <div class="rpt-modal-title">Add New Program</div>
                        </div>
                    </div>
                    <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="admin_program.php" enctype="multipart/form-data">
                    <input type="hidden" name="add_program" value="1">
                    <div class="rpt-modal-body">
                        <div class="field-group">
                            <label class="field-label">Program Title</label>
                            <input type="text" class="field-input" name="title"
                                   placeholder="Enter program title..." required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Department</label>
                            <input type="text" class="field-input" name="department"
                                   placeholder="e.g. Health Department" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Description</label>
                            <textarea class="field-input" name="description"
                                      placeholder="Describe the program and its goals..."
                                      required style="resize:vertical;"></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Start Date</label>
                            <input type="date" class="field-input" name="start_date">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Image (optional)</label>
                            <div onclick="document.getElementById('uProgFileInput').click()"
                                 style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                                        border:1.5px dashed var(--border); border-radius:10px;
                                        cursor:pointer; background:var(--faint);">
                                <i class="fa fa-image" style="font-size:18px; color:var(--muted);"></i>
                                <span style="font-size:13px; color:var(--muted);" id="uProgFileLabel">Choose image...</span>
                            </div>
                            <input type="file" id="uProgFileInput" name="prog_image" accept="image/*"
                                   style="display:none;"
                                   onchange="document.getElementById('uProgFileLabel').textContent =
                                       this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                        </div>
                    </div>
                    <div class="rpt-modal-footer">
                        <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                        <button type="submit" class="rpt-modal-submit">
                            <i class="fa fa-paper-plane"></i> Add Program
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
                z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:18px; padding:28px 28px 22px; max-width:340px;
                    width:90%; box-shadow:0 8px 40px rgba(0,0,0,0.18); text-align:center;">
            <div style="width:52px; height:52px; border-radius:50%; background:#fff0f0;
                        display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <i class="fa fa-sign-out" style="font-size:22px; color:#c0001a;"></i>
            </div>
            <div style="font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px;">Log Out?</div>
            <div style="font-size:13px; color:#8890b8; margin-bottom:20px;">
                Are you sure you want to log out of the admin panel?
            </div>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button onclick="document.getElementById('logoutModal').style.display='none'"
                        style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0;
                               background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">
                    Cancel
                </button>
                <a href="index.html" onclick="sessionStorage.clear()"
                   style="flex:1; padding:10px; border-radius:10px; background:#c0001a; color:#fff;
                          font-weight:700; cursor:pointer; font-size:13px; text-decoration:none;
                          display:flex; align-items:center; justify-content:center;">
                    Log Out
                </a>
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

    // Unified modal
    function openUnifiedModal() {
        document.getElementById('uStep1').style.display     = '';
        document.getElementById('uStep2Ann').style.display  = 'none';
        document.getElementById('uStep2Prog').style.display = 'none';
        document.getElementById('unifiedModal').classList.add('open');
    }
    function closeUnifiedModal() {
        document.getElementById('unifiedModal').classList.remove('open');
    }
    function chooseType(type) {
        document.getElementById('uStep1').style.display = 'none';
        document.getElementById('uStep2Ann').style.display  = (type === 'announcement') ? '' : 'none';
        document.getElementById('uStep2Prog').style.display = (type === 'program')       ? '' : 'none';
    }
    function backToTypePicker() {
        document.getElementById('uStep1').style.display     = '';
        document.getElementById('uStep2Ann').style.display  = 'none';
        document.getElementById('uStep2Prog').style.display = 'none';
    }

    let urgentOn = false;
    function toggleUrgent() {
        urgentOn = !urgentOn;
        const btn   = document.getElementById('urgentBtn');
        const label = document.getElementById('urgentBtnLabel');
        document.getElementById('urgentHidden').value = urgentOn ? '1' : '0';
        btn.style.background  = urgentOn ? '#c0001a' : '#fff3e0';
        btn.style.color       = urgentOn ? '#fff'    : '#c47200';
        btn.style.borderColor = urgentOn ? '#c0001a' : '#ffd580';
        label.textContent     = urgentOn ? '⚠ Urgent ON' : 'Mark as Urgent';
    }

    // ── Chart setup ──
    const monthlyData = <?= json_encode($monthly_data) ?>;
    const monthLabels = [], totals = [], pendingArr = [], progressArr = [], resolvedArr = [];
    const now = new Date();

    for (let i = 11; i >= 0; i--) {
        const d   = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const ym  = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        const lbl = d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
        monthLabels.push(lbl);
        const found = monthlyData.find(x => x.ym === ym);
        totals.push(found ? parseInt(found.total)       : 0);
        pendingArr.push(found ? parseInt(found.pending)     : 0);
        progressArr.push(found ? parseInt(found.inprogress) : 0);
        resolvedArr.push(found ? parseInt(found.resolved)   : 0);
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
        const cats   = <?= json_encode($category_data) ?>;
        const labels = Object.keys(cats), data = Object.values(cats);
        const colors = ['#3b7ef8', '#f59c23', '#22cc77', '#c0001a', '#8b00c7'];
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
                    backgroundColor: ['#f59c23', '#3b7ef8', '#22cc77'],
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
            b.style.background = 'var(--faint)';
            b.style.color      = 'var(--muted)';
        });
        btn.style.background = 'var(--blue-main)';
        btn.style.color      = '#fff';
        if (chartInstance) chartInstance.destroy();
        const ctx = document.getElementById('reportsChart').getContext('2d');
        const cfg = view === 'month' ? buildMonthChart()
                  : view === 'category' ? buildCategoryChart()
                  : buildStatusChart();
        chartInstance = new Chart(ctx, cfg);
    }

    window.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('reportsChart').getContext('2d');
        chartInstance = new Chart(ctx, buildMonthChart());
    });
    </script>
</body>
</html>