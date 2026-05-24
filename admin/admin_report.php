<?php
session_start();
if (!isset($_SESSION['userID'])) { header("Location: admin_login.php"); exit(); }
include __DIR__ . '/../connection.php';

// --- Ensure resolved_at column exists ---
$col_rat = @mysqli_query($conn, "SHOW COLUMNS FROM `reports` LIKE 'resolved_at'");
if ($col_rat && mysqli_num_rows($col_rat) === 0) {
    mysqli_query($conn, "ALTER TABLE `reports` ADD COLUMN `resolved_at` DATETIME NULL DEFAULT NULL AFTER `status`");
}

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id      = (int) $_POST['report_id'];
    $status  = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed = ['pending', 'in-progress', 'resolved'];
    $ok = false;
    $resolved_at = null;
    if (in_array($status, $allowed) && $id > 0) {
        if ($status === 'resolved') {
            executeQuery("UPDATE reports SET status='$status', resolved_at = COALESCE(resolved_at, NOW()) WHERE id=$id");
            $row = mysqli_fetch_assoc(executeQuery("SELECT resolved_at FROM reports WHERE id=$id"));
            $resolved_at = $row['resolved_at'] ?? null;
        } else {
            executeQuery("UPDATE reports SET status='$status', resolved_at = NULL WHERE id=$id");
        }
        $ok = true;
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'report_id' => $id, 'new_status' => $status, 'resolved_at' => $resolved_at]);
        exit;
    }
    header('Location: admin_report.php');
    exit;
}

// --- Ensure report_comments has is_admin + commenter_name columns ---
$col_chk = @mysqli_query($conn, "SHOW COLUMNS FROM `report_comments` LIKE 'is_admin'");
if ($col_chk && mysqli_num_rows($col_chk) === 0) {
    mysqli_query($conn, "ALTER TABLE `report_comments` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `comment_text`");
    mysqli_query($conn, "ALTER TABLE `report_comments` ADD COLUMN `commenter_name` VARCHAR(120) NULL DEFAULT NULL AFTER `is_admin`");
}

// --- Handle admin comment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_comment'])) {
    $report_id   = (int) ($_POST['report_id'] ?? 0);
    $comment_txt = trim($_POST['comment_text'] ?? '');
    $admin_name  = mysqli_real_escape_string($conn, 'Barangay Admin');
    $new_comment = null;
    if ($report_id > 0 && $comment_txt !== '') {
        $comm_esc = mysqli_real_escape_string($conn, $comment_txt);
        executeQuery("INSERT INTO report_comments (report_id, resident_id, resident_name, comment_text, is_admin, commenter_name)
                      VALUES ($report_id, 0, 'Barangay Admin', '$comm_esc', 1, '$admin_name')");
        $new_id = mysqli_insert_id($conn);
        $new_comment = [
            'id'             => $new_id,
            'report_id'      => $report_id,
            'resident_name'  => 'Barangay Admin',
            'comment_text'   => $comment_txt,
            'is_admin'       => 1,
            'commenter_name' => 'Barangay Admin',
            'created_at'     => date('Y-m-d H:i:s'),
        ];
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $new_comment !== null, 'comment' => $new_comment]);
        exit;
    }
    header('Location: admin_report.php');
    exit;
}

// --- Handle add announcement ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title      = mysqli_real_escape_string($conn, trim($_POST['ann_title']  ?? ''));
    $body       = mysqli_real_escape_string($conn, trim($_POST['ann_body']   ?? ''));
    $posted_by  = mysqli_real_escape_string($conn, trim($_POST['posted_by'] ?? 'Barangay Admin'));
    $is_urgent  = (!empty($_POST['is_urgent']) && $_POST['is_urgent'] === '1') ? 1 : 0;
    $image_path = null;

    if (!empty($_FILES['ann_image']['name'])) {
        $upload_dir     = __DIR__ . '/../assets/img/uploads/';
        $upload_dir_web = 'assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['ann_image']['name'], PATHINFO_EXTENSION));
        $filename = 'ann_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['ann_image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = $upload_dir_web . $filename;
        }
    }

    if ($title && $body) {
        $img_esc = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        executeQuery("INSERT INTO announcements (title, body, posted_by, is_urgent, image_path)
                      VALUES ('$title','$body','$posted_by',$is_urgent,$img_esc)");
    }
    header('Location: admin_report.php');
    exit;
}

// --- Stats ---
$r_pending  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='pending'"))[0]     ?? 0;
$r_progress = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='in-progress'"))[0] ?? 0;
$r_resolved = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='resolved'"))[0]    ?? 0;

// --- All reports with comments ---
$result  = executeQuery("SELECT *, resolved_at FROM reports ORDER BY created_at DESC");
$reports = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['image_path'])) {
            $row['image_path'] = '../' . $row['image_path'];
        }
        // Attach comments
        $rid = (int) $row['id'];
        $row['comments'] = [];
        $cres = executeQuery("SELECT * FROM report_comments WHERE report_id = $rid ORDER BY created_at ASC");
        if ($cres && mysqli_num_rows($cres) > 0) {
            while ($c = mysqli_fetch_assoc($cres)) {
                $row['comments'][] = $c;
            }
        }
        $reports[] = $row;
    }
}

$current_page = 'admin_report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Reports</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { background: #f0f2f8; min-height: 100vh; }
        .page-wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }

        @keyframes fa-spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        #refreshBtn.spinning .fa { animation: fa-spin 0.7s linear infinite; }

        /* Lightbox */
        #imgLightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.94); z-index:9999; align-items:center; justify-content:center; }
        #imgLightbox.open { display:flex; }
        #imgLightboxWrap { position:relative; display:flex; align-items:center; justify-content:center; max-width:90vw; max-height:90vh; }
        #imgLightboxImg  { max-width:88vw; max-height:86vh; border-radius:10px; box-shadow:0 10px 60px rgba(0,0,0,0.6); object-fit:contain; display:block; transition:opacity 0.18s; }
        #imgLightboxClose, #imgLightboxPrev, #imgLightboxNext { position:absolute; color:white; cursor:pointer; background:rgba(255,255,255,0.12); border:1.5px solid rgba(255,255,255,0.22); border-radius:50%; display:flex; align-items:center; justify-content:center; z-index:10001; transition:background 0.15s; }
        #imgLightboxClose { top:16px; right:18px; width:40px; height:40px; font-size:20px; }
        #imgLightboxPrev  { top:50%; transform:translateY(-50%); left:18px;  width:44px; height:44px; font-size:18px; }
        #imgLightboxNext  { top:50%; transform:translateY(-50%); right:18px; width:44px; height:44px; font-size:18px; }
        #imgLightboxPrev:hover, #imgLightboxNext:hover, #imgLightboxClose:hover { background:rgba(255,255,255,0.28); }
        #imgLightboxPrev.hidden, #imgLightboxNext.hidden { display:none; }
        #imgLightboxCounter { position:absolute; bottom:-30px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,0.7); font-size:13px; font-weight:600; white-space:nowrap; }
        .rpt-img-wrap { margin-bottom:14px; border-radius:10px; overflow:hidden; cursor:zoom-in; position:relative; }
        .rpt-img-wrap img { width:100%; max-height:240px; object-fit:cover; border:1.5px solid var(--border); display:block; transition:transform 0.22s, opacity 0.18s; }
        .rpt-img-wrap:hover img { transform:scale(1.02); opacity:0.9; }
        .rpt-img-zoom-hint { position:absolute; bottom:8px; right:8px; background:rgba(0,0,0,0.45); color:#fff; font-size:11px; font-weight:600; padding:3px 9px; border-radius:20px; pointer-events:none; display:flex; align-items:center; gap:4px; }

        /* Page header */
        .page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
        .page-title { font-family:'Sora',sans-serif; font-size:28px; font-weight:800; color:#1a1c2e; margin:0; }
        .page-sub   { font-size:16px; color:#8890b8; margin:2px 0 0; }

        /* Stats row */
        .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
        .stat-box   { background:#fff; border-radius:14px; padding:16px 20px; border:1.5px solid #e8eaf0; border-left:4px solid #e8eaf0; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .stat-label { font-size:13px; font-weight:700; color:#8890b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
        .stat-num   { font-family:'Sora',sans-serif; font-size:32px; font-weight:800; color:#1a1c2e; }

        /* Main card */
        .main-card { background:#fff; border-radius:16px; border:1.5px solid #e8eaf0; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .main-card-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:16px 20px; border-bottom:1.5px solid #e8eaf0; }
        .main-card-title  { font-family:'Sora',sans-serif; font-size:17px; font-weight:800; color:#1a1c2e; display:flex; align-items:center; gap:8px; }
        .main-card-body   { padding:16px 20px; }

        /* Filter tab buttons */
        .rpt-filter-tab {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 8px;
            border: 1.5px solid var(--border); background: transparent;
            color: var(--muted); font-family: 'Sora', sans-serif;
            font-size: 12.5px; font-weight: 700; cursor: pointer;
            transition: all .18s; white-space: nowrap;
        }
        .rpt-filter-tab--active {
            background: #1a56db; color: #fff; border-color: #1a56db;
        }
        .rpt-filter-tab--today-active {
            background: #f59c23; color: #fff; border-color: #f59c23;
        }
        .rpt-filter-tab:hover:not(.rpt-filter-tab--active):not(.rpt-filter-tab--today-active) {
            border-color: #1a56db; color: #1a56db;
        }
        /* Calendar day cells */
        .cal-day {
            padding: 5px 2px 3px; border-radius: 8px;
            font-size: 12px; font-weight: 600; line-height: 1.2;
            text-align: center; transition: background .15s, transform .1s;
        }
        .cal-day.has-reports { cursor: pointer; font-weight: 700; }
        .cal-day.has-reports:hover { transform: scale(1.1); }
        .cal-day.is-selected { background: #1a56db !important; color: #fff !important; }
        .cal-day.is-today:not(.is-selected) { color: #f59c23; font-weight: 800; outline: 1.5px solid #f59c23; outline-offset: -1px; }

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
        #kbc-dark-toggle { display: none !important; }

    </style>
</head>
<body>
<script>window.__isAdminPage=true;</script>
<?php include __DIR__ . '/includes/admin_navbar.php'; ?>

<div class="page-wrap">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-flag" style="color:#f59c23; margin-right:8px;"></i>Reports Overview</h1>
            <p class="page-sub">Manage and update resident-submitted reports</p>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <button onclick="openUnifiedModal()"
                    style="display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; background:#1a56db; color:#fff; border:none; font-size:12.5px; font-weight:700; cursor:pointer;">
                <i class="fa fa-plus"></i> New Post
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box" style="border-left-color:#f59c23;">
            <div class="stat-label">Pending</div>
            <div class="stat-num" style="color:#f59c23;"><?= $r_pending ?></div>
        </div>
        <div class="stat-box" style="border-left-color:#1a56db;">
            <div class="stat-label">In Progress</div>
            <div class="stat-num"><?= $r_progress ?></div>
        </div>
        <div class="stat-box" style="border-left-color:#22cc77;">
            <div class="stat-label">Resolved</div>
            <div class="stat-num" style="color:#22cc77;"><?= $r_resolved ?></div>
        </div>
    </div>

    <!-- Reports list -->
    <div class="main-card">
        <div class="main-card-header">
            <div class="main-card-title">
                <i class="fa fa-flag" style="color:#1a56db;"></i>
                <span id="reportsListTitle">All Reports</span>
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">

                <!-- Date range filter -->
                <div style="position:relative;">
                    <select class="rpt-filter-select" id="dateFilter" onchange="applyDateFilter(this.value)">
                        <option value="all">&#128197; All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="pick">&#128198; Pick a Date...</option>
                    </select>
                </div>

                <!-- Calendar picker (shown only when "Pick a Date" is chosen) -->
                <div id="calDropdown" style="display:none; position:absolute; z-index:9999;
                            background:var(--white); border:1.5px solid var(--border); border-radius:14px;
                            box-shadow:0 8px 32px rgba(0,0,0,.14); width:252px; overflow:hidden;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 13px; background:linear-gradient(135deg,#0800a0,#1a56db);">
                        <button onclick="calNav(-1);event.stopPropagation();" style="border:none; background:rgba(255,255,255,0.15); color:#fff; cursor:pointer; width:26px; height:26px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa fa-chevron-left" style="font-size:10px;"></i>
                        </button>
                        <div id="calMonthLabel" style="font-family:'Sora',sans-serif; font-size:13px; font-weight:800; color:#fff;"></div>
                        <button onclick="calNav(1);event.stopPropagation();" style="border:none; background:rgba(255,255,255,0.15); color:#fff; cursor:pointer; width:26px; height:26px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa fa-chevron-right" style="font-size:10px;"></i>
                        </button>
                    </div>
                    <div style="padding:10px 12px 4px;">
                        <div id="calGrid" style="display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center;"></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; padding:8px 12px 10px; border-top:1px solid var(--border); margin-top:6px;">
                        <div style="display:flex; align-items:center; gap:4px; font-size:10px; color:var(--muted); font-weight:600;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#1a56db; display:inline-block;"></span> Reports
                        </div>
                        <div style="display:flex; align-items:center; gap:4px; font-size:10px; color:var(--muted); font-weight:600;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#f59c23; display:inline-block;"></span> Today
                        </div>
                        <button onclick="applyDateFilter('all')" style="margin-left:auto; font-size:10px; font-weight:700; color:#1a56db; background:none; border:none; cursor:pointer; padding:0;">Clear</button>
                    </div>
                </div>

                <select class="rpt-filter-select" id="statusFilter" onchange="filterReports()">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
                <select class="rpt-filter-select" id="catFilter" onchange="filterReports()">
                    <option value="all">All Categories</option>
                    <option value="Infrastructure">Infrastructure</option>
                    <option value="Kalikasan">Kalikasan</option>
                    <option value="Serbisyo Publiko">Serbisyo Publiko</option>
                    <option value="Kapayapaan">Kapayapaan</option>
                    <option value="Publiko">Publiko</option>
                </select>

                <!-- Active date chip (shown when a specific day is picked) -->
                <div id="activeDateLabel" style="display:none; align-items:center; gap:5px; padding:5px 10px; background:#e8f0fe; border:1.5px solid #90aef8; border-radius:8px; font-size:11.5px; font-weight:700; color:#1a56db;">
                    <i class="fa fa-calendar" style="font-size:10px;"></i>
                    <span id="activeDateText"></span>
                    <button onclick="applyDateFilter('all')" style="border:none; background:none; color:#1a56db; cursor:pointer; font-size:11px; padding:0; margin-left:1px; line-height:1;"><i class="fa fa-times"></i></button>
                </div>

            </div>
        </div>
        <div class="main-card-body">
            <div id="reportsList"></div>
            <div class="content-empty" id="emptyState" style="display:none;">
                <div class="content-empty-icon"><i class="fa fa-flag"></i></div>
                <p id="emptyStateMsg">No reports match the selected filter.</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ UNIFIED CREATE MODAL ══ -->
<div class="rpt-modal-overlay" id="unifiedModal" onclick="if(event.target===this) closeUnifiedModal()">
    <div class="rpt-modal">
        <!-- Step 1: Type picker -->
        <div id="uStep1">
            <div class="rpt-modal-header">
                <div>
                    <div class="rpt-modal-eyebrow">Admin Action</div>
                    <div class="rpt-modal-title">What would you like to create?</div>
                </div>
                <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()"><i class="fa fa-times"></i></button>
            </div>
            <div class="rpt-modal-body" style="padding-bottom:24px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px;">
                    <button type="button" class="u-type-tile" onclick="chooseType('announcement')">
                        <div class="u-type-icon" style="background:#fff3cd; color:#856404;"><i class="fa fa-bullhorn"></i></div>
                        <div class="u-type-label">Announcement</div>
                        <div class="u-type-sub">Post an official notice to residents</div>
                    </button>
                    <button type="button" class="u-type-tile" onclick="chooseType('program')">
                        <div class="u-type-icon" style="background:#e8f0fe; color:#1a56db;"><i class="fa fa-briefcase"></i></div>
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
                    <button type="button" class="u-back-btn" onclick="backToTypePicker()"><i class="fa fa-arrow-left"></i></button>
                    <div><div class="rpt-modal-eyebrow">Admin Action</div><div class="rpt-modal-title">Post Announcement</div></div>
                </div>
                <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()"><i class="fa fa-times"></i></button>
            </div>
            <form method="POST" action="admin_report.php" enctype="multipart/form-data">
                <input type="hidden" name="add_announcement" value="1">
                <div class="rpt-modal-body">
                    <div class="field-group">
                        <label class="field-label">Title</label>
                        <input type="text" class="field-input" name="ann_title" placeholder="Announcement title..." required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Message</label>
                        <textarea class="field-input" name="ann_body" rows="4" placeholder="Write the full announcement here..." required style="resize:vertical;"></textarea>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Posted By</label>
                        <input type="text" class="field-input" name="posted_by" placeholder="e.g. Barangay Admin" value="Barangay Admin">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Image (optional)</label>
                        <div onclick="document.getElementById('annFileInput').click()" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border:1.5px dashed var(--border); border-radius:10px; cursor:pointer; background:var(--faint);">
                            <i class="fa fa-image" style="font-size:18px; color:var(--muted);"></i>
                            <span style="font-size:13px; color:var(--muted);" id="annFileLabel">Choose image...</span>
                        </div>
                        <input type="file" id="annFileInput" name="ann_image" accept="image/*" style="display:none;"
                               onchange="document.getElementById('annFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                        <input type="hidden" name="is_urgent" value="0" id="urgentHidden">
                        <button type="button" id="urgentBtn" onclick="toggleUrgent()" style="display:flex; align-items:center; gap:7px; padding:8px 14px; border-radius:8px; border:1.5px solid #ffd580; background:#fff3e0; color:#c47200; font-size:12px; font-weight:700; cursor:pointer;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span id="urgentBtnLabel">Mark as Urgent</span>
                        </button>
                    </div>
                </div>
                <div class="rpt-modal-footer">
                    <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                    <button type="submit" class="rpt-modal-submit"><i class="fa fa-paper-plane"></i> Post Announcement</button>
                </div>
            </form>
        </div>

        <!-- Step 2b: Program form -->
        <div id="uStep2Prog" style="display:none;">
            <div class="rpt-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" class="u-back-btn" onclick="backToTypePicker()"><i class="fa fa-arrow-left"></i></button>
                    <div><div class="rpt-modal-eyebrow">Admin Action</div><div class="rpt-modal-title">Add New Program</div></div>
                </div>
                <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()"><i class="fa fa-times"></i></button>
            </div>
            <form method="POST" action="admin_program.php" enctype="multipart/form-data">
                <input type="hidden" name="add_program" value="1">
                <div class="rpt-modal-body">
                    <div class="field-group">
                        <label class="field-label">Program Title</label>
                        <input type="text" class="field-input" name="title" placeholder="Enter program title..." required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Department</label>
                        <input type="text" class="field-input" name="department" placeholder="e.g. Health Department" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Description</label>
                        <textarea class="field-input" name="description" placeholder="Describe the program and its goals..." required style="resize:vertical;"></textarea>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Start Date</label>
                        <input type="date" class="field-input" name="start_date">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Image (optional)</label>
                        <div onclick="document.getElementById('uProgFileInput').click()" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border:1.5px dashed var(--border); border-radius:10px; cursor:pointer; background:var(--faint);">
                            <i class="fa fa-image" style="font-size:18px; color:var(--muted);"></i>
                            <span style="font-size:13px; color:var(--muted);" id="uProgFileLabel">Choose image...</span>
                        </div>
                        <input type="file" id="uProgFileInput" name="prog_image" accept="image/*" style="display:none;"
                               onchange="document.getElementById('uProgFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                    </div>
                </div>
                <div class="rpt-modal-footer">
                    <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                    <button type="submit" class="rpt-modal-submit"><i class="fa fa-paper-plane"></i> Add Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden status form -->
<form method="POST" action="admin_report.php" id="statusFormHidden" style="display:none;">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="report_id"  id="formReportIdH"  value="">
    <input type="hidden" name="new_status" id="formNewStatusH" value="">
</form>

<!-- Status Confirmation Modal -->
<div id="statusConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:18px; padding:28px 28px 22px; max-width:340px; width:90%; box-shadow:0 8px 40px rgba(0,0,0,.18); text-align:center;">
        <div style="width:52px; height:52px; border-radius:50%; background:#e8f0fe; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
            <i class="fa fa-refresh" style="font-size:22px; color:#1a56db;"></i>
        </div>
        <div style="font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px;">Change Status?</div>
        <div style="font-size:13px; color:#8890b8; margin-bottom:4px;">Set status to <strong id="confirmStatusLabel"></strong> for</div>
        <div style="font-size:13px; color:#4a5280; font-weight:600; margin-bottom:18px;" id="confirmReportTitle"></div>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="document.getElementById('statusConfirmModal').style.display='none'" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0; background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">Cancel</button>
            <button onclick="confirmStatusChange()" style="flex:1; padding:10px; border-radius:10px; background:#1a56db; color:#fff; font-weight:700; cursor:pointer; font-size:13px; border:none;">Confirm</button>
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
            <button onclick="document.getElementById('logoutModal').style.display='none'" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0; background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">Cancel</button>
            <a href="admin_logout.php" style="flex:1; padding:10px; border-radius:10px; background:#c0001a; color:#fff; font-weight:700; cursor:pointer; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center;">Log Out</a>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div id="imgLightbox" onclick="if(event.target===this)closeLightbox()">
    <div id="imgLightboxWrap">
        <button id="imgLightboxClose" onclick="closeLightbox()" aria-label="Close">&#215;</button>
        <button id="imgLightboxPrev"  onclick="lightboxNav(-1)" aria-label="Previous">&#8249;</button>
        <img id="imgLightboxImg" src="" alt="Full image">
        <button id="imgLightboxNext"  onclick="lightboxNav(1)"  aria-label="Next">&#8250;</button>
        <div id="imgLightboxCounter"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.REPORTS_DATA  = <?= json_encode(array_values($reports)) ?>;
    window.STAT_PENDING  = <?= $r_pending ?>;
    window.STAT_PROGRESS = <?= $r_progress ?>;
    window.STAT_RESOLVED = <?= $r_resolved ?>;
</script>
<!-- Guard: tell main.js this is an admin page so it skips resident dark-mode logic -->
<script>window.__isAdminPage = true;</script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin_report.js"></script>

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