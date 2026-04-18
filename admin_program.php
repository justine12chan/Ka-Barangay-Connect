<?php
require_once 'connection.php';

// ─── Handle: update program status ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_program_status'])) {
    $id      = (int) $_POST['program_id'];
    $status  = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed = ['planned', 'ongoing', 'completed'];
    if (in_array($status, $allowed) && $id > 0) {
        executeQuery("UPDATE programs SET status='$status' WHERE id=$id");
    }
    header('Location: admin_program.php');
    exit;
}

// ─── Handle: edit program ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_program'])) {
    $id         = (int) $_POST['program_id'];
    $title      = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $dept       = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $status     = mysqli_real_escape_string($conn, trim($_POST['status']      ?? 'planned'));
    $start_date = !empty($_POST['start_date'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'"
        : 'NULL';

    $img_update = '';
    if (!empty($_FILES['edit_image']['name'])) {
        $upload_dir = 'assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $filename = 'prog_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target   = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target)) {
            $img_esc    = mysqli_real_escape_string($conn, $target);
            $img_update = ", image_path='$img_esc'";
        }
    }

    if ($title && $id > 0) {
        executeQuery("UPDATE programs
                      SET title='$title', department='$dept', description='$desc',
                          status='$status', start_date=$start_date $img_update
                      WHERE id=$id");
    }
    header('Location: admin_program.php');
    exit;
}

// ─── Handle: add new program ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $title      = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $dept       = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $start_date = !empty($_POST['start_date'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'"
        : 'NULL';
    $image_path = null;

    if (!empty($_FILES['prog_image']['name'])) {
        $upload_dir = 'assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['prog_image']['name'], PATHINFO_EXTENSION));
        $filename = 'prog_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target   = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['prog_image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }

    if ($title && $dept && $desc) {
        $img_esc = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        executeQuery("INSERT INTO programs (title, department, description, status, start_date, image_path)
                      VALUES ('$title','$dept','$desc','planned',$start_date,$img_esc)");
    }
    header('Location: admin_program.php');
    exit;
}

// ─── Stats ───────────────────────────────────────────────────────────────────
$p_planned   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]    ?? 0;
$p_ongoing   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]    ?? 0;
$p_completed = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0]  ?? 0;

// ─── Fetch programs ──────────────────────────────────────────────────────────
$programs = [];
$result   = executeQuery("SELECT * FROM programs ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['post_type'] = 'project';
        $programs[] = $row;
    }
}

// ─── Fetch announcements ─────────────────────────────────────────────────────
$announcements_list = [];
$ann_result = executeQuery("SELECT id, title, body AS description, posted_by AS department,
                                   image_path, is_urgent, created_at,
                                   NULL AS start_date, NULL AS status
                            FROM announcements ORDER BY created_at DESC");
if ($ann_result) {
    while ($row = mysqli_fetch_assoc($ann_result)) {
        $row['post_type'] = 'announcement';
        $row['status']    = $row['is_urgent'] ? 'urgent' : 'general';
        $announcements_list[] = $row;
    }
}

// ─── Merge + sort by date ────────────────────────────────────────────────────
$posts = array_merge($programs, $announcements_list);
usort($posts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Programs</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* Social feed card */
        .prog-social-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: visible;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
            transition: box-shadow 0.2s ease;
            margin-bottom: 14px;
            position: relative;
        }
        .prog-social-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.1); }

        .prog-social-header {
            display: flex; align-items: center; gap: 10px; padding: 14px 16px 10px;
        }
        .prog-reporter-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: var(--blue-light); color: var(--blue-main);
            font-family: 'Sora', sans-serif; font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; border: 2px solid var(--border); text-align: center; line-height: 1.2;
        }
        .prog-reporter-meta { flex: 1; min-width: 0; }
        .prog-reporter-name {
            font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
            color: var(--text-main); margin: 0 0 2px 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .prog-reporter-time { font-size: 12px; color: var(--muted); margin: 0; }

        .prog-status-pill {
            font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px;
            flex-shrink: 0; letter-spacing: 0.03em; text-transform: uppercase; border: 1px solid transparent;
        }
        .pill-planned   { background:#f3e8ff; color:#7c3aed; border-color:#c4b5fd; }
        .pill-ongoing   { background:#e8f0fe; color:#1a56db; border-color:#93b4f7; }
        .pill-completed { background:#e6faed; color:#128548; border-color:#6dd98d; }

        .prog-social-body { padding: 0 16px 14px; }
        .prog-social-title {
            font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
            color: var(--text-main); margin: 0 0 6px 0; line-height: 1.4;
        }
        .prog-social-desc { font-size: 13.5px; color: var(--gray-600); margin: 0 0 10px 0; line-height: 1.65; }
        .prog-social-meta { font-size: 12px; color: var(--muted); display: flex; gap: 14px; flex-wrap: wrap; margin-top: 6px; }

        /* Image grid */
        .prog-social-images { border-radius: 10px; overflow: hidden; margin-bottom: 8px; display: grid; gap: 3px; }
        .prog-social-images.img-count-1  { grid-template-columns: 1fr; }
        .prog-social-images.img-count-1 .prog-img-cell { height: 260px; }
        .prog-social-images.img-count-2  { grid-template-columns: 1fr 1fr; }
        .prog-social-images.img-count-2 .prog-img-cell { height: 200px; }
        .prog-social-images.img-count-3  { grid-template-columns: 1fr 1fr; grid-template-rows: 120px 120px; }
        .prog-social-images.img-count-3 .prog-img-cell:first-child { grid-row: span 2; }
        .prog-social-images.img-count-many { grid-template-columns: 1fr 1fr; grid-template-rows: 130px 130px; }
        .prog-img-cell { position: relative; overflow: hidden; background: var(--gray-100); cursor: pointer; }
        .prog-img-cell img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.22s ease, opacity 0.18s; }
        .prog-img-cell:hover img { transform: scale(1.04); opacity: 0.9; }
        .prog-img-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.52);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 800;
            color: white; pointer-events: none;
        }

        /* Three-dot menu */
        .prog-dots-btn {
            background: none; border: none; padding: 6px 8px; cursor: pointer;
            color: var(--muted); border-radius: 8px; font-size: 16px; line-height: 1;
            flex-shrink: 0; transition: background 0.15s, color 0.15s;
        }
        .prog-dots-btn:hover { background: var(--faint); color: var(--blue-main); }
        .prog-dropdown {
            position: absolute; top: 48px; right: 12px;
            background: white; border: 1px solid var(--border); border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.13); z-index: 200; min-width: 130px; display: none; overflow: hidden;
        }
        .prog-dropdown.open { display: block; }
        .prog-dropdown-item {
            display: flex; align-items: center; gap: 8px; padding: 10px 16px;
            font-size: 13.5px; font-weight: 600; color: var(--text-main);
            cursor: pointer; transition: background 0.13s; border: none; background: none; width: 100%; text-align: left;
        }
        .prog-dropdown-item:hover { background: var(--faint); }
        .prog-dropdown-item i { font-size: 13px; color: var(--blue-main); }

        /* Filter tabs */
        .prog-filter-row { display: flex; gap: 8px; flex-wrap: wrap; padding: 12px 14px 8px; border-bottom: 1px solid var(--border); }
        .prog-filter-tab {
            font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px;
            border: 1.5px solid var(--border); background: white; color: var(--muted);
            cursor: pointer; letter-spacing: 0.02em; transition: all 0.15s;
        }
        .prog-filter-tab:hover { border-color: var(--blue-main); color: var(--blue-main); }
        .prog-filter-tab.active { background: var(--blue-main); border-color: var(--blue-main); color: white; }

        /* Lightbox */
        #imgLightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.94); z-index: 9999; align-items: center; justify-content: center; }
        #imgLightbox.open { display: flex; }
        #imgLightboxWrap { position: relative; display: flex; align-items: center; justify-content: center; max-width: 90vw; max-height: 90vh; }
        #imgLightboxImg { max-width: 88vw; max-height: 86vh; border-radius: 10px; box-shadow: 0 10px 60px rgba(0,0,0,0.6); object-fit: contain; display: block; transition: opacity 0.18s; }
        #imgLightboxClose, #imgLightboxPrev, #imgLightboxNext {
            position: absolute; color: white; cursor: pointer;
            background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.22); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; z-index: 10001; transition: background 0.15s;
        }
        #imgLightboxClose { top: 16px; right: 18px; width: 40px; height: 40px; font-size: 20px; }
        #imgLightboxPrev  { top: 50%; transform: translateY(-50%); left:  18px; width: 44px; height: 44px; font-size: 18px; }
        #imgLightboxNext  { top: 50%; transform: translateY(-50%); right: 18px; width: 44px; height: 44px; font-size: 18px; }
        #imgLightboxPrev:hover, #imgLightboxNext:hover, #imgLightboxClose:hover { background: rgba(255,255,255,0.28); }
        #imgLightboxPrev.hidden, #imgLightboxNext.hidden { display: none; }
        #imgLightboxCounter { position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%); color: rgba(255,255,255,0.7); font-size: 13px; font-weight: 600; white-space: nowrap; }

        /* Edit modal */
        #editConfirmModal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9998; align-items: center; justify-content: center; }
        #editConfirmModal.open { display: flex; }
        .edit-confirm-box { background: white; border-radius: 18px; padding: 26px 24px 20px; max-width: 420px; width: 92%; box-shadow: 0 8px 40px rgba(0,0,0,0.18); }
        .edit-confirm-title { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 800; color: #1a2340; margin-bottom: 14px; }
        .edit-field-group { margin-bottom: 12px; }
        .edit-field-label { font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 4px; display: block; }
        .edit-field-input, .edit-field-textarea, .edit-field-select {
            width: 100%; border: 1.5px solid var(--border); border-radius: 9px; padding: 9px 12px;
            font-size: 13.5px; color: var(--text-main); background: var(--faint); outline: none;
            transition: border-color 0.15s; font-family: inherit;
        }
        .edit-field-input:focus, .edit-field-textarea:focus, .edit-field-select:focus { border-color: var(--blue-main); background: white; }
        .edit-field-textarea { resize: vertical; min-height: 80px; }
        .edit-confirm-actions { display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end; }
        .edit-cancel-btn { padding: 9px 20px; border-radius: 10px; border: 1.5px solid var(--border); background: var(--faint); color: #4a5280; font-weight: 700; cursor: pointer; font-size: 13px; }
        .edit-save-btn  { padding: 9px 22px; border-radius: 10px; background: var(--blue-main); color: white; font-weight: 700; cursor: pointer; font-size: 13px; border: none; }
        .edit-save-btn:hover { opacity: 0.88; }

        /* Save confirmation */
        #editSaveConfirm { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; align-items: center; justify-content: center; }
        #editSaveConfirm.open { display: flex; }
        .save-confirm-box { background: white; border-radius: 18px; padding: 28px 28px 22px; max-width: 340px; width: 90%; box-shadow: 0 8px 40px rgba(0,0,0,0.18); text-align: center; }
        .save-confirm-icon { width: 52px; height: 52px; border-radius: 50%; background: #e8f0fe; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .save-confirm-icon i { font-size: 22px; color: var(--blue-main); }
        .save-confirm-title { font-size: 16px; font-weight: 800; color: #1a2340; margin-bottom: 6px; }
        .save-confirm-sub   { font-size: 13px; color: #8890b8; margin-bottom: 20px; }
        .save-confirm-actions { display: flex; gap: 10px; justify-content: center; }
        .save-go-btn     { flex: 1; padding: 10px; border-radius: 10px; background: var(--blue-main); color: white; font-weight: 700; cursor: pointer; font-size: 13px; border: none; }
        .save-cancel-btn { flex: 1; padding: 10px; border-radius: 10px; border: 1.5px solid #e0e4f0; background: #f7f8fc; color: #4a5280; font-weight: 700; cursor: pointer; font-size: 13px; }

        #programsList { padding: 14px; }
    </style>
</head>
<body class="page-official">

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
                            <a href="official.php" class="back-btn light">&#8592; Back</a>
                            <span class="top-nav-title">Programs Overview</span>
                        </div>
                        <div class="top-nav-pills">
                            <a href="official.php"      class="nav-pill">Dashboard</a>
                            <a href="report_admin.php"  class="nav-pill">Report</a>
                            <a href="admin_program.php" class="nav-pill active">Programs</a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="stats-row">
                        <div class="stat-box" style="border-left-color:#7c3aed;">
                            <div class="stat-label">Planned</div>
                            <div class="stat-num" style="color:#7c3aed;"><?= $p_planned ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:var(--blue-main);">
                            <div class="stat-label">Ongoing</div>
                            <div class="stat-num"><?= $p_ongoing ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:#22cc77;">
                            <div class="stat-label">Completed</div>
                            <div class="stat-num" style="color:#22cc77;"><?= $p_completed ?></div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="content-area" style="padding:0;">

                        <!-- Filter bar -->
                        <div style="padding:12px 14px 10px; display:flex; gap:8px; align-items:center;
                                    flex-wrap:wrap; border-bottom:1px solid var(--border);">
                            <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:120px;">
                                <span class="content-eyebrow" style="white-space:nowrap;">Programs</span>
                                <div class="content-line"></div>
                            </div>
                            <select class="rpt-filter-select" id="sortFilter" onchange="renderPrograms()">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                            <select class="rpt-filter-select" id="typeFilter" onchange="renderPrograms()">
                                <option value="all">All Posts</option>
                                <option value="announcement">Announcement</option>
                                <option value="project">Project</option>
                            </select>
                            <select class="rpt-filter-select" id="statusFilter" onchange="renderPrograms()">
                                <option value="all">All Status</option>
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div id="programsList"></div>
                        <div class="content-empty" id="emptyState" style="display:none; padding:40px 16px;">
                            <div class="content-empty-icon"><i class="fa fa-briefcase"></i></div>
                            <p>No programs match the selected filter.</p>
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

            <!-- Step 1 -->
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

            <!-- Step 2a: Announcement -->
            <div id="uStep2Ann" style="display:none;">
                <div class="rpt-modal-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" class="u-back-btn" onclick="backToTypePicker()"><i class="fa fa-arrow-left"></i></button>
                        <div>
                            <div class="rpt-modal-eyebrow">Admin Action</div>
                            <div class="rpt-modal-title">Post Announcement</div>
                        </div>
                    </div>
                    <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()"><i class="fa fa-times"></i></button>
                </div>
                <form method="POST" action="report_admin.php" enctype="multipart/form-data">
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
                            <input type="text" class="field-input" name="posted_by" value="Barangay Admin">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Image (optional)</label>
                            <div onclick="document.getElementById('annFileInput').click()"
                                 style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                                        border:1.5px dashed var(--border); border-radius:10px; cursor:pointer; background:var(--faint);">
                                <i class="fa fa-image" style="font-size:18px; color:var(--muted);"></i>
                                <span style="font-size:13px; color:var(--muted);" id="annFileLabel">Choose image...</span>
                            </div>
                            <input type="file" id="annFileInput" name="ann_image" accept="image/*" style="display:none;"
                                   onchange="document.getElementById('annFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                            <input type="hidden" name="is_urgent" value="0" id="urgentHidden">
                            <button type="button" id="urgentBtn" onclick="toggleUrgent()"
                                style="display:flex; align-items:center; gap:7px; padding:8px 14px; border-radius:8px;
                                       border:1.5px solid #ffd580; background:#fff3e0; color:#c47200; font-size:12px; font-weight:700; cursor:pointer;">
                                <i class="fa fa-exclamation-triangle"></i>
                                <span id="urgentBtnLabel">Mark as Urgent</span>
                            </button>
                            <span style="font-size:11.5px; color:var(--muted);">Toggle to flag as urgent</span>
                        </div>
                    </div>
                    <div class="rpt-modal-footer">
                        <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                        <button type="submit" class="rpt-modal-submit"><i class="fa fa-paper-plane"></i> Post Announcement</button>
                    </div>
                </form>
            </div>

            <!-- Step 2b: Program -->
            <div id="uStep2Prog" style="display:none;">
                <div class="rpt-modal-header">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" class="u-back-btn" onclick="backToTypePicker()"><i class="fa fa-arrow-left"></i></button>
                        <div>
                            <div class="rpt-modal-eyebrow">Admin Action</div>
                            <div class="rpt-modal-title">Add New Program</div>
                        </div>
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
                            <textarea class="field-input" name="description" placeholder="Describe the program..." required style="resize:vertical;"></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Start Date</label>
                            <input type="date" class="field-input" name="start_date">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Image (optional)</label>
                            <div onclick="document.getElementById('uProgFileInput').click()"
                                 style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                                        border:1.5px dashed var(--border); border-radius:10px; cursor:pointer; background:var(--faint);">
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

    <!-- Edit Program Modal -->
    <div id="editConfirmModal" onclick="if(event.target===this) closeEditModal()">
        <div class="edit-confirm-box">
            <div class="edit-confirm-title">
                <i class="fa fa-pencil" style="color:var(--blue-main); margin-right:8px;"></i>Edit Program
            </div>
            <form method="POST" action="admin_program.php" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="edit_program" value="1">
                <input type="hidden" name="program_id"   id="editId">
                <div class="edit-field-group">
                    <label class="edit-field-label">Title</label>
                    <input type="text" class="edit-field-input" name="title" id="editTitle" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Department</label>
                    <input type="text" class="edit-field-input" name="department" id="editDept">
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Description</label>
                    <textarea class="edit-field-textarea" name="description" id="editDesc"></textarea>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Status</label>
                    <select class="edit-field-select" name="status" id="editStatus">
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Start Date</label>
                    <input type="date" class="edit-field-input" name="start_date" id="editStartDate">
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Replace Image (optional)</label>
                    <div onclick="document.getElementById('editFileInput').click()"
                         style="display:flex; align-items:center; gap:12px; padding:10px 14px;
                                border:1.5px dashed var(--border); border-radius:9px; cursor:pointer; background:var(--faint);">
                        <i class="fa fa-image" style="color:var(--muted);"></i>
                        <span style="font-size:12.5px; color:var(--muted);" id="editFileLabel">Keep current image...</span>
                    </div>
                    <input type="file" id="editFileInput" name="edit_image" accept="image/*" style="display:none;"
                           onchange="document.getElementById('editFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Keep current image...'">
                </div>
                <div class="edit-confirm-actions">
                    <button type="button" class="edit-cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="button" class="edit-save-btn"   onclick="showSaveConfirm()">
                        <i class="fa fa-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Save confirmation -->
    <div id="editSaveConfirm" onclick="if(event.target===this) closeSaveConfirm()">
        <div class="save-confirm-box">
            <div class="save-confirm-icon"><i class="fa fa-pencil-square-o"></i></div>
            <div class="save-confirm-title">Confirm Changes</div>
            <div class="save-confirm-sub">Are you sure you want to save these changes?</div>
            <div class="save-confirm-actions">
                <button class="save-cancel-btn" onclick="closeSaveConfirm()">Cancel</button>
                <button class="save-go-btn" onclick="document.getElementById('editForm').submit()">
                    <i class="fa fa-check"></i> Yes, Save
                </button>
            </div>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="imgLightbox" onclick="if(event.target===this||event.target.id==='imgLightbox') closeLightbox()">
        <button id="imgLightboxClose" onclick="closeLightbox()"><i class="fa fa-times"></i></button>
        <button id="imgLightboxPrev"  onclick="lightboxNav(-1)"><i class="fa fa-chevron-left"></i></button>
        <div id="imgLightboxWrap">
            <img id="imgLightboxImg" src="" alt="Full image">
            <div id="imgLightboxCounter"></div>
        </div>
        <button id="imgLightboxNext" onclick="lightboxNav(1)"><i class="fa fa-chevron-right"></i></button>
    </div>

    <!-- Logout Modal -->
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
    <script>
    const programs = <?= json_encode(array_values($posts)) ?>;

    const catColors = {
        'Infrastructure':   { bg: '#fff3e0', color: '#c47200', border: '#ffd580' },
        'Kalikasan':        { bg: '#e6faed', color: '#128548', border: '#7de0a4' },
        'Serbisyo Publiko': { bg: '#e8f0fe', color: '#1a56db', border: '#90aef8' },
        'Kapayapaan':       { bg: '#fce8ff', color: '#8b00c7', border: '#d48cf7' },
        'Publiko':          { bg: '#fff0f0', color: '#c0001a', border: '#f7a0aa' },
    };

    const typeConfig = {
        'announcement': { bg: '#fff8e1', color: '#b45309', border: '#fcd34d', icon: 'fa-bullhorn',  label: 'Announcement' },
        'project':      { bg: '#eff6ff', color: '#1d4ed8', border: '#93c5fd', icon: 'fa-briefcase', label: 'Project' },
    };

    const statusPill  = { planned: 'pill-planned', ongoing: 'pill-ongoing', completed: 'pill-completed' };
    const statusLabel = { planned: 'Planned',       ongoing: 'Ongoing',      completed: 'Completed' };

    function fmtDate(str) {
        if (!str) return 'TBD';
        return new Date(str + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function fmtDateTime(str) {
        if (!str) return '';
        return new Date(str).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }
    function getCatInitials(cat) {
        if (!cat) return '??';
        const w = cat.split(' ');
        return w.length === 1 ? cat.substring(0, 2).toUpperCase() : (w[0][0] + w[1][0]).toUpperCase();
    }

    function buildImageGrid(imagePath, progId) {
        if (!imagePath) return '';
        let images = [];
        try {
            const parsed = JSON.parse(imagePath);
            images = Array.isArray(parsed) ? parsed : [imagePath];
        } catch (e) {
            images = imagePath.split(',').map(s => s.trim()).filter(Boolean);
        }
        if (!images.length) return '';

        const MAX_VISIBLE = 4;
        const visible    = images.slice(0, MAX_VISIBLE);
        const extra      = images.length - MAX_VISIBLE;
        const countClass = images.length === 1 ? 'img-count-1'
                         : images.length === 2 ? 'img-count-2'
                         : images.length === 3 ? 'img-count-3'
                         : 'img-count-many';

        const cells = visible.map((src, idx) => {
            const isLast  = idx === visible.length - 1 && extra > 0;
            const overlay = isLast ? `<div class="prog-img-overlay">+${extra + 1}</div>` : '';
            return `<div class="prog-img-cell" onclick="openLightbox(event,${JSON.stringify(images)},${idx})">
                        <img src="${src.replace(/"/g,'&quot;')}" alt="Program image" loading="lazy">
                        ${overlay}
                    </div>`;
        }).join('');
        return `<div class="prog-social-images ${countClass}">${cells}</div>`;
    }

    function buildCard(r, isLatest) {
        const postType   = (r.post_type || 'project').toLowerCase();
        const tc         = typeConfig[postType] || typeConfig['project'];
        const cat        = r.department || '';
        const catC       = catColors[cat] || { bg: '#ededff', color: '#0800a0', border: '#c7c8f0' };
        const avatarStyle = `background:${catC.bg}; color:${catC.color}; border-color:${catC.border};`;
        const initials   = getCatInitials(cat);
        const dateStr    = fmtDateTime(r.created_at);
        const imgHtml    = buildImageGrid(r.image_path, r.id);

        const typeBadge = `<span class="prog-status-pill" style="background:${tc.bg}; color:${tc.color};
                            border:1.5px solid ${tc.border}; display:inline-flex; align-items:center; gap:5px;">
            <i class="fa ${tc.icon}" style="font-size:9px;"></i>${tc.label}
        </span>`;

        const statusBadge = (postType === 'project' && r.status && statusPill[r.status])
            ? `<span class="prog-status-pill ${statusPill[r.status]}">${statusLabel[r.status]}</span>`
            : '';

        const latestTag = isLatest
            ? `<div style="padding:2px 16px 8px;">
                   <span style="display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:700;
                                color:#1a56db; background:#e8f0fe; border:1px solid #93b4f7; border-radius:20px;
                                padding:2px 10px; letter-spacing:0.03em;">
                       <i class="fa fa-bolt" style="font-size:9px;"></i> Latest Update
                   </span>
               </div>` : '';

        return `
        <div class="prog-social-card" data-status="${r.status || ''}" data-type="${postType}"
             data-id="${r.id}" data-date="${r.created_at || ''}">
            <div class="prog-social-header">
                <div class="prog-reporter-avatar" style="${avatarStyle}">${initials}</div>
                <div class="prog-reporter-meta">
                    <p class="prog-reporter-name">${r.title}</p>
                    <p class="prog-reporter-time">${cat}${dateStr ? ' · ' + dateStr : ''}</p>
                </div>
                ${typeBadge} ${statusBadge}
                <button class="prog-dots-btn" title="More options"
                        onclick="toggleDropdown(event,${r.id})">&#8942;</button>
                <div class="prog-dropdown" id="dropdown-${r.id}">
                    <button class="prog-dropdown-item" onclick="openEditModal(event,${r.id})">
                        <i class="fa fa-pencil"></i> Edit
                    </button>
                </div>
            </div>
            ${latestTag}
            <div class="prog-social-body">
                <p class="prog-social-desc">${r.description || ''}</p>
                ${imgHtml}
                <div class="prog-social-meta">
                    <span><i class="fa fa-calendar" style="margin-right:4px;"></i>${fmtDate(r.start_date)}</span>
                </div>
            </div>
        </div>`;
    }

    function renderPrograms() {
        const statusVal = document.getElementById('statusFilter').value;
        const sortVal   = document.getElementById('sortFilter').value;
        const typeVal   = document.getElementById('typeFilter').value;
        const list      = document.getElementById('programsList');
        const empty     = document.getElementById('emptyState');

        let filtered = programs.filter(r =>
            (typeVal   === 'all' || r.post_type === typeVal) &&
            (statusVal === 'all' || r.status    === statusVal)
        );

        filtered.sort((a, b) => {
            const da = new Date(a.created_at || 0), db = new Date(b.created_at || 0);
            return sortVal === 'oldest' ? da - db : db - da;
        });

        if (!filtered.length) { list.innerHTML = ''; empty.style.display = ''; return; }
        empty.style.display = 'none';

        if (statusVal !== 'all') {
            const iconMap = { planned: 'fa-clock-o', ongoing: 'fa-spinner', completed: 'fa-check-circle' };
            const divider = `
            <div style="display:flex; align-items:center; gap:10px; padding:14px 14px 6px;">
                <span class="content-eyebrow" style="display:inline-flex; align-items:center; gap:6px; white-space:nowrap;">
                    <i class="fa ${iconMap[statusVal] || 'fa-list'}"></i> ${statusLabel[statusVal] || statusVal}
                </span>
                <div class="content-line"></div>
            </div>`;
            list.innerHTML = divider + filtered.map((r, i) => buildCard(r, i === 0)).join('');
        } else {
            list.innerHTML = filtered.map(r => buildCard(r, false)).join('');
        }
    }

    // Three-dot dropdown
    function toggleDropdown(e, id) {
        e.stopPropagation();
        const dd     = document.getElementById('dropdown-' + id);
        const isOpen = dd.classList.contains('open');
        document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
        if (!isOpen) dd.classList.add('open');
    }
    document.addEventListener('click', () => {
        document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
    });

    // Edit modal
    function openEditModal(e, id) {
        e.stopPropagation();
        document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
        const r = programs.find(x => parseInt(x.id) === id);
        if (!r) return;
        document.getElementById('editId').value        = r.id;
        document.getElementById('editTitle').value     = r.title;
        document.getElementById('editDept').value      = r.department;
        document.getElementById('editDesc').value      = r.description;
        document.getElementById('editStatus').value    = r.status;
        document.getElementById('editStartDate').value = r.start_date ? r.start_date.substring(0, 10) : '';
        document.getElementById('editFileLabel').textContent = 'Keep current image...';
        document.getElementById('editFileInput').value = '';
        document.getElementById('editConfirmModal').classList.add('open');
    }
    function closeEditModal()  { document.getElementById('editConfirmModal').classList.remove('open'); }
    function showSaveConfirm() {
        if (!document.getElementById('editTitle').value.trim()) { alert('Title cannot be empty.'); return; }
        document.getElementById('editSaveConfirm').classList.add('open');
    }
    function closeSaveConfirm() { document.getElementById('editSaveConfirm').classList.remove('open'); }

    // Lightbox
    let lbImages = [], lbIndex = 0;
    function openLightbox(e, images, startIndex) {
        e.stopPropagation();
        lbImages = Array.isArray(images) ? images : [images];
        lbIndex  = startIndex || 0;
        showLightboxImage();
        document.getElementById('imgLightbox').classList.add('open');
        document.addEventListener('keydown', lightboxKeyHandler);
    }
    function showLightboxImage() {
        const img     = document.getElementById('imgLightboxImg');
        const counter = document.getElementById('imgLightboxCounter');
        img.style.opacity = '0';
        img.onload = () => { img.style.opacity = '1'; };
        img.src = lbImages[lbIndex];
        counter.textContent = lbImages.length > 1 ? `${lbIndex + 1} / ${lbImages.length}` : '';
        document.getElementById('imgLightboxPrev').classList.toggle('hidden', lbIndex === 0);
        document.getElementById('imgLightboxNext').classList.toggle('hidden', lbIndex === lbImages.length - 1);
    }
    function lightboxNav(dir) {
        lbIndex = Math.max(0, Math.min(lbImages.length - 1, lbIndex + dir));
        showLightboxImage();
    }
    function lightboxKeyHandler(e) {
        if (e.key === 'ArrowLeft')  lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
        if (e.key === 'Escape')     closeLightbox();
    }
    function closeLightbox() {
        document.getElementById('imgLightbox').classList.remove('open');
        document.removeEventListener('keydown', lightboxKeyHandler);
        lbImages = []; lbIndex = 0;
    }

    function confirmLogout() { document.getElementById('logoutModal').style.display = 'flex'; return false; }

    // Show username
    const adminUser = sessionStorage.getItem('adminUser');
    if (adminUser) {
        const el = document.getElementById('sidebarUsername');
        if (el) el.textContent = adminUser.charAt(0).toUpperCase() + adminUser.slice(1);
    }

    // Unified modal
    function openUnifiedModal() {
        document.getElementById('uStep1').style.display     = '';
        document.getElementById('uStep2Ann').style.display  = 'none';
        document.getElementById('uStep2Prog').style.display = 'none';
        document.getElementById('unifiedModal').classList.add('open');
    }
    function closeUnifiedModal() { document.getElementById('unifiedModal').classList.remove('open'); }
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

    renderPrograms();
    </script>
</body>
</html>