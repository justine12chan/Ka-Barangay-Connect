<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: admin_login.php");
    exit();
}
include __DIR__ . '/../connection.php';

// ─── Handle: delete program ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_program'])) {
    $id = (int) $_POST['program_id'];
    if ($id > 0) executeQuery("DELETE FROM programs WHERE id=$id");
    header('Location: admin_program.php'); exit;
}

// ─── Handle: edit announcement ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $id    = (int) $_POST['announcement_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $body  = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $dept  = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $img_update = '';
    if (!empty($_FILES['edit_image']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $filename = 'ann_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $upload_dir . $filename)) {
            $img_esc = mysqli_real_escape_string($conn, 'assets/img/uploads/' . $filename);
            $img_update = ", image_path='$img_esc'";
        }
    }
    if ($title && $id > 0)
        executeQuery("UPDATE announcements SET title='$title', body='$body', posted_by='$dept' $img_update WHERE id=$id");
    header('Location: admin_program.php'); exit;
}

// ─── Handle: delete announcement ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = (int) $_POST['announcement_id'];
    if ($id > 0) executeQuery("DELETE FROM announcements WHERE id=$id");
    header('Location: admin_program.php'); exit;
}

// ─── Handle: edit program ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_program'])) {
    $id         = (int) $_POST['program_id'];
    $title      = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $dept       = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $status     = mysqli_real_escape_string($conn, trim($_POST['status']      ?? 'planned'));
    $start_date = !empty($_POST['start_date'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : 'NULL';
    $img_update = '';
    if (!empty($_FILES['edit_image']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $filename = 'prog_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $upload_dir . $filename)) {
            $img_esc = mysqli_real_escape_string($conn, 'assets/img/uploads/' . $filename);
            $img_update = ", image_path='$img_esc'";
        }
    }
    if ($title && $id > 0)
        executeQuery("UPDATE programs SET title='$title', department='$dept', description='$desc',
                      status='$status', start_date=$start_date $img_update WHERE id=$id");
    header('Location: admin_program.php'); exit;
}

// ─── Handle: add new program ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $title      = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $dept       = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $start_date = !empty($_POST['start_date'])
        ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : 'NULL';
    $image_path = null;
    if (!empty($_FILES['prog_image']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['prog_image']['name'], PATHINFO_EXTENSION));
        $filename = 'prog_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['prog_image']['tmp_name'], $upload_dir . $filename))
            $image_path = 'assets/img/uploads/' . $filename;
    }
    if ($title && $dept && $desc) {
        $img_esc = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        executeQuery("INSERT INTO programs (title, department, description, status, start_date, image_path)
                      VALUES ('$title','$dept','$desc','planned',$start_date,$img_esc)");
    }
    header('Location: admin_program.php'); exit;
}

// ─── Handle: add announcement ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title     = mysqli_real_escape_string($conn, trim($_POST['ann_title']  ?? ''));
    $body      = mysqli_real_escape_string($conn, trim($_POST['ann_body']   ?? ''));
    $posted_by = mysqli_real_escape_string($conn, trim($_POST['posted_by'] ?? 'Barangay Admin'));
    $is_urgent = (!empty($_POST['is_urgent']) && $_POST['is_urgent'] === '1') ? 1 : 0;
    $image_path = null;
    if (!empty($_FILES['ann_image']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['ann_image']['name'], PATHINFO_EXTENSION));
        $filename = 'ann_' . time() . '_' . rand(100,999) . '.' . $ext;
        if (move_uploaded_file($_FILES['ann_image']['tmp_name'], $upload_dir . $filename))
            $image_path = 'assets/img/uploads/' . $filename;
    }
    if ($title && $body) {
        $img_esc = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        executeQuery("INSERT INTO announcements (title, body, posted_by, is_urgent, image_path)
                      VALUES ('$title','$body','$posted_by',$is_urgent,$img_esc)");
    }
    header('Location: admin_program.php'); exit;
}

// ─── Stats ───────────────────────────────────────────────────────────────────
$p_planned   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]   ?? 0;
$p_ongoing   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]   ?? 0;
$p_completed = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0] ?? 0;
$ann_total   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements"))[0] ?? 0;

// ─── Fetch programs ──────────────────────────────────────────────────────────
$programs_list = [];
$result = executeQuery("SELECT * FROM programs ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['post_type'] = 'project';
        if (!empty($row['image_path'])) $row['image_path'] = '../' . $row['image_path'];
        $programs_list[] = $row;
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
        if (!empty($row['image_path'])) $row['image_path'] = '../' . $row['image_path'];
        $announcements_list[] = $row;
    }
}

// ─── Merge + sort by date ────────────────────────────────────────────────────
$posts = array_merge($programs_list, $announcements_list);
usort($posts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

$current_page = 'admin_program';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Programs</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { background: #f0f2f8; min-height: 100vh; }
        .page-wrap { max-width: 1100px; margin: 0 auto; padding: 28px 24px; }

        /* ── Spin ── */
        @keyframes fa-spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        #refreshBtn.spinning .fa { animation: fa-spin 0.7s linear infinite; }

        /* ── Page header ── */
        .page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
        .page-title  { font-family:'Sora',sans-serif; font-size:20px; font-weight:800; color:#0d0e2e; margin:0 0 3px; }
        .page-sub    { font-size:13px; color:#8890b8; margin:0; }

        /* ── Stats row (3 boxes matching report style) ── */
        .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
        @media (max-width:560px) { .stats-row { grid-template-columns:1fr; } }
        .stat-box  { background:#fff; border-radius:14px; padding:16px 20px; border:1.5px solid #e8eaf0; border-left:4px solid #e8eaf0; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .stat-label { font-size:11px; font-weight:700; color:#8890b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
        .stat-num  { font-family:'Sora',sans-serif; font-size:26px; font-weight:800; color:#0d0e2e; }
        .stat-sub  { font-size:11px; color:#b0b8d8; margin-top:2px; }

        /* ── Main card ── */
        .main-card { background:#fff; border-radius:16px; border:1.5px solid #e8eaf0; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .main-card-header {
            display:flex; align-items:center; justify-content:space-between;
            flex-wrap:wrap; gap:10px; padding:16px 20px; border-bottom:1.5px solid #e8eaf0;
        }
        .main-card-title { font-family:'Sora',sans-serif; font-size:14px; font-weight:800; color:#0d0e2e; display:flex; align-items:center; gap:8px; }
        .main-card-body  { padding:0; }

        /* ── Social feed cards ── */
        .prog-social-card {
            background: white; border-bottom: 1px solid var(--border);
            overflow: visible; transition: background .15s;
            position: relative;
        }
        .prog-social-card:last-child { border-bottom: none; border-radius: 0 0 14px 14px; }
        .prog-social-card:first-child { border-radius: 14px 14px 0 0; }
        .prog-social-card:hover { background: #fafbff; }

        .prog-social-header { display:flex; align-items:center; gap:10px; padding:14px 18px 10px; }
        .prog-reporter-avatar {
            width:42px; height:42px; border-radius:50%;
            font-family:'Sora',sans-serif; font-size:11px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; border:2px solid var(--border); text-align:center; line-height:1.2;
        }
        .prog-reporter-meta { flex:1; min-width:0; }
        .prog-reporter-name { font-family:'Sora',sans-serif; font-size:14px; font-weight:700; color:var(--text-main); margin:0 0 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .prog-reporter-time { font-size:12px; color:var(--muted); margin:0; }

        .prog-status-pill { font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; flex-shrink:0; letter-spacing:.03em; text-transform:uppercase; border:1px solid transparent; }
        .pill-planned   { background:#f3e8ff; color:#7c3aed; border-color:#c4b5fd; }
        .pill-ongoing   { background:#e8f0fe; color:#1a56db; border-color:#93b4f7; }
        .pill-completed { background:#e6faed; color:#128548; border-color:#6dd98d; }

        .prog-social-body { padding:0 18px 16px; }
        .prog-social-desc { font-size:13.5px; color:var(--gray-600); margin:0 0 10px; line-height:1.65; }
        .prog-social-meta { font-size:12px; color:var(--muted); display:flex; gap:14px; flex-wrap:wrap; margin-top:6px; }

        /* ── Image grid ── */
        .prog-social-images { border-radius:10px; overflow:hidden; margin-bottom:8px; display:grid; gap:3px; }
        .prog-social-images.img-count-1  { grid-template-columns:1fr; }
        .prog-social-images.img-count-1 .prog-img-cell { height:260px; }
        .prog-social-images.img-count-2  { grid-template-columns:1fr 1fr; }
        .prog-social-images.img-count-2 .prog-img-cell { height:200px; }
        .prog-social-images.img-count-3  { grid-template-columns:1fr 1fr; grid-template-rows:120px 120px; }
        .prog-social-images.img-count-3 .prog-img-cell:first-child { grid-row:span 2; }
        .prog-social-images.img-count-many { grid-template-columns:1fr 1fr; grid-template-rows:130px 130px; }
        .prog-img-cell { position:relative; overflow:hidden; background:var(--gray-100); cursor:pointer; }
        .prog-img-cell img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .22s ease, opacity .18s; }
        .prog-img-cell:hover img { transform:scale(1.04); opacity:0.9; }
        .prog-img-overlay { position:absolute; inset:0; background:rgba(0,0,0,.52); display:flex; align-items:center; justify-content:center; font-family:'Sora',sans-serif; font-size:22px; font-weight:800; color:white; pointer-events:none; }

        /* ── Three-dot menu ── */
        .prog-dots-btn { background:none; border:none; padding:6px 8px; cursor:pointer; color:var(--muted); border-radius:8px; font-size:16px; line-height:1; flex-shrink:0; transition:background .15s, color .15s; }
        .prog-dots-btn:hover { background:var(--faint); color:var(--blue-main); }
        .prog-dropdown { position:absolute; top:48px; right:14px; background:white; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.13); z-index:200; min-width:130px; display:none; overflow:hidden; }
        .prog-dropdown.open { display:block; }
        .prog-dropdown-item { display:flex; align-items:center; gap:8px; padding:10px 16px; font-size:13.5px; font-weight:600; color:var(--text-main); cursor:pointer; transition:background .13s; border:none; background:none; width:100%; text-align:left; }
        .prog-dropdown-item:hover { background:var(--faint); }
        .prog-dropdown-item i { font-size:13px; color:var(--blue-main); }
        .prog-dropdown-item.delete-item { color:#c0001a; }
        .prog-dropdown-item.delete-item i { color:#c0001a; }
        .prog-dropdown-item.delete-item:hover { background:#fff0f0; }

        /* ── Latest badge ── */
        .latest-tag { padding:2px 18px 8px; }

        /* ── Empty state ── */
        .empty-state { text-align:center; padding:56px 24px; color:var(--muted); }
        .empty-state i { font-size:36px; opacity:.25; margin-bottom:12px; }
        .empty-state p { font-size:14px; }

        /* ── Lightbox ── */
        #imgLightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.94); z-index:9999; align-items:center; justify-content:center; }
        #imgLightbox.open { display:flex; }
        #imgLightboxWrap { position:relative; display:flex; align-items:center; justify-content:center; max-width:90vw; max-height:90vh; }
        #imgLightboxImg  { max-width:88vw; max-height:86vh; border-radius:10px; box-shadow:0 10px 60px rgba(0,0,0,.6); object-fit:contain; display:block; transition:opacity .18s; }
        #imgLightboxClose, #imgLightboxPrev, #imgLightboxNext { position:absolute; color:white; cursor:pointer; background:rgba(255,255,255,.12); border:1.5px solid rgba(255,255,255,.22); border-radius:50%; display:flex; align-items:center; justify-content:center; z-index:10001; transition:background .15s; }
        #imgLightboxClose { top:16px; right:18px; width:40px; height:40px; font-size:20px; }
        #imgLightboxPrev  { top:50%; transform:translateY(-50%); left:18px;  width:44px; height:44px; font-size:18px; }
        #imgLightboxNext  { top:50%; transform:translateY(-50%); right:18px; width:44px; height:44px; font-size:18px; }
        #imgLightboxPrev:hover, #imgLightboxNext:hover, #imgLightboxClose:hover { background:rgba(255,255,255,.28); }
        #imgLightboxPrev.hidden, #imgLightboxNext.hidden { display:none; }
        #imgLightboxCounter { position:absolute; bottom:-30px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,.7); font-size:13px; font-weight:600; white-space:nowrap; }

        /* ── Modals ── */
        #deleteConfirmModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.52); z-index:9999; align-items:center; justify-content:center; }
        #deleteConfirmModal.open { display:flex; }
        #editConfirmModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9998; align-items:center; justify-content:center; }
        #editConfirmModal.open { display:flex; }
        .edit-confirm-box { background:white; border-radius:18px; padding:26px 24px 20px; max-width:420px; width:92%; box-shadow:0 8px 40px rgba(0,0,0,.18); max-height:90vh; overflow-y:auto; }
        .edit-confirm-title { font-family:'Sora',sans-serif; font-size:16px; font-weight:800; color:#1a2340; margin-bottom:14px; }
        .edit-field-group { margin-bottom:12px; }
        .edit-field-label { font-size:11.5px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin-bottom:4px; display:block; }
        .edit-field-input, .edit-field-textarea, .edit-field-select { width:100%; border:1.5px solid var(--border); border-radius:9px; padding:9px 12px; font-size:13.5px; color:var(--text-main); background:var(--faint); outline:none; transition:border-color .15s; font-family:inherit; }
        .edit-field-input:focus, .edit-field-textarea:focus, .edit-field-select:focus { border-color:var(--blue-main); background:white; }
        .edit-field-textarea { resize:vertical; min-height:80px; }
        .edit-confirm-actions { display:flex; gap:10px; margin-top:18px; justify-content:flex-end; }
        .edit-cancel-btn { padding:9px 20px; border-radius:10px; border:1.5px solid var(--border); background:var(--faint); color:#4a5280; font-weight:700; cursor:pointer; font-size:13px; }
        .edit-save-btn  { padding:9px 22px; border-radius:10px; background:var(--blue-main); color:white; font-weight:700; cursor:pointer; font-size:13px; border:none; }
        .edit-save-btn:hover { opacity:.88; }
        #editSaveConfirm { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; }
        #editSaveConfirm.open { display:flex; }
        .save-confirm-box { background:white; border-radius:18px; padding:28px 28px 22px; max-width:340px; width:90%; box-shadow:0 8px 40px rgba(0,0,0,.18); text-align:center; }
        .save-confirm-icon { width:52px; height:52px; border-radius:50%; background:#e8f0fe; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
        .save-confirm-icon i { font-size:22px; color:var(--blue-main); }
        .save-confirm-title { font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px; }
        .save-confirm-sub   { font-size:13px; color:#8890b8; margin-bottom:20px; }
        .save-confirm-actions { display:flex; gap:10px; justify-content:center; }
        .save-go-btn     { flex:1; padding:10px; border-radius:10px; background:var(--blue-main); color:white; font-weight:700; cursor:pointer; font-size:13px; border:none; }
        .save-cancel-btn { flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0; background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px; }

        /* ── Filter select ── */
        .rpt-filter-select { padding:7px 12px; border-radius:9px; border:1.5px solid var(--border); background:#fff; font-size:12.5px; font-weight:600; color:var(--text-main); cursor:pointer; outline:none; transition:border-color .15s; }
        .rpt-filter-select:focus { border-color:var(--blue-main); }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/admin_navbar.php'; ?>

<div class="page-wrap">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa fa-briefcase" style="color:#1a56db; margin-right:8px;"></i>Programs & Announcements</h1>
            <p class="page-sub">Manage barangay programs, projects and public announcements</p>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <button id="refreshBtn" onclick="refreshPage()"
                    style="display:flex; align-items:center; gap:5px; padding:8px 14px; border-radius:9px; border:1.5px solid #e8eaf0; background:#fff; color:#474960; font-size:12.5px; font-weight:700; cursor:pointer;">
                <i class="fa fa-refresh"></i> Refresh
            </button>
            <button onclick="openUnifiedModal()"
                    style="display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; background:#1a56db; color:#fff; border:none; font-size:12.5px; font-weight:700; cursor:pointer;">
                <i class="fa fa-plus"></i> New Post
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box" style="border-left-color:#7c3aed;">
            <div class="stat-label">Planned</div>
            <div class="stat-num" style="color:#7c3aed;"><?= $p_planned ?></div>
            <div class="stat-sub">Upcoming programs</div>
        </div>
        <div class="stat-box" style="border-left-color:#1a56db;">
            <div class="stat-label">Ongoing</div>
            <div class="stat-num"><?= $p_ongoing ?></div>
            <div class="stat-sub">Currently active</div>
        </div>
        <div class="stat-box" style="border-left-color:#22cc77;">
            <div class="stat-label">Completed</div>
            <div class="stat-num" style="color:#22cc77;"><?= $p_completed ?></div>
            <div class="stat-sub">Finished programs</div>
        </div>
    </div>

    <!-- Main card -->
    <div class="main-card">
        <div class="main-card-header">
            <div class="main-card-title">
                <i class="fa fa-list-ul" style="color:#1a56db;"></i> All Posts
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <select class="rpt-filter-select" id="sortFilter"   onchange="renderPrograms()">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
                <select class="rpt-filter-select" id="typeFilter"   onchange="renderPrograms()">
                    <option value="all">All Posts</option>
                    <option value="announcement">Announcements</option>
                    <option value="project">Projects</option>
                </select>
                <select class="rpt-filter-select" id="statusFilter" onchange="renderPrograms()">
                    <option value="all">All Status</option>
                    <option value="planned">Planned</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div class="main-card-body">
            <div id="programsList"></div>
            <div class="empty-state" id="emptyState" style="display:none;">
                <div><i class="fa fa-briefcase"></i></div>
                <p>No posts match the selected filters.</p>
            </div>
        </div>
    </div>

</div>

<!-- FAB -->
<button class="rpt-fab" title="Create New" onclick="openUnifiedModal()">
    <i class="fa fa-plus"></i>
</button>

<!-- Unified Create Modal -->
<div class="rpt-modal-overlay" id="unifiedModal" onclick="if(event.target===this) closeUnifiedModal()">
    <div class="rpt-modal">
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

        <!-- Announcement form -->
        <div id="uStep2Ann" style="display:none;">
            <div class="rpt-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" class="u-back-btn" onclick="backToTypePicker()"><i class="fa fa-arrow-left"></i></button>
                    <div><div class="rpt-modal-eyebrow">Admin Action</div><div class="rpt-modal-title">Post Announcement</div></div>
                </div>
                <button type="button" class="rpt-modal-close" onclick="closeUnifiedModal()"><i class="fa fa-times"></i></button>
            </div>
            <form method="POST" action="admin_program.php" enctype="multipart/form-data">
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
                        <span style="font-size:11.5px; color:var(--muted);">Toggle to flag as urgent</span>
                    </div>
                </div>
                <div class="rpt-modal-footer">
                    <button type="button" class="rpt-modal-cancel" onclick="closeUnifiedModal()">Cancel</button>
                    <button type="submit" class="rpt-modal-submit"><i class="fa fa-paper-plane"></i> Post Announcement</button>
                </div>
            </form>
        </div>

        <!-- Program form -->
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
                        <textarea class="field-input" name="description" placeholder="Describe the program..." required style="resize:vertical;"></textarea>
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

<!-- Edit Modal -->
<div id="editConfirmModal" onclick="if(event.target===this) closeEditModal()">
    <div class="edit-confirm-box">
        <div class="edit-confirm-title" id="editModalTitle">
            <i class="fa fa-pencil" style="color:var(--blue-main); margin-right:8px;"></i>Edit
        </div>
        <form method="POST" action="admin_program.php" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="edit_program"      id="editFlagProgram"  value="1">
            <input type="hidden" name="edit_announcement" id="editFlagAnn"      value="" disabled>
            <input type="hidden" name="program_id"        id="editId"           value="">
            <input type="hidden" name="announcement_id"   id="editAnnId"        value="" disabled>
            <div class="edit-field-group">
                <label class="edit-field-label">Title</label>
                <input type="text" class="edit-field-input" name="title" id="editTitle" required>
            </div>
            <div class="edit-field-group">
                <label class="edit-field-label" id="editDeptLabel">Department</label>
                <input type="text" class="edit-field-input" name="department" id="editDept">
            </div>
            <div class="edit-field-group">
                <label class="edit-field-label" id="editDescLabel">Description</label>
                <textarea class="edit-field-textarea" name="description" id="editDesc"></textarea>
            </div>
            <div id="editProgFields">
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
            </div>
            <div class="edit-field-group">
                <label class="edit-field-label">Replace Image (optional)</label>
                <div onclick="document.getElementById('editFileInput').click()"
                     style="display:flex; align-items:center; gap:12px; padding:10px 14px; border:1.5px dashed var(--border); border-radius:9px; cursor:pointer; background:var(--faint);">
                    <i class="fa fa-image" style="color:var(--muted);"></i>
                    <span style="font-size:12.5px; color:var(--muted);" id="editFileLabel">Keep current image...</span>
                </div>
                <input type="file" id="editFileInput" name="edit_image" accept="image/*" style="display:none;"
                       onchange="document.getElementById('editFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Keep current image...'">
            </div>
            <div class="edit-confirm-actions">
                <button type="button" class="edit-cancel-btn" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="edit-save-btn"   onclick="showSaveConfirm()"><i class="fa fa-check"></i> Save Changes</button>
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
            <button class="save-go-btn" onclick="document.getElementById('editForm').submit()"><i class="fa fa-check"></i> Yes, Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation -->
<div id="deleteConfirmModal" onclick="if(event.target===this) closeDeleteModal()">
    <div style="background:#fff; border-radius:18px; padding:28px 28px 22px; max-width:340px; width:90%; box-shadow:0 8px 40px rgba(0,0,0,.18); text-align:center;">
        <div style="width:52px; height:52px; border-radius:50%; background:#fff0f0; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
            <i class="fa fa-trash" style="font-size:22px; color:#c0001a;"></i>
        </div>
        <div style="font-size:16px; font-weight:800; color:#1a2340; margin-bottom:6px;">Delete Post?</div>
        <div style="font-size:13px; color:#8890b8; margin-bottom:4px;">This will permanently delete</div>
        <div style="font-size:13px; color:#4a5280; font-weight:600; margin-bottom:18px;" id="deleteProgramTitle"></div>
        <div style="font-size:12px; color:#c0001a; margin-bottom:18px;">This action cannot be undone.</div>
        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="closeDeleteModal()" style="flex:1; padding:10px; border-radius:10px; border:1.5px solid #e0e4f0; background:#f7f8fc; color:#4a5280; font-weight:700; cursor:pointer; font-size:13px;">Cancel</button>
            <button onclick="confirmDelete()" style="flex:1; padding:10px; border-radius:10px; background:#c0001a; color:#fff; font-weight:700; cursor:pointer; font-size:13px; border:none;"><i class="fa fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<!-- Hidden forms -->
<form method="POST" action="admin_program.php" id="deleteFormHidden" style="display:none;">
    <input type="hidden" name="delete_program" value="1">
    <input type="hidden" name="program_id" id="deleteProgramId" value="">
</form>
<form method="POST" action="admin_program.php" id="deleteAnnFormHidden" style="display:none;">
    <input type="hidden" name="delete_announcement" value="1">
    <input type="hidden" name="announcement_id" id="deleteAnnId" value="">
</form>

<!-- Lightbox -->
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.PROGRAMS_DATA = <?= json_encode(array_values($posts)) ?>;
</script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin_program.js"></script>
</body>
</html>