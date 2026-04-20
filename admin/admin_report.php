<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: admin_login.php");
    exit();
}
include __DIR__ . '/../connection.php';

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id      = (int) $_POST['report_id'];
    $status  = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed = ['pending', 'in-progress', 'resolved'];
    if (in_array($status, $allowed) && $id > 0) {
        executeQuery("UPDATE reports SET status='$status' WHERE id=$id");
    }
    header('Location: admin_report.php');
    exit;
}

// --- Handle add announcement ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title     = mysqli_real_escape_string($conn, trim($_POST['ann_title']  ?? ''));
    $body      = mysqli_real_escape_string($conn, trim($_POST['ann_body']   ?? ''));
    $posted_by = mysqli_real_escape_string($conn, trim($_POST['posted_by'] ?? 'Barangay Admin'));
    $is_urgent = (!empty($_POST['is_urgent']) && $_POST['is_urgent'] === '1') ? 1 : 0;
    $image_path = null;

    if (!empty($_FILES['ann_image']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/uploads/';
        $upload_dir_web = 'assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['ann_image']['name'], PATHINFO_EXTENSION));
        $filename = 'ann_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target   = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['ann_image']['tmp_name'], $target)) {
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

// --- All reports ---
$result  = executeQuery("SELECT * FROM reports ORDER BY created_at DESC");
$reports = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $reports[] = $row;
}
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
</head>
<body class="page-official">

    <div class="container-fluid p-3 p-md-4">
        <div class="row g-3">

            <!-- SIDEBAR -->
            <div class="col-12 col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-top">
                        <div class="sidebar-logo-wrap">
                            <img src="../assets/img/logo.png" alt="Logo"
                                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
                        </div>
                        <div>
                            <div class="sidebar-admin">Admin Panel</div>
                            <div class="sidebar-name"><?= htmlspecialchars($_SESSION['admin_full_name'] ?? $_SESSION['admin_user'] ?? 'Admin') ?></div>
                        </div>
                    </div>
                    <div class="sidebar-footer">
                        <a href="#" class="sidebar-btn profile"><i class="fa fa-user"></i> Profile</a>
                        <a href="#" class="sidebar-btn logout"
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
                            <a href="admin_dashboard.php" class="back-btn light">&#8592; Back</a>
                            <span class="top-nav-title">Reports Overview</span>
                        </div>
                        <div class="top-nav-pills">
                            <a href="admin_dashboard.php" class="nav-pill">Dashboard</a>
                            <a href="admin_report.php"    class="nav-pill active">Report</a>
                            <a href="admin_program.php"   class="nav-pill">Programs</a>
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

                    <!-- Content -->
                    <div class="content-area" style="padding:16px;">

                        <!-- Filter bar -->
                        <div style="display:flex; align-items:center; justify-content:space-between;
                                    gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                            <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:0;">
                                <span class="content-eyebrow">Reports</span>
                                <div class="content-line"></div>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
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
                            </div>
                        </div>

                        <div id="reportsList"></div>
                        <div class="content-empty" id="emptyState" style="display:none;">
                            <div class="content-empty-icon"><i class="fa fa-flag"></i></div>
                            <p>No reports match the selected filter.</p>
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
                <form method="POST" action="admin_report.php" enctype="multipart/form-data">
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

    <!-- Hidden status form -->
    <form method="POST" action="admin_report.php" id="statusFormHidden" style="display:none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="report_id"  id="formReportIdH"  value="">
        <input type="hidden" name="new_status" id="formNewStatusH" value="">
    </form>

    <!-- Status Confirmation Modal -->
    <div id="statusConfirmModal"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
                z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:18px; padding:28px 28px 22px; max-width:340px;
                    width:90%; box-shadow:0 8px 40px rgba(0,0,0,0.18); text-align:center;">
            <div style="width:52px; height:52px; border-radius:50%; background:#e8f0fe;
                        display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <i class="fa fa-refresh" style="font-size:22px; color:#1a56db;"></i>
            </div>
            <div style="font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px;">Change Status?</div>
            <div style="font-size:13px; color:#8890b8; margin-bottom:4px;">
                Set status to <strong id="confirmStatusLabel"></strong> for
            </div>
            <div style="font-size:13px; color:#4a5280; font-weight:600; margin-bottom:18px;"
                 id="confirmReportTitle"></div>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button onclick="document.getElementById('statusConfirmModal').style.display='none'"
                        style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0;
                               background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">
                    Cancel
                </button>
                <button onclick="confirmStatusChange()"
                        style="flex:1; padding:10px; border-radius:10px; background:#1a56db;
                               color:#fff; font-weight:700; cursor:pointer; font-size:13px; border:none;">
                    Confirm
                </button>
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
                <a href="admin_logout.php"
                   style="flex:1; padding:10px; border-radius:10px; background:#c0001a; color:#fff;
                          font-weight:700; cursor:pointer; font-size:13px; text-decoration:none;
                          display:flex; align-items:center; justify-content:center;">
                    Log Out
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const reports = <?= json_encode(array_values($reports)) ?>;
    let openDetailId = null;

    const catColors = {
        'Infrastructure':   { bg: '#fff3e0', color: '#c47200', border: '#ffd580' },
        'Kalikasan':        { bg: '#e6faed', color: '#128548', border: '#7de0a4' },
        'Serbisyo Publiko': { bg: '#e8f0fe', color: '#1a56db', border: '#90aef8' },
        'Kapayapaan':       { bg: '#fce8ff', color: '#8b00c7', border: '#d48cf7' },
        'Publiko':          { bg: '#fff0f0', color: '#c0001a', border: '#f7a0aa' },
    };

    const statusConfig = {
        'pending':     { label: 'Pending',     bg: '#fff3e0', color: '#c47200' },
        'in-progress': { label: 'In Progress', bg: '#e8f0fe', color: '#1a56db' },
        'resolved':    { label: 'Resolved',    bg: '#e6faed', color: '#128548' },
    };

    function fmtDate(str) {
        if (!str) return '—';
        return new Date(str).toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: '2-digit'
        });
    }

    function escapeJs(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    function buildDetailHtml(r) {
        const st            = statusConfig[r.status] || statusConfig['pending'];
        const reporter_disp = parseInt(r.is_anonymous) ? 'Anonymous' : (r.reporter || '');
        const imgHtml       = r.image_path
            ? `<div style="margin-bottom:14px;">
                   <img src="${r.image_path}" alt="Report photo"
                        style="width:100%; border-radius:10px; max-height:240px;
                               object-fit:cover; border:1.5px solid var(--border);">
               </div>` : '';
        const purokHtml = r.purok
            ? `<div style="font-size:11px; color:var(--muted); margin-top:2px;">📍 ${r.purok}</div>` : '';

        return `
        <div style="border-top:1.5px solid var(--border); background:var(--faint);">
            <div style="background:linear-gradient(135deg,var(--blue-deep) 60%,var(--blue-main));
                        padding:16px 18px;">
                <div style="font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
                             color:rgba(255,255,255,0.6); margin-bottom:4px;">${r.category}</div>
                <div style="font-family:'Sora',sans-serif; font-size:16px; font-weight:800;
                             color:#fff; margin-bottom:8px;">${r.title}</div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="font-size:10px; font-weight:700; padding:3px 12px; border-radius:20px;
                                 background:rgba(255,255,255,0.15); color:#fff;
                                 border:1px solid rgba(255,255,255,0.25);">${st.label}</span>
                    <span style="font-size:11px; color:rgba(255,255,255,0.6);">${fmtDate(r.created_at)}</span>
                </div>
                <div style="font-size:11.5px; color:rgba(255,255,255,0.6); margin-top:5px;
                             display:flex; align-items:center; gap:5px;">
                    <i class="fa fa-${parseInt(r.is_anonymous) ? 'user-secret' : 'user'}"></i>
                    ${reporter_disp}
                </div>
                ${purokHtml}
            </div>
            <div style="padding:16px 18px;">
                ${imgHtml}
                <div class="field-group" style="margin-bottom:14px;">
                    <label class="field-label">Description</label>
                    <p style="font-size:13px; color:var(--text); line-height:1.6; margin:0;">
                        ${r.description || '—'}
                    </p>
                </div>
                <div class="field-group">
                    <label class="field-label">Update Status</label>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
                        <button type="button" class="rpt-status-btn pending"
                                onclick="askStatusChange(${r.id},'pending','${escapeJs(r.title)}')">
                            <i class="fa fa-clock-o"></i> Pending
                        </button>
                        <button type="button" class="rpt-status-btn in-progress"
                                onclick="askStatusChange(${r.id},'in-progress','${escapeJs(r.title)}')">
                            <i class="fa fa-spinner"></i> In Progress
                        </button>
                        <button type="button" class="rpt-status-btn resolved"
                                onclick="askStatusChange(${r.id},'resolved','${escapeJs(r.title)}')">
                            <i class="fa fa-check"></i> Resolved
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function renderReports() {
        const statusVal = document.getElementById('statusFilter').value;
        const catVal    = document.getElementById('catFilter').value;
        const filtered  = reports.filter(r =>
            (statusVal === 'all' || r.status === statusVal) &&
            (catVal    === 'all' || r.category === catVal)
        );
        const list  = document.getElementById('reportsList');
        const empty = document.getElementById('emptyState');

        if (filtered.length === 0) { list.innerHTML = ''; empty.style.display = ''; return; }
        empty.style.display = 'none';

        list.innerHTML = filtered.map(r => {
            const cat           = catColors[r.category]  || catColors['Infrastructure'];
            const st            = statusConfig[r.status] || statusConfig['pending'];
            const reporter_disp = parseInt(r.is_anonymous) ? 'Anonymous' : (r.reporter || '');
            const purok_disp    = r.purok ? ` · ${r.purok}` : '';
            const has_img       = r.image_path
                ? '&nbsp;<i class="fa fa-image" style="color:var(--muted);font-size:10px;" title="Has image"></i>' : '';
            const isOpen        = openDetailId === parseInt(r.id);

            return `
            <div class="rpt-inline-card ${isOpen ? 'is-open' : ''}" data-id="${r.id}"
                 style="margin-bottom:8px; border-radius:12px;
                        border:1.5px solid ${isOpen ? 'var(--blue-main)' : 'var(--border)'};
                        background:#fff; overflow:hidden; transition:border-color 0.2s;">
                <div style="display:flex; align-items:center; gap:8px; padding:12px 14px; cursor:pointer;"
                     onclick="toggleDetail(${r.id})">
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                            <span class="rpt-cat-badge"
                                  style="background:${cat.bg}; color:${cat.color}; border-color:${cat.border}">
                                ${r.category}
                            </span>
                            <span class="rpt-status-badge"
                                  style="background:${st.bg}; color:${st.color}">${st.label}</span>
                            ${has_img}
                        </div>
                        <div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px;
                                    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${r.title}</div>
                        <div style="font-size:11px; color:var(--muted); display:flex;
                                    align-items:center; gap:4px; flex-wrap:wrap;">
                            <i class="fa fa-${parseInt(r.is_anonymous) ? 'user-secret' : 'user'}"></i>
                            ${reporter_disp}${purok_disp}
                            &nbsp;·&nbsp;
                            <i class="fa fa-clock-o"></i> ${fmtDate(r.created_at)}
                        </div>
                    </div>
                    <div style="color:var(--muted); font-size:12px; flex-shrink:0;
                                transition:transform 0.25s;
                                transform:rotate(${isOpen ? '90deg' : '0deg'});">
                        <i class="fa fa-chevron-right"></i>
                    </div>
                </div>
                <div id="detail-${r.id}" style="display:${isOpen ? 'block' : 'none'};">
                    ${isOpen ? buildDetailHtml(r) : ''}
                </div>
            </div>`;
        }).join('');
    }

    function toggleDetail(id) {
        id = parseInt(id);
        openDetailId = (openDetailId === id) ? null : id;
        renderReports();
        if (openDetailId) {
            setTimeout(() => {
                const el = document.querySelector(`.rpt-inline-card[data-id="${id}"]`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 50);
        }
    }

    function filterReports() { renderReports(); }

    let pendingStatusId = null, pendingStatusVal = null;

    function askStatusChange(reportId, newStatus, title) {
        pendingStatusId  = reportId;
        pendingStatusVal = newStatus;
        const labels = { pending: 'Pending', 'in-progress': 'In Progress', resolved: 'Resolved' };
        document.getElementById('confirmStatusLabel').textContent = labels[newStatus];
        document.getElementById('confirmReportTitle').textContent = '"' + title + '"';
        document.getElementById('statusConfirmModal').style.display = 'flex';
    }

    function confirmStatusChange() {
        document.getElementById('formReportIdH').value  = pendingStatusId;
        document.getElementById('formNewStatusH').value = pendingStatusVal;
        document.getElementById('statusFormHidden').submit();
    }

    function confirmLogout() {
        document.getElementById('logoutModal').style.display = 'flex';
        return false;
    }

    // Urgent toggle
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

    renderReports();
    </script>
</body>
</html>