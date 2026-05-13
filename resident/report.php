<?php
// resident/report.php
session_start();

// ── Auth gate ───────────────────────────────────────────────
if (!isset($_SESSION['resident_id'])) {
    header('Location: resident_login.php?redirect=report.php');
    exit;
}

$resident_id    = (int) $_SESSION['resident_id'];
$resident_name  = $_SESSION['resident_name'];
$resident_purok = $_SESSION['resident_purok'] ?? '';

require_once __DIR__ . '/../connection.php';

// ── Ensure resident_id column exists in reports table ───────
// This is a one-time safe migration: if the column is missing it gets added.
$col_check = @mysqli_query($conn, "SHOW COLUMNS FROM `reports` LIKE 'resident_id'");
if ($col_check && mysqli_num_rows($col_check) === 0) {
    mysqli_query($conn, "ALTER TABLE `reports` ADD COLUMN `resident_id` INT(11) NOT NULL DEFAULT 0 AFTER `status`");
    // Back-fill existing rows so they don't break foreign constraints
    mysqli_query($conn, "UPDATE `reports` SET `resident_id` = 0 WHERE `resident_id` IS NULL");
}

$success_msg     = '';
$error_msg       = '';
$comment_success = '';
$comment_error   = '';

// ── Ensure report_comments has is_admin + commenter_name columns ──
$col2 = @mysqli_query($conn, "SHOW COLUMNS FROM `report_comments` LIKE 'is_admin'");
if ($col2 && mysqli_num_rows($col2) === 0) {
    mysqli_query($conn, "ALTER TABLE `report_comments` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `comment_text`");
    mysqli_query($conn, "ALTER TABLE `report_comments` ADD COLUMN `commenter_name` VARCHAR(120) NULL DEFAULT NULL AFTER `is_admin`");
}

// ── Handle comment submission ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $report_id   = (int) ($_POST['report_id'] ?? 0);
    $comment_txt = trim($_POST['comment_text'] ?? '');
    if ($report_id > 0 && $comment_txt !== '') {
        $name_esc = mysqli_real_escape_string($conn, $resident_name);
        $comm_esc = mysqli_real_escape_string($conn, $comment_txt);
        executeQuery("INSERT INTO report_comments (report_id, resident_id, resident_name, comment_text, is_admin)
                      VALUES ($report_id, $resident_id, '$name_esc', '$comm_esc', 0)");
        $comment_success = 'Feedback submitted.';
    } else {
        $comment_error = 'Comment cannot be empty.';
    }
}

// ── Handle new report submission ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report') {
    $reporter    = $resident_name;
    $purok       = trim($_POST['purok']       ?? $resident_purok);
    $raw_cat     = trim($_POST['category']    ?? '');
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path  = null;

    $cat_parts      = explode('|', $raw_cat, 2);
    $category       = $cat_parts[0];
    $specific_issue = $cat_parts[1] ?? '';

    if (empty($title) || empty($category) || empty($description)) {
        $error_msg = 'Please fill in all required fields.';
    } else {
        if (!empty($_FILES['report_image']['name'])) {
            $upload_dir     = __DIR__ . '/../assets/img/uploads/';
            $upload_dir_web = 'assets/img/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext      = pathinfo($_FILES['report_image']['name'], PATHINFO_EXTENSION);
            $filename = 'report_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $target   = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['report_image']['tmp_name'], $target)) {
                $image_path = $upload_dir_web . $filename;
            }
        }

        $r_esc    = mysqli_real_escape_string($conn, $reporter);
        $p_esc    = mysqli_real_escape_string($conn, $purok);
        $cat_esc  = mysqli_real_escape_string($conn, $category);
        $spec_esc = mysqli_real_escape_string($conn, $specific_issue);
        $t_esc    = mysqli_real_escape_string($conn, $title);
        $d_esc    = mysqli_real_escape_string($conn, $description);
        $img_esc  = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        $full_cat_esc = $spec_esc ? "$cat_esc|$spec_esc" : $cat_esc;

        $query = "INSERT INTO reports (reporter, purok, category, title, description, image_path, status, resident_id)
                  VALUES ('$r_esc', '$p_esc', '$full_cat_esc', '$t_esc', '$d_esc', $img_esc, 'pending', $resident_id)";

        if (executeQuery($query)) {
            $success_msg = 'Your report has been submitted successfully. It will appear below once approved.';
        } else {
            $error_msg = 'Failed to submit report. Please try again.';
        }
    }
}

// ── Fetch this resident's reports + comments ─────────────────
$my_reports = [];
$res = executeQuery("SELECT * FROM reports WHERE resident_id = $resident_id ORDER BY created_at DESC");
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $rid = (int) $row['id'];
        $comments = [];
        $cres = executeQuery("SELECT * FROM report_comments WHERE report_id = $rid ORDER BY created_at ASC");
        if ($cres && mysqli_num_rows($cres) > 0) {
            while ($c = mysqli_fetch_assoc($cres)) $comments[] = $c;
        }
        $row['comments'] = $comments;
        $my_reports[] = $row;
    }
}

$badge_map = [
    'pending'     => ['label' => 'Pending',     'bg' => '#fff8e1', 'color' => '#92650a', 'border' => '#f0c040', 'dot' => '#f0c040'],
    'in-progress' => ['label' => 'In Progress',  'bg' => '#e8f0fe', 'color' => '#1a56db', 'border' => '#93b4f7', 'dot' => '#4285f4'],
    'resolved'    => ['label' => 'Resolved',     'bg' => '#e6f9ee', 'color' => '#126e40', 'border' => '#72d49a', 'dot' => '#34a853'],
];

// Helper: initials from full name
function initials($name) {
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    return $i;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports — Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
    <style>
        /* ── Page shell ── */
        body.page-report {
            background: #f0f1f8;
            min-height: 100vh;
        }

        .rp-layout {
            max-width: 760px;
            margin: 0 auto;
            padding: 28px 16px 72px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* ────────────────────────────────────────────
           NAV
        ──────────────────────────────────────────── */
        .rp-nav {
            background: #04005a;
            border-bottom: 3px solid #f5cc00;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 200;
        }
        .rp-nav-logo {
            width: 38px; height: 38px;
            border-radius: 50%;
            flex-shrink: 0;
            background: transparent;
        }
        .rp-nav-logo img {
            width: 100%; height: 100%; object-fit: contain;
            mix-blend-mode: screen;
            filter: brightness(1.08);
        }
        .rp-nav-title {
            font-family: 'Sora', sans-serif;
            font-size: 14px; font-weight: 800;
            color: #fff; margin: 0;
        }
        .rp-nav-sub {
            font-size: 10px; font-weight: 600;
            color: #f5cc00; text-transform: uppercase;
            letter-spacing: .1em;
        }
        .rp-nav-right {
            margin-left: auto;
            display: flex; align-items: center; gap: 8px;
        }
        .rp-nav-user {
            font-size: 12px; font-weight: 600;
            color: rgba(255,255,255,.7);
            display: none;
        }
        @media (min-width: 480px) { .rp-nav-user { display: block; } }
        .rp-nav-btn {
            font-family: 'Sora', sans-serif;
            font-size: 11.5px; font-weight: 700;
            color: #f5cc00;
            background: rgba(245,204,0,.1);
            border: 1px solid rgba(245,204,0,.28);
            border-radius: 20px;
            padding: 5px 14px;
            text-decoration: none;
            transition: background .18s;
            white-space: nowrap;
        }
        .rp-nav-btn:hover { background: rgba(245,204,0,.22); color: #f5cc00; }
        .rp-nav-btn.logout {
            color: #ffb0b0;
            background: rgba(255,80,80,.08);
            border-color: rgba(255,80,80,.25);
        }
        .rp-nav-btn.logout:hover { background: rgba(255,80,80,.18); color: #ffb0b0; }

        /* ────────────────────────────────────────────
           SUBMIT CARD
        ──────────────────────────────────────────── */
        .submit-card {
            background: #fff;
            border: 1px solid #e2e3f0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(8,0,120,.06);
        }

        .submit-card-head {
            background: linear-gradient(135deg, #0600a0 0%, #04005a 100%);
            padding: 22px 26px 18px;
            position: relative;
            overflow: hidden;
        }
        .submit-card-head::after {
            content: '';
            position: absolute; right: -40px; top: -40px;
            width: 180px; height: 180px; border-radius: 50%;
            background: radial-gradient(circle, rgba(245,204,0,.12), transparent 70%);
            pointer-events: none;
        }
        .submit-card-eyebrow {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .12em; color: #f5cc00; margin-bottom: 4px;
        }
        .submit-card-title {
            font-family: 'Sora', sans-serif;
            font-size: 19px; font-weight: 800; color: #fff;
            margin: 0 0 4px;
        }
        .submit-card-sub { font-size: 12px; color: rgba(255,255,255,.55); margin: 0; }

        .submit-form-body { padding: 24px 26px 26px; }

        /* Alerts */
        .sf-alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 600; margin-bottom: 18px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .sf-alert.success {
            background: #e6f9ee; color: #126e40;
            border: 1.5px solid #72d49a;
        }
        .sf-alert.error {
            background: #fff0f0; color: #c0001a;
            border: 1.5px solid #f7a0aa;
        }
        .sf-alert-icon {
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 900; flex-shrink: 0; margin-top: 1px;
        }
        .sf-alert.success .sf-alert-icon { background: #126e40; color: #fff; }
        .sf-alert.error   .sf-alert-icon { background: #c0001a; color: #fff; }

        /* Section divider */
        .sf-section {
            display: flex; align-items: center; gap: 10px;
            margin: 20px 0 14px;
        }
        .sf-section-pill {
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .1em; color: #0800a0;
            background: rgba(8,0,160,.07);
            border: 1px solid rgba(8,0,160,.15);
            border-radius: 20px; padding: 3px 12px; white-space: nowrap;
        }
        .sf-section-line { flex: 1; height: 1px; background: #e2e3f0; }

        /* Field labels & inputs */
        .sf-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: #5c5e80; margin-bottom: 6px; display: block;
        }
        .sf-input {
            width: 100%; padding: 10px 14px;
            background: #f8f9fc;
            border: 1.5px solid #e2e3f0;
            border-radius: 10px; font-size: 13.5px;
            color: #0d0e2e; outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: border-color .18s, background .18s;
            appearance: none;
        }
        .sf-input::placeholder { color: #9496b8; }
        .sf-input:focus { border-color: #0800a0; background: #fff; }
        .sf-input:disabled { background: #f0f1f8; color: #9496b8; cursor: not-allowed; }
        select.sf-input { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239496b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
        textarea.sf-input { min-height: 100px; resize: vertical; }

        /* Category badge strip */
        .cat-badge-row { margin-top: 8px; }
        .cat-badge {
            display: none; font-size: 11px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
            letter-spacing: .04em;
        }
        .cat-badge.infra      { background: #fff3e0; color: #b56000; border: 1px solid #f5c77e; }
        .cat-badge.kalikasan  { background: #e6f9ee; color: #126e40; border: 1px solid #72d49a; }
        .cat-badge.serbisyo   { background: #e8f0fe; color: #1a56db; border: 1px solid #93b4f7; }
        .cat-badge.publiko    { background: #fce8e8; color: #b0001a; border: 1px solid #f7a0aa; }
        .cat-badge.kapayapaan { background: #f3e8ff; color: #7000b5; border: 1px solid #d4a0f7; }

        /* Image picker */
        .sf-picker {
            display: flex; align-items: center; gap: 14px;
            background: #f8f9fc;
            border: 1.5px dashed #c8cadf;
            border-radius: 12px; padding: 14px 18px; cursor: pointer;
            transition: border-color .18s, background .18s;
        }
        .sf-picker:hover { border-color: #0800a0; background: rgba(8,0,160,.03); }
        .sf-picker-icon {
            width: 42px; height: 42px; border-radius: 10px;
            background: rgba(8,0,160,.07);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sf-picker-title { font-size: 13px; font-weight: 700; color: #0d0e2e; margin-bottom: 2px; }
        .sf-picker-sub   { font-size: 11px; color: #9496b8; }

        /* Submit button */
        .sf-submit {
            width: 100%; padding: 13px 20px; margin-top: 22px;
            background: #0800a0;
            color: #fff; font-weight: 700; font-size: 14px;
            font-family: 'Sora', sans-serif; border: none; border-radius: 12px;
            cursor: pointer; letter-spacing: .04em;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: background .18s, transform .1s;
        }
        .sf-submit:hover  { background: #0a00c7; transform: translateY(-1px); }
        .sf-submit:active { transform: translateY(0); }

        /* ────────────────────────────────────────────
           MY REPORTS SECTION
        ──────────────────────────────────────────── */
        .section-head {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 4px;
        }
        .section-head-title {
            font-family: 'Sora', sans-serif;
            font-size: 15px; font-weight: 800; color: #0d0e2e;
            white-space: nowrap;
        }
        .section-head-count {
            font-size: 11px; font-weight: 700; color: #5c5e80;
            background: #e2e3f0; border-radius: 20px; padding: 2px 10px;
        }
        .section-head-line { flex: 1; height: 1px; background: #e2e3f0; }

        /* Report card */
        .report-post {
            background: #fff;
            border: 1px solid #e2e3f0;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 6px rgba(8,0,120,.04);
            transition: box-shadow .18s, border-color .18s;
        }
        .report-post:hover { box-shadow: 0 4px 20px rgba(8,0,120,.08); }

        /* Post header */
        .rp-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            padding: 16px 18px 12px; gap: 10px;
            border-bottom: 1px solid #f0f1f8;
        }
        .rp-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #0800a0, #4e8ef7);
            color: #fff; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            font-family: 'Sora', sans-serif;
        }
        .rp-meta-name {
            font-size: 13.5px; font-weight: 700; color: #0d0e2e; margin: 0 0 2px;
        }
        .rp-meta-loc {
            font-size: 11px; color: #9496b8; margin: 0 0 1px;
            display: flex; align-items: center; gap: 4px;
        }
        .rp-meta-date { font-size: 11px; color: #9496b8; margin: 0; }

        /* Status badge */
        .rp-status-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 700;
            padding: 4px 10px 4px 8px; border-radius: 20px;
            border: 1px solid; flex-shrink: 0;
            white-space: nowrap;
        }
        .rp-status-dot {
            width: 6px; height: 6px; border-radius: 50;
            border-radius: 50%; flex-shrink: 0;
        }

        /* Post body */
        .rp-body { padding: 14px 18px 16px; }
        .rp-cat-pill {
            display: inline-block; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            padding: 3px 10px; border-radius: 20px;
            background: rgba(8,0,160,.07); color: #0800a0;
            border: 1px solid rgba(8,0,160,.15); margin-bottom: 10px;
        }
        .rp-title {
            font-family: 'Sora', sans-serif;
            font-size: 15px; font-weight: 800; color: #0d0e2e; margin: 0 0 6px;
        }
        .rp-desc  { font-size: 13px; color: #5c5e80; line-height: 1.65; margin: 0; }
        .rp-img   {
            width: 100%; max-height: 220px; object-fit: cover;
            border-radius: 10px; margin-top: 14px; cursor: zoom-in;
            border: 1px solid #e2e3f0;
        }

        /* Comments section */
        .rp-comments {
            border-top: 1px solid #f0f1f8;
            padding: 14px 18px 18px;
            background: #fafbff;
        }
        .rp-comments-head {
            display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
        }
        .rp-comments-label {
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .08em; color: #5c5e80;
        }
        .rp-comments-count {
            font-size: 10px; font-weight: 700; color: #9496b8;
            background: #e2e3f0; border-radius: 20px; padding: 1px 8px;
        }

        .comment-empty { font-size: 12.5px; color: #9496b8; margin: 0 0 14px; }

        .comment-item { display: flex; gap: 10px; margin-bottom: 12px; }
        .comment-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: #e2e3f0;
            color: #5c5e80; font-size: 11px; font-weight: 700;
            font-family: 'Sora', sans-serif;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .comment-bubble {
            background: #fff;
            border: 1px solid #e2e3f0;
            border-radius: 0 12px 12px 12px;
            padding: 8px 12px; flex: 1;
        }
        .comment-name { font-size: 11.5px; font-weight: 700; color: #0800a0; margin: 0 0 3px; }
        .comment-name .admin-badge {
            display: inline-block;
            font-size: 9.5px; font-weight: 800;
            background: #0800a0; color: #f5cc00;
            padding: 1px 7px; border-radius: 20px;
            margin-left: 6px; vertical-align: middle;
            letter-spacing: .05em; text-transform: uppercase;
        }
        .comment-bubble.admin-comment {
            background: #f4f4ff;
            border-color: rgba(8,0,160,.15);
        }
        .comment-avatar.admin-avatar {
            background: #0800a0;
            color: #f5cc00;
        }
        .comment-text { font-size: 12.5px; color: #23243d; line-height: 1.55; margin: 0; }
        .comment-time { font-size: 10px; color: #9496b8; margin-top: 4px; display: block; }

        .comment-form { display: flex; gap: 10px; margin-top: 14px; }
        .comment-input {
            flex: 1; padding: 9px 14px;
            background: #fff;
            border: 1.5px solid #e2e3f0;
            border-radius: 10px; font-size: 13px; color: #0d0e2e;
            outline: none; font-family: 'DM Sans', sans-serif;
            transition: border-color .18s;
        }
        .comment-input::placeholder { color: #9496b8; }
        .comment-input:focus { border-color: #0800a0; }
        .comment-send {
            padding: 9px 18px;
            background: #0800a0;
            color: #fff; font-weight: 700; font-size: 12.5px;
            border: none; border-radius: 10px; cursor: pointer;
            font-family: 'Sora', sans-serif; white-space: nowrap;
            transition: background .18s;
        }
        .comment-send:hover { background: #0a00c7; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 44px 20px;
            color: #9496b8; font-size: 13px;
            background: #fff; border: 1px solid #e2e3f0;
            border-radius: 16px;
        }
        .empty-state svg { opacity: .3; margin-bottom: 14px; display: block; margin: 0 auto 14px; }
        .empty-state p { margin: 0; font-size: 13.5px; color: #9496b8; }

        /* File selected label */
        .file-selected-label {
            display: none; font-size: 12.5px; color: #126e40;
            font-weight: 600; margin-top: 8px;
        }
    </style>
</head>
<body class="page-report">

    <!-- ── NAV ── -->
    <nav class="rp-nav">
        <div class="rp-nav-logo">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="rp-nav-title">Ka-Barangay Connect</div>
            <div class="rp-nav-sub">San Bartolome</div>
        </div>
        <div class="rp-nav-right">
            <span class="rp-nav-user"><?= htmlspecialchars($resident_name) ?></span>
            <a href="resident.php"     class="rp-nav-btn">Back</a>
            <a href="resident_logout.php" class="rp-nav-btn logout">Log Out</a>
        </div>
    </nav>

    <div class="page-body">
        <div class="rp-layout">

            <!-- ── SUBMIT CARD ── -->
            <div class="submit-card">
                <div class="submit-card-head">
                    <div class="submit-card-eyebrow">Resident Submission</div>
                    <div class="submit-card-title">File a Report</div>
                    <div class="submit-card-sub">
                        Logged in as <strong style="color:#fff;"><?= htmlspecialchars($resident_name) ?></strong>
                    </div>
                </div>

                <div class="submit-form-body">

                    <?php if ($success_msg): ?>
                    <div class="sf-alert success">
                        <span class="sf-alert-icon">&#10003;</span>
                        <?= htmlspecialchars($success_msg) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                    <div class="sf-alert error">
                        <span class="sf-alert-icon">&#33;</span>
                        <?= htmlspecialchars($error_msg) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="report">

                        <!-- Your Info -->
                        <div class="sf-section">
                            <span class="sf-section-pill">Your Info</span>
                            <div class="sf-section-line"></div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-12 col-sm-6">
                                <label class="sf-label">Full Name</label>
                                <input type="text" class="sf-input"
                                       value="<?= htmlspecialchars($resident_name) ?>" disabled>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="sf-label">Purok / Area</label>
                                <select class="sf-input" name="purok" required>
                                    <option value="" disabled>Select your purok...</option>
                                    <?php
                                    $puroks = ['Purok 1','Purok 2','Purok 3','Purok 4',
                                               'Purok 5','Purok 6','Purok 7','PMK','Morgan'];
                                    foreach ($puroks as $pk):
                                        $sel = ($pk === $resident_purok) ? 'selected' : '';
                                    ?>
                                    <option value="<?= $pk ?>" <?= $sel ?>><?= $pk ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="sf-section">
                            <span class="sf-section-pill">Issue Category</span>
                            <div class="sf-section-line"></div>
                        </div>

                        <div class="mb-3">
                            <label class="sf-label">Category</label>
                            <select class="sf-input" name="category" onchange="updateCategoryBadge(this)" required>
                                <option value="" disabled selected>Select a category...</option>
                                <optgroup label="Isyu sa Imprastraktura">
                                    <option value="Infrastructure|Sirang kalsada">Sirang kalsada</option>
                                    <option value="Infrastructure|Lubak sa daan">Lubak sa daan</option>
                                    <option value="Infrastructure|Sirang tulay">Sirang tulay</option>
                                    <option value="Infrastructure|Wasak na sidewalk">Wasak na sidewalk</option>
                                </optgroup>
                                <optgroup label="Isyu sa Kapaligiran">
                                    <option value="Kalikasan|Baradong kanal">Baradong kanal</option>
                                    <option value="Kalikasan|Tambak na basura">Tambak na basura</option>
                                    <option value="Kalikasan|Maruming paligid">Maruming paligid</option>
                                    <option value="Kalikasan|Mabahong tubig">Mabahong tubig</option>
                                </optgroup>
                                <optgroup label="Serbisyong Pangunahing Pangangailangan">
                                    <option value="Serbisyo Publiko|Sirang streetlight">Sirang streetlight</option>
                                    <option value="Serbisyo Publiko|Walang ilaw sa daan">Walang ilaw sa daan</option>
                                    <option value="Serbisyo Publiko|Problema sa tubig">Problema sa tubig</option>
                                    <option value="Serbisyo Publiko|Nawawalang poste ng ilaw">Nawawalang poste ng ilaw</option>
                                </optgroup>
                                <optgroup label="Serbisyong Pampubliko">
                                    <option value="Publiko|Mabagal na aksyon ng barangay">Mabagal na aksyon ng barangay</option>
                                    <option value="Publiko|Hindi naasikaso ang reklamo">Hindi naasikaso ang reklamo</option>
                                    <option value="Publiko|Kulang ang serbisyo">Kulang ang serbisyo</option>
                                    <option value="Publiko|Walang follow-up">Walang follow-up</option>
                                </optgroup>
                                <optgroup label="Kapayapaan at Kaayusan">
                                    <option value="Kapayapaan|Madilim na lugar sa gabi">Madilim na lugar sa gabi</option>
                                    <option value="Kapayapaan|Maingay na kapitbahay">Maingay na kapitbahay</option>
                                    <option value="Kapayapaan|Gulo o away">Gulo o away</option>
                                    <option value="Kapayapaan|Kahina-hinalang tambay">Kahina-hinalang tambay</option>
                                </optgroup>
                            </select>
                            <div class="cat-badge-row">
                                <span class="cat-badge infra"      id="badge-infra">Imprastraktura</span>
                                <span class="cat-badge kalikasan"  id="badge-kalikasan">Kapaligiran</span>
                                <span class="cat-badge serbisyo"   id="badge-serbisyo">Pangunahing Serbisyo</span>
                                <span class="cat-badge publiko"    id="badge-publiko">Serbisyong Pampubliko</span>
                                <span class="cat-badge kapayapaan" id="badge-kapayapaan">Kapayapaan at Kaayusan</span>
                            </div>
                        </div>

                        <!-- Report Details -->
                        <div class="sf-section">
                            <span class="sf-section-pill">Report Details</span>
                            <div class="sf-section-line"></div>
                        </div>

                        <div class="mb-3">
                            <label class="sf-label">Report Title</label>
                            <input type="text" class="sf-input" name="title"
                                   placeholder="Brief title of your report" required>
                        </div>
                        <div class="mb-3">
                            <label class="sf-label">Description</label>
                            <textarea class="sf-input" name="description"
                                      placeholder="Describe the issue in full detail..." required></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="sf-section">
                            <span class="sf-section-pill">Attachment</span>
                            <div class="sf-section-line"></div>
                        </div>

                        <div class="sf-picker mb-1" onclick="document.getElementById('fileInput').click()">
                            <div class="sf-picker-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#0800a0" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <div>
                                <div class="sf-picker-title">Attach an Image</div>
                                <div class="sf-picker-sub">Optional — tap to open gallery</div>
                            </div>
                        </div>
                        <div id="fileSelectedLabel" class="file-selected-label"></div>
                        <input type="file" id="fileInput" name="report_image" accept="image/*" style="display:none;"
                               onchange="handleFileSelect(this)">

                        <button type="submit" class="sf-submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            Submit Report
                        </button>
                    </form>

                </div>
            </div><!-- /submit-card -->

            <!-- ── MY REPORTS ── -->
            <div>
                <div class="section-head">
                    <span class="section-head-title">My Reports</span>
                    <span class="section-head-count"><?= count($my_reports) ?></span>
                    <div class="section-head-line"></div>
                </div>

                <?php if ($comment_success): ?>
                <div class="sf-alert success" style="margin-bottom:14px;">
                    <span class="sf-alert-icon">&#10003;</span>
                    <?= htmlspecialchars($comment_success) ?>
                </div>
                <?php endif; ?>
                <?php if ($comment_error): ?>
                <div class="sf-alert error" style="margin-bottom:14px;">
                    <span class="sf-alert-icon">&#33;</span>
                    <?= htmlspecialchars($comment_error) ?>
                </div>
                <?php endif; ?>

                <?php if (empty($my_reports)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#9496b8" stroke-width="1.5"
                         stroke-linecap="round" width="48" height="48">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <p>You have not submitted any reports yet.</p>
                </div>

                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:16px;">
                <?php foreach ($my_reports as $rpt):
                    $st        = $rpt['status'];
                    $bi        = $badge_map[$st] ?? $badge_map['pending'];
                    $raw_cat   = trim($rpt['category'] ?? '');
                    $cat_parts = explode('|', $raw_cat, 2);
                    $cat_label = isset($cat_parts[1]) ? $cat_parts[1] : $cat_parts[0];
                    $img_path  = $rpt['image_path'] ? '../' . htmlspecialchars($rpt['image_path']) : '';
                    $init      = initials($resident_name);
                    $rid       = (int) $rpt['id'];
                ?>
                <div class="report-post">

                    <!-- Header -->
                    <div class="rp-header">
                        <div style="display:flex; gap:10px; align-items:flex-start; flex:1; min-width:0;">
                            <div class="rp-avatar"><?= $init ?></div>
                            <div style="min-width:0;">
                                <p class="rp-meta-name"><?= htmlspecialchars($resident_name) ?></p>
                                <p class="rp-meta-loc">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="11" height="11">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?= htmlspecialchars($rpt['purok'] ?? 'N/A') ?>
                                </p>
                                <p class="rp-meta-date"><?= date('M j, Y · g:i A', strtotime($rpt['created_at'])) ?></p>
                            </div>
                        </div>
                        <span class="rp-status-badge"
                              style="background:<?= $bi['bg'] ?>; color:<?= $bi['color'] ?>; border-color:<?= $bi['border'] ?>;">
                            <span class="rp-status-dot" style="background:<?= $bi['dot'] ?>;"></span>
                            <?= $bi['label'] ?>
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="rp-body">
                        <span class="rp-cat-pill"><?= htmlspecialchars($cat_label) ?></span>
                        <p class="rp-title"><?= htmlspecialchars($rpt['title']) ?></p>
                        <p class="rp-desc"><?= nl2br(htmlspecialchars($rpt['description'])) ?></p>
                        <?php if ($img_path): ?>
                        <img src="<?= $img_path ?>" alt="Report photo" class="rp-img"
                             onclick="openLightbox('<?= $img_path ?>')"
                             onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>

                    <!-- Comments -->
                    <div class="rp-comments">
                        <div class="rp-comments-head">
                            <span class="rp-comments-label">Comments</span>
                            <span class="rp-comments-count"><?= count($rpt['comments']) ?></span>
                        </div>

                        <?php if (empty($rpt['comments'])): ?>
                        <p class="comment-empty">No comments yet.</p>
                        <?php else: foreach ($rpt['comments'] as $c):
                            $is_admin_comment = !empty($c['is_admin']);
                            $display_name = $is_admin_comment
                                ? ($c['commenter_name'] ?? 'Barangay Admin')
                                : ($c['resident_name'] ?? 'Resident');
                            $ci = $is_admin_comment ? 'BA' : initials($display_name);
                        ?>
                        <div class="comment-item">
                            <div class="comment-avatar <?= $is_admin_comment ? 'admin-avatar' : '' ?>"><?= $ci ?></div>
                            <div class="comment-bubble <?= $is_admin_comment ? 'admin-comment' : '' ?>">
                                <p class="comment-name">
                                    <?= htmlspecialchars($display_name) ?>
                                    <?php if ($is_admin_comment): ?>
                                    <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                </p>
                                <p class="comment-text"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></p>
                                <span class="comment-time"><?= date('M j, Y · g:i A', strtotime($c['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>

                        <form method="POST" class="comment-form">
                            <input type="hidden" name="action"    value="comment">
                            <input type="hidden" name="report_id" value="<?= $rid ?>">
                            <input type="text" name="comment_text" class="comment-input"
                                   placeholder="Leave a comment or feedback..." required>
                            <button type="submit" class="comment-send">Send</button>
                        </form>
                    </div>

                </div><!-- /report-post -->
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" onclick="closeLightbox()"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.9);
                z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(6px);">
        <button onclick="closeLightbox()"
                style="position:fixed;top:18px;right:22px;width:40px;height:40px;border-radius:50%;
                       background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
                       color:#fff;font-size:20px;cursor:pointer;
                       display:flex;align-items:center;justify-content:center;">&#215;</button>
        <img id="lightbox-img" src="" alt=""
             style="max-width:92vw;max-height:92vh;object-fit:contain;border-radius:10px;">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateCategoryBadge(sel) {
        const group = sel.value.split('|')[0];
        document.querySelectorAll('.cat-badge').forEach(b => b.style.display = 'none');
        const map = {
            'Infrastructure':   'badge-infra',
            'Kalikasan':        'badge-kalikasan',
            'Serbisyo Publiko': 'badge-serbisyo',
            'Publiko':          'badge-publiko',
            'Kapayapaan':       'badge-kapayapaan',
        };
        if (map[group]) document.getElementById(map[group]).style.display = 'inline-block';
    }

    function handleFileSelect(input) {
        const label = document.getElementById('fileSelectedLabel');
        if (input.files[0]) {
            label.style.display = 'block';
            label.textContent = 'Selected: ' + input.files[0].name;
        }
    }

    function openLightbox(src) {
        const lb = document.getElementById('lightbox');
        document.getElementById('lightbox-img').src = src;
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    </script>
</body>
</html>