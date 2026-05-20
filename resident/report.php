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

    // ── Security: verify this report belongs to the logged-in resident ──
    $owner_check = ($report_id > 0)
        ? executeQuery("SELECT id FROM reports WHERE id = $report_id AND resident_id = $resident_id")
        : false;

    if (!$owner_check || mysqli_num_rows($owner_check) === 0) {
        // Report not found or doesn't belong to this resident — silently redirect
        header('Location: report.php'); exit;
    } elseif ($comment_txt === '') {
        $comment_error = 'Comment cannot be empty.';
    } else {
        $name_esc = mysqli_real_escape_string($conn, $resident_name);
        $comm_esc = mysqli_real_escape_string($conn, $comment_txt);
        executeQuery("INSERT INTO report_comments (report_id, resident_id, resident_name, comment_text, is_admin)
                      VALUES ($report_id, $resident_id, '$name_esc', '$comm_esc', 0)");
        $comment_success = 'Feedback submitted.';
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
    'in-progress' => ['label' => 'In Progress',  'bg' => '#fdf0e8', 'color' => '#8b1a1a', 'border' => '#e8cfa0', 'dot' => '#d4a96a'],
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
    <style>
        :root {
            --primary:      #7c1d1d;
            --primary-dark: #5a1313;
            --primary-mid:  #9b2424;
            --primary-glow: rgba(124,29,29,.10);
            --gold:         #c4922a;
            --gold-light:   #f5e6c8;
            --gold-pale:    #fdf8ee;
            --ink:          #0f172a;
            --ink-2:        #334155;
            --muted:        #64748b;
            --faint:        #94a3b8;
            --border:       #e2e8f0;
            --border-light: #f1f5f9;
            --surface:      #f8fafc;
            --white:        #ffffff;
            --bg:           #eef1f7;
            --sidebar-w:    400px;
            --nav-h:        68px;
            --r:            10px;
            --r-lg:         16px;
            --r-xl:         22px;
            --shadow-xs: 0 1px 2px rgba(0,0,0,.04);
            --shadow-sm: 0 1px 4px rgba(0,0,0,.06), 0 2px 8px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04);
            --shadow-lg: 0 12px 36px rgba(0,0,0,.10), 0 4px 8px rgba(0,0,0,.05);
            --transition: 180ms cubic-bezier(0.4,0,0.2,1);
        }
        /* ─── Base ─────────────────────────────────────── */
        html { scroll-behavior: smooth; }
        body.page-report {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        p, span, div { line-height: 1.5; }
        h1,h2,h3,h4,h5 { font-family: 'DM Serif Display', Georgia, serif; }


        /* ─── Navbar ──────────────────────────────────── */
        .rp-nav {
            height: var(--nav-h);
            background: var(--primary-dark);
            border-bottom: 2px solid rgba(196,146,42,.3);
            padding: 0 32px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 24px rgba(0,0,0,.2);
        }
        .rp-nav-logo {
            width: 36px; height: 36px; border-radius: 8px;
            overflow: hidden; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .rp-nav-logo img { width: 100%; height: 100%; object-fit: contain; filter: brightness(1.2) drop-shadow(0 0 4px rgba(255,255,255,.2)); }
        .rp-nav-wordmark { flex: 1; min-width: 0; }
        .rp-nav-title {
            font-family: 'DM Serif Display', serif;
            font-size: 16px; font-weight: 400; color: #fff;
            letter-spacing: .01em; line-height: 1.2;
        }
        .rp-nav-sub {
            font-size: 10px; font-weight: 500; color: rgba(196,146,42,.85);
            text-transform: uppercase; letter-spacing: .12em;
        }
        .rp-nav-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .rp-nav-user {
            font-size: 12.5px; font-weight: 500;
            color: rgba(255,255,255,.5); display: none;
        }
        @media (min-width: 520px) { .rp-nav-user { display: block; } }
        .rp-nav-btn {
            font-size: 12px; font-weight: 600;
            padding: 7px 16px; border-radius: 8px;
            text-decoration: none;
            transition: background var(--transition), color var(--transition);
            white-space: nowrap;
            color: rgba(255,255,255,.75);
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
        }
        .rp-nav-btn:hover { background: rgba(255,255,255,.16); color: #fff; }
        .rp-nav-btn.logout {
            color: #fca5a5;
            background: rgba(239,68,68,.10);
            border-color: rgba(239,68,68,.22);
        }
        .rp-nav-btn.logout:hover { background: rgba(239,68,68,.20); color: #fca5a5; }

        /* ─── Compact Tab Bar (replaces hero) ──────────────── */
        .compact-tab-bar {
            background: var(--white);
            border-bottom: 1.5px solid var(--border);
            box-shadow: var(--shadow-xs);
        }
        .compact-tab-inner {
            max-width: 100%;
            margin: 0;
            padding: 0 24px;
            display: flex;
            gap: 4px;
        }
        .compact-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 14px 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--muted);
            background: transparent;
            border: none;
            border-bottom: 2.5px solid transparent;
            cursor: pointer;
            transition: color var(--transition), border-color var(--transition);
            white-space: nowrap;
            margin-bottom: -1.5px;
        }
        .compact-tab-btn:hover { color: var(--primary); }
        .compact-tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .compact-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            background: var(--primary-glow);
            color: var(--primary);
            border: 1px solid rgba(124,29,29,.15);
        }
        .compact-tab-btn.active .compact-tab-count {
            background: var(--primary);
            color: #fff;
            border-color: transparent;
        }

        /* ─── Page layout ──────────────────────────────── */
        .page-body {
            min-height: calc(100vh - var(--nav-h));
        }

        /* ── Full-width Hero Banner ── */
        .page-hero {
            width: 100%;
            background: linear-gradient(118deg, #2d0606 0%, #5a1313 40%, #7c1d1d 75%, #4a0e0e 100%);
            padding: 56px 0 0;
            position: relative;
            overflow: hidden;
        }
        /* Crosshatch texture */
        .page-hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        /* Gold glow top-right */
        .page-hero::after {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 480px; height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196,146,42,.16) 0%, transparent 65%);
            pointer-events: none;
        }
        .page-hero-inner {
            position: relative;
            max-width: 100%;
            margin: 0;
            padding: 0 24px;
        }
        .page-hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .18em;
            color: rgba(196,146,42,.9);
            border: 1px solid rgba(196,146,42,.28);
            background: rgba(196,146,42,.08);
            padding: 5px 16px; border-radius: 99px;
            margin-bottom: 20px;
        }
        .page-hero-eyebrow-dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 6px rgba(196,146,42,.7);
            animation: hero-pulse 2s ease-in-out infinite;
        }
        @keyframes hero-pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50% { opacity:.5; transform:scale(1.4); }
        }
        .page-hero-title {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 400; color: #fff;
            line-height: 1.15; margin-bottom: 12px;
            letter-spacing: -.01em;
        }
        .page-hero-title em {
            font-style: italic;
            color: rgba(196,146,42,.9);
        }
        .page-hero-sub {
            font-size: 14px; color: rgba(255,255,255,.5);
            margin-bottom: 36px; line-height: 1.6;
        }
        .page-hero-sub strong { color: rgba(255,255,255,.85); font-weight: 600; }

        /* Stat row */
        .page-hero-stats {
            display: flex; gap: 0;
            border-top: 1px solid rgba(255,255,255,.08);
            margin-top: 0;
        }
        .page-hero-stat {
            flex: 1; padding: 18px 0;
            border-right: 1px solid rgba(255,255,255,.08);
            text-align: center;
        }
        .page-hero-stat:last-child { border-right: none; }
        .page-hero-stat-val {
            font-family: 'DM Serif Display', serif;
            font-size: 26px; color: #fff; line-height: 1;
            margin-bottom: 4px;
        }
        .page-hero-stat-lbl {
            font-size: 10px; font-weight: 600;
            color: rgba(255,255,255,.38);
            text-transform: uppercase; letter-spacing: .1em;
        }

        /* Tab switcher bar */
        .tab-bar {
            width: 100%;
            background: rgba(0,0,0,.25);
            border-top: 1px solid rgba(255,255,255,.07);
            position: relative;
        }
        .tab-bar-inner {
            max-width: 100%;
            margin: 0;
            padding: 0 24px;
            display: flex; gap: 0;
        }
        .tab-btn {
            padding: 14px 24px;
            font-family: inherit; font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,.45);
            background: none; border: none; cursor: pointer;
            border-bottom: 2.5px solid transparent;
            transition: color var(--transition), border-color var(--transition);
            display: flex; align-items: center; gap: 8px;
            letter-spacing: .01em;
        }
        .tab-btn .tab-count {
            font-size: 10px; font-weight: 700;
            background: rgba(255,255,255,.1);
            border-radius: 99px; padding: 1px 8px;
        }
        .tab-btn.active {
            color: #fff;
            border-bottom-color: var(--gold);
        }
        .tab-btn.active .tab-count {
            background: rgba(196,146,42,.25);
            color: var(--gold);
        }
        .tab-btn:hover:not(.active) { color: rgba(255,255,255,.72); }

        /* ── Main content area (single column) ── */
        .page-content {
            max-width: 100%;
            margin: 0;
            padding: 28px 24px 80px;
        }

        /* Tab panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        @media (max-width: 600px) {
            .page-hero-inner { padding: 0 16px; }
            .tab-bar-inner { padding: 0 8px; }
            .page-content { padding: 16px 12px 64px; }
            .page-hero { padding: 40px 0 0; }
            .tab-btn { padding: 12px 14px; font-size: 12px; }
            .page-hero-stats { display: none; }
        }

        /* ─── Submit card ──────────────────────────────── */
        .submit-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .submit-card-head {
            padding: 28px 28px 24px;
            background: linear-gradient(140deg, #3d0a0a 0%, #7c1d1d 100%);
            position: relative;
            overflow: hidden;
        }
        .submit-card-head::after {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196,146,42,.18) 0%, transparent 65%);
            pointer-events: none;
        }
        /* Bottom gold divider */
        .submit-card-head::before {
            content: '';
            position: absolute;
            bottom: 0; left: 28px; right: 28px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(196,146,42,.4), transparent);
            pointer-events: none;
        }
        .submit-card-eyebrow {
            font-size: 9.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .15em;
            color: rgba(196,146,42,.85); margin-bottom: 8px;
            position: relative;
        }
        .submit-card-title {
            font-family: 'DM Serif Display', serif;
            font-size: 22px; font-weight: 400; color: #fff;
            letter-spacing: .01em; line-height: 1.25;
            margin-bottom: 6px; position: relative;
        }
        .submit-card-sub {
            font-size: 12px; color: rgba(255,255,255,.44);
            position: relative;
        }
        .submit-card-sub strong { color: rgba(255,255,255,.82); font-weight: 600; }

        .submit-form-body { padding: 24px 28px 28px; }
        /* placeholder - already replaced above */

        /* ─── Alerts ────────────────────────────────────── */
        .sf-alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 16px; border-radius: var(--r);
            font-size: 13px; font-weight: 500; margin-bottom: 20px;
            border: 1px solid;
        }
        .sf-alert.success { background: #f0fdf4; color: #15803d; border-color: #86efac; }
        .sf-alert.error   { background: #fff1f2; color: #991b1b; border-color: #fecdd3; }
        .sf-alert-icon {
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 900; flex-shrink: 0; margin-top: 1px;
        }
        .sf-alert.success .sf-alert-icon { background: #16a34a; color: #fff; }
        .sf-alert.error   .sf-alert-icon { background: #dc2626; color: #fff; }

        /* ─── Section labels ────────────────────────────── */
        .sf-section {
            display: flex; align-items: center; gap: 10px;
            margin: 22px 0 14px;
        }
        .sf-section:first-child { margin-top: 0; }
        .sf-section-pill {
            font-size: 9.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .12em; white-space: nowrap;
            color: var(--primary); background: var(--primary-glow);
            border: 1px solid rgba(124,29,29,.16);
            padding: 3px 11px; border-radius: 99px;
        }
        .sf-section-line { flex: 1; height: 1px; background: var(--border); }

        /* ─── Form controls ─────────────────────────────── */
        .sf-label {
            display: block; font-size: 11.5px; font-weight: 600;
            color: var(--ink-2); margin-bottom: 6px; letter-spacing: .02em;
            text-transform: uppercase;
        }
        .sf-input {
            display: block; width: 100%;
            padding: 10px 14px;
            font-family: inherit; font-size: 13.5px;
            color: var(--ink);
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
            appearance: none;
        }
        .sf-input::placeholder { color: var(--faint); }
        .sf-input:focus {
            background: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .sf-input:disabled {
            background: var(--border-light);
            color: var(--muted);
            cursor: not-allowed;
        }
        select.sf-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 34px;
        }
        textarea.sf-input { min-height: 96px; resize: vertical; }

        /* ─── Category badges ───────────────────────────── */
        .cat-badge-row { margin-top: 8px; min-height: 22px; }
        .cat-badge {
            display: none; font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 99px; letter-spacing: .03em;
        }
        .cat-badge.infra      { background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; }
        .cat-badge.kalikasan  { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }
        .cat-badge.serbisyo   { background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; }
        .cat-badge.publiko    { background: #fff1f2; color: #9f1239; border: 1px solid #fda4af; }
        .cat-badge.kapayapaan { background: #faf5ff; color: #7e22ce; border: 1px solid #d8b4fe; }

        /* ─── Image picker ──────────────────────────────── */
        .sf-picker {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 18px;
            background: var(--surface);
            border: 1.5px dashed var(--border);
            border-radius: var(--r); cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
        }
        .sf-picker:hover { border-color: var(--primary); background: var(--white); }
        .sf-picker-icon {
            width: 44px; height: 44px; border-radius: 11px;
            background: var(--primary-glow);
            border: 1px solid rgba(124,29,29,.14);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sf-picker-title { font-size: 13.5px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
        .sf-picker-sub   { font-size: 11.5px; color: var(--faint); }

        .file-selected-label {
            display: none; margin-top: 8px;
            font-size: 12px; font-weight: 600; color: #15803d;
            padding: 6px 12px; border-radius: 8px;
            background: #f0fdf4; border: 1px solid #86efac;
        }

        /* ─── Submit button ─────────────────────────────── */
        .sf-submit {
            width: 100%; margin-top: 22px;
            padding: 13px 20px;
            font-family: inherit; font-size: 14px; font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border: none; border-radius: var(--r);
            cursor: pointer; letter-spacing: .02em;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity var(--transition), transform var(--transition), box-shadow var(--transition);
            box-shadow: 0 3px 12px rgba(124,29,29,.30), 0 1px 3px rgba(0,0,0,.12);
        }
        .sf-submit:hover  { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,29,29,.36); }
        .sf-submit:active { transform: translateY(0); box-shadow: none; }

        /* ─── Right column header ───────────────────────── */
        .section-head {
            display: flex; align-items: center; gap: 10px;
        }
        .section-head-title {
            font-family: 'DM Serif Display', serif;
            font-size: 18px; font-weight: 400; color: var(--ink);
            letter-spacing: .01em; white-space: nowrap;
        }
        .section-head-count {
            font-size: 11px; font-weight: 700; color: var(--primary);
            background: var(--primary-glow); border: 1px solid rgba(124,29,29,.15);
            border-radius: 99px; padding: 2px 10px;
        }
        .section-head-line { flex: 1; height: 1px; background: var(--border); }

        /* ─── Report card ───────────────────────────────── */
        .report-post {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition), border-color var(--transition);
        }
        .report-post:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
            border-color: #cbd5e1;
        }

        .rp-header {
            display: flex; align-items: flex-start;
            justify-content: space-between;
            padding: 18px 20px 16px; gap: 12px;
            border-bottom: 1px solid var(--border-light);
            background: var(--surface);
        }
        .rp-header-left { display: flex; gap: 12px; align-items: flex-start; flex: 1; min-width: 0; }
        .rp-avatar {
            width: 40px; height: 40px; border-radius: 11px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #fff; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; letter-spacing: .02em;
            box-shadow: 0 2px 8px rgba(124,29,29,.28);
        }
        .rp-meta { min-width: 0; }
        .rp-meta-name { font-size: 14px; font-weight: 700; color: var(--ink); line-height: 1.3; }
        .rp-meta-loc {
            font-size: 11.5px; color: var(--muted);
            display: flex; align-items: center; gap: 3px; margin-top: 1px;
        }
        .rp-meta-date { font-size: 11px; color: var(--faint); margin-top: 1px; }

        .rp-status-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 600;
            padding: 4px 10px; border-radius: 99px;
            border: 1px solid; flex-shrink: 0; white-space: nowrap;
        }
        .rp-status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

        .rp-body { padding: 16px 20px 18px; }
        .rp-cat-pill {
            display: inline-block; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            padding: 3px 11px; border-radius: 99px; margin-bottom: 10px;
            background: var(--primary-glow); color: var(--primary);
            border: 1px solid rgba(124,29,29,.16);
        }
        .rp-title {
            font-family: 'DM Serif Display', serif;
            font-size: 17px; font-weight: 400; color: var(--ink);
            letter-spacing: .01em; line-height: 1.35; margin-bottom: 8px;
        }
        .rp-desc { font-size: 13.5px; color: var(--muted); line-height: 1.7; }
        .rp-img {
            width: 100%; max-height: 260px; object-fit: cover;
            border-radius: 10px; margin-top: 14px; cursor: zoom-in;
            border: 1px solid var(--border);
            transition: opacity var(--transition);
            display: block;
        }
        .rp-img:hover { opacity: .88; }

        /* ─── Comments ──────────────────────────────────── */
        .rp-comments {
            border-top: 1px solid var(--border-light);
            padding: 16px 20px 20px;
            background: var(--surface);
        }
        .rp-comments-head {
            display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
        }
        .rp-comments-label {
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .10em; color: var(--muted);
        }
        .rp-comments-count {
            font-size: 10px; font-weight: 700; color: var(--primary);
            background: var(--primary-glow); border: 1px solid rgba(124,29,29,.14);
            border-radius: 99px; padding: 1px 8px;
        }
        .comment-empty {
            font-size: 13px; color: var(--faint); margin: 0 0 14px;
            font-style: italic;
        }
        .comment-item { display: flex; gap: 10px; margin-bottom: 12px; }
        .comment-avatar {
            width: 30px; height: 30px; border-radius: 8px;
            background: var(--border); color: var(--muted);
            font-size: 10.5px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .comment-avatar.admin-avatar {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #f9c97a;
        }
        .comment-bubble {
            flex: 1; background: var(--white);
            border: 1px solid var(--border);
            border-radius: 4px 10px 10px 10px;
            padding: 9px 13px;
        }
        .comment-bubble.admin-comment {
            background: linear-gradient(135deg, #fefce8 0%, #fffbf0 100%);
            border-color: #fde68a;
        }
        .comment-name {
            font-size: 12px; font-weight: 700; color: var(--primary); margin-bottom: 3px;
        }
        .comment-name .admin-badge {
            display: inline-block;
            font-size: 8.5px; font-weight: 700;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #f9c97a;
            padding: 1px 7px; border-radius: 99px;
            margin-left: 6px; vertical-align: middle;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .comment-text { font-size: 13px; color: var(--ink-2); line-height: 1.6; }
        .comment-time { font-size: 10px; color: var(--faint); margin-top: 4px; display: block; }

        .comment-form { display: flex; gap: 8px; margin-top: 14px; }
        .comment-input {
            flex: 1; padding: 10px 13px;
            font-family: inherit; font-size: 13px; color: var(--ink);
            background: var(--white); border: 1.5px solid var(--border);
            border-radius: var(--r); outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .comment-input::placeholder { color: var(--faint); }
        .comment-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .comment-send {
            padding: 10px 18px;
            font-family: inherit; font-size: 13px; font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border: none; border-radius: var(--r); cursor: pointer;
            white-space: nowrap;
            transition: opacity var(--transition), transform var(--transition);
            box-shadow: 0 1px 4px rgba(124,29,29,.25);
        }
        .comment-send:hover { opacity: .90; transform: translateY(-1px); }
        .comment-send:active { transform: translateY(0); }
        .comment-send {
            padding: 9px 18px;
            font-family: inherit; font-size: 13px; font-weight: 700;
            color: #fff; background: var(--primary);
            border: none; border-radius: var(--r); cursor: pointer;
            white-space: nowrap;
            transition: background .15s, transform .1s;
            box-shadow: 0 1px 4px rgba(124,29,29,.25);
        }
        .comment-send:hover { background: var(--primary-mid); transform: translateY(-1px); }
        .comment-send:active { transform: translateY(0); }

        /* ─── Empty state ───────────────────────────────── */
        .empty-state {
            text-align: center; padding: 60px 24px;
            background: var(--white);
            border: 1.5px dashed var(--border);
            border-radius: var(--r-xl);
        }
        .empty-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .empty-state p { font-family: 'DM Serif Display', serif; font-size: 16px; color: var(--muted); font-weight: 400; }
        .empty-state span { font-size: 12.5px; color: var(--faint); display: block; margin-top: 5px; }

        /* ─── Responsive ────────────────────────────────── */
        @media (max-width: 560px) {
            .submit-card-head { padding: 22px 20px 20px; }
            .submit-form-body { padding: 18px 20px 22px; }
            .rp-header { padding: 14px 15px 13px; }
            .rp-body { padding: 14px 15px 16px; }
            .rp-comments { padding: 13px 15px 17px; }
            .comment-form { flex-direction: column; }
            .comment-send { width: 100%; text-align: center; }
        }

        /* ════════════════════════════════════════════════
           DARK MODE — report.php specific overrides
           ════════════════════════════════════════════════ */
        html[data-theme="dark"] body.page-report {
            background: #130808 !important;
        }
        html[data-theme="dark"] {
            --ink:          #f0e8df;
            --ink-2:        #d4c8be;
            --muted:        #b09080;
            --faint:        #8a6a5a;
            --border:       #3a2020;
            --border-light: #2e1818;
            --surface:      #1e0f0f;
            --white:        #1a0a0a;
            --bg:           #130808;
        }
        html[data-theme="dark"] .rp-nav {
            background: linear-gradient(90deg,#2e0808,#3a0a0a) !important;
            border-bottom-color: rgba(212,169,106,0.18) !important;
        }
        html[data-theme="dark"] .rp-nav-name  { color: #f0e8df !important; }
        html[data-theme="dark"] .rp-nav-sub   { color: #b09080 !important; }
        html[data-theme="dark"] .back-btn     { border-color: #4a2020 !important; color: #d4a96a !important; }
        html[data-theme="dark"] .back-btn:hover { background: #2a1010 !important; }
        html[data-theme="dark"] .rp-item-card {
            background: #1e0f0f !important;
            border-color: #3a2020 !important;
        }
        html[data-theme="dark"] .rp-item-card:hover { border-color: rgba(212,169,106,0.35) !important; }
        html[data-theme="dark"] .rp-header    { border-bottom-color: #3a2020 !important; }
        html[data-theme="dark"] .rp-title     { color: #f0e8df !important; }
        html[data-theme="dark"] .rp-desc      { color: #b09080 !important; }
        html[data-theme="dark"] .rp-comments  { background: #1a0a0a !important; border-top-color: #3a2020 !important; }
        html[data-theme="dark"] .comment-item-body { background: #2a1010 !important; border-color: #3a2020 !important; }
        html[data-theme="dark"] .comment-text  { color: #c0a898 !important; }
        html[data-theme="dark"] .comment-name  { color: #f0e8df !important; }
        html[data-theme="dark"] .comment-time  { color: #8a6a5a !important; }
        html[data-theme="dark"] .comment-input { background: #2a1010 !important; border-color: #4a2020 !important; color: #f0e8df !important; }
        html[data-theme="dark"] .empty-state   { background: #1e0f0f !important; border-color: #3a2020 !important; }
        html[data-theme="dark"] .empty-state p { color: #b09080 !important; }
        html[data-theme="dark"] .sf-alert.success { background: #082a14 !important; color: #50e090 !important; border-color: #0a4a22 !important; }
        html[data-theme="dark"] .sf-alert.error   { background: #2a0808 !important; color: #f09090 !important; border-color: #4a1010 !important; }
        html[data-theme="dark"] .submit-form-body { background: #1e0f0f !important; }
    </style>
</head>
<body class="page-report">

    <!-- ── NAV ── -->
    <nav class="rp-nav">
        <div class="rp-nav-logo">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div class="rp-nav-wordmark">
            <div class="rp-nav-title">Ka-Barangay Connect</div>
            <div class="rp-nav-sub">San Bartolome</div>
        </div>
        <div class="rp-nav-right">
            <span class="rp-nav-user"><?= htmlspecialchars($resident_name) ?></span>
            <a href="resident.php" class="rp-nav-btn">← Back</a>
            <a href="resident_logout.php" class="rp-nav-btn logout">Log Out</a>
        </div>
    </nav>

    <div class="page-body">

        <!-- ══ COMPACT TAB SWITCHER ══ -->
        <div class="compact-tab-bar">
            <div class="compact-tab-inner">
                <button class="compact-tab-btn active" onclick="switchTab('file')" id="tab-file">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    File a Report
                </button>
                <button class="compact-tab-btn" onclick="switchTab('reports')" id="tab-reports">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    My Reports
                    <span class="compact-tab-count"><?= count($my_reports) ?></span>
                </button>
            </div>
        </div>

        <!-- ══ MAIN CONTENT ══ -->
        <div class="page-content">

            <!-- ── TAB: File a Report ── -->
            <div class="tab-panel active" id="panel-file">
            <div class="submit-card">
                <div class="submit-card-head">
                    <div class="submit-card-eyebrow">New Submission</div>
                    <div class="submit-card-title">File a Report</div>
                    <div class="submit-card-sub">
                        All reports are reviewed by the barangay within 24–48 hours.
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="#9b1f1f" stroke-width="2"
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
            </div><!-- /panel-file -->

            <!-- ── TAB: My Reports ── -->
            <div class="tab-panel" id="panel-reports">

                <div class="section-head" style="margin-bottom: 20px;">
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
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"
                             stroke-linecap="round" width="26" height="26">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <p>No reports submitted yet</p>
                    <span>Your submitted reports will appear here</span>
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
                        <div class="rp-header-left">
                            <div class="rp-avatar"><?= $init ?></div>
                            <div class="rp-meta">
                                <p class="rp-meta-name"><?= htmlspecialchars($resident_name) ?></p>
                                <p class="rp-meta-loc">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="10" height="10">
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

            </div><!-- /panel-reports -->

        </div><!-- /page-content -->
    </div><!-- /page-body -->

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
    /* ── Tab switching ── */
    function switchTab(tab) {
        document.querySelectorAll('.compact-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }

    /* If page loaded with a comment success/error, show the reports tab */
    <?php if ($comment_success || $comment_error): ?>
    document.addEventListener('DOMContentLoaded', () => switchTab('reports'));
    <?php endif; ?>

    /* ── Category badge ── */
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

    /* ── File select ── */
    function handleFileSelect(input) {
        const label = document.getElementById('fileSelectedLabel');
        if (input.files[0]) {
            label.style.display = 'block';
            label.textContent = 'Selected: ' + input.files[0].name;
        }
    }

    /* ── Lightbox ── */
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