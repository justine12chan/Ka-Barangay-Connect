<?php
// resident/report.php
session_start();
date_default_timezone_set('Asia/Manila');

// ── Auth gate ───────────────────────────────────────────────
if (!isset($_SESSION['resident_id'])) {
    header('Location: resident_login.php?redirect=report.php');
    exit;
}

$resident_id    = (int) $_SESSION['resident_id'];
$resident_name  = $_SESSION['resident_name'];
$resident_purok = $_SESSION['resident_purok'] ?? '';

// ── Safety guard: if session ID is somehow 0 or negative, force re-login ──
if ($resident_id <= 0) {
    session_destroy();
    header('Location: resident_login.php?redirect=report.php');
    exit;
}

require_once __DIR__ . '/../connection.php';
// Force MySQL session timezone to PH time so CURRENT_TIMESTAMP defaults store the correct time
mysqli_query($conn, "SET time_zone = '+08:00'");

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

// ── Ensure report_comments has is_read column (one-time migration) ──
$col3 = @mysqli_query($conn, "SHOW COLUMNS FROM `report_comments` LIKE 'is_read'");
if ($col3 && mysqli_num_rows($col3) === 0) {
    mysqli_query($conn, "ALTER TABLE `report_comments` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_admin`");
}

// ── Ensure reports has updated_at column (one-time migration) ──
$col_upd = @mysqli_query($conn, "SHOW COLUMNS FROM `reports` LIKE 'updated_at'");
if ($col_upd && mysqli_num_rows($col_upd) === 0) {
    mysqli_query($conn, "ALTER TABLE `reports` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
    mysqli_query($conn, "UPDATE `reports` SET `updated_at` = `created_at`");
}

// ── Ensure reports has last_notified_status column (one-time migration) ──
// This tracks what status the resident last "saw" so we can detect unseen changes.
$col4 = @mysqli_query($conn, "SHOW COLUMNS FROM `reports` LIKE 'last_notified_status'");
if ($col4 && mysqli_num_rows($col4) === 0) {
    mysqli_query($conn, "ALTER TABLE `reports` ADD COLUMN `last_notified_status` VARCHAR(30) NULL DEFAULT NULL AFTER `status`");
    // Back-fill: set last_notified_status = status so existing reports don't all fire at once
    mysqli_query($conn, "UPDATE `reports` SET `last_notified_status` = `status`");
}

// ── Helper: human-readable status label ─────────────────────────
function _status_label(string $s): string {
    $map = [
        'pending'     => 'Pending',
        'in-progress' => 'In Progress',
        'resolved'    => 'Resolved',
    ];
    return $map[$s] ?? ucfirst($s);
}

// ── AJAX: fetch notifications (admin comments + status changes) ──
if (isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    header('Content-Type: application/json');
    $notifs = [];

    // 1) Admin comments on this resident's reports
    $res = executeQuery("
        SELECT rc.id, rc.report_id, rc.created_at, rc.is_read,
               r.title AS report_title
        FROM report_comments rc
        JOIN reports r ON r.id = rc.report_id
        WHERE r.resident_id = $resident_id
          AND rc.is_admin = 1
        ORDER BY rc.created_at DESC
        LIMIT 30
    ");
    if ($res) {
        while ($n = mysqli_fetch_assoc($res)) {
            $notifs[] = [
                'id'         => 'c' . $n['id'],   // prefix to avoid ID clash with status notifs
                'report_id'  => $n['report_id'],
                'type'       => 'admin_comment',
                'message'    => 'Admin replied on: ' . htmlspecialchars($n['report_title']),
                'is_read'    => $n['is_read'],
                'created_at' => $n['created_at'],
            ];
        }
    }

    // 2) Status changes — reports where current status differs from last_notified_status
    $sres = executeQuery("
        SELECT id, title, status, updated_at
        FROM reports
        WHERE resident_id = $resident_id
          AND last_notified_status IS NOT NULL
          AND status != last_notified_status
        ORDER BY updated_at DESC
        LIMIT 30
    ");
    $status_unread = 0;
    if ($sres) {
        while ($r = mysqli_fetch_assoc($sres)) {
            $notifs[] = [
                'id'         => 's' . $r['id'],
                'report_id'  => $r['id'],
                'type'       => 'status_change',
                'message'    => '"' . htmlspecialchars($r['title']) . '" is now ' . _status_label($r['status']),
                'is_read'    => 0,   // always unread until dismissed
                'created_at' => $r['updated_at'],
            ];
            $status_unread++;
        }
    }

    // Sort all notifications by created_at descending
    usort($notifs, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

    // Unread count = unread admin comments + unread status changes
    $comment_unread_res = executeQuery("
        SELECT COUNT(*) FROM report_comments rc
        JOIN reports r ON r.id = rc.report_id
        WHERE r.resident_id = $resident_id
          AND rc.is_admin = 1
          AND rc.is_read = 0
    ");
    $comment_unread = (int)(mysqli_fetch_row($comment_unread_res)[0] ?? 0);
    $unread = $comment_unread + $status_unread;

    $statuses = [];
    $stres = executeQuery("SELECT id, status FROM reports WHERE resident_id = $resident_id AND resident_id > 0");
    if ($stres) { while ($sr = mysqli_fetch_assoc($stres)) $statuses[(int)$sr['id']] = $sr['status']; }
    echo json_encode(['notifications' => $notifs, 'unread' => $unread, 'report_statuses' => $statuses]);
    exit;
}

// ── AJAX: mark notifications as read ────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'mark_read') {
    header('Content-Type: application/json');

    // Mark admin comments as read
    executeQuery("
        UPDATE report_comments rc
        JOIN reports r ON r.id = rc.report_id
        SET rc.is_read = 1
        WHERE r.resident_id = $resident_id
          AND rc.is_admin = 1
          AND rc.is_read = 0
    ");

    // Acknowledge status changes — sync last_notified_status to current status
    executeQuery("
        UPDATE reports
        SET last_notified_status = status
        WHERE resident_id = $resident_id
          AND status != last_notified_status
    ");

    echo json_encode(['success' => true]);
    exit;
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
// FIX: Added `AND resident_id > 0` as an extra guard so orphaned rows (resident_id = 0)
//      from before the login system was added never leak into any resident's view.
$my_reports = [];
$res = executeQuery("SELECT * FROM reports WHERE resident_id = $resident_id AND resident_id > 0 ORDER BY created_at DESC");
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
    <link rel="stylesheet" href="../assets/css/resident-darkmode-append.css">
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
    <link rel="stylesheet" href="../assets/css/report.css">
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

            <!-- ── Notification Bell ── -->
            <div class="rp-notif-wrap" id="notifWrap">
                <button class="rp-notif-btn" id="notifBtn" onclick="toggleNotifDropdown()" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="rp-notif-badge" id="notifBadge" style="display:none;">0</span>
                </button>

                <!-- Dropdown -->
                <div class="rp-notif-dropdown" id="notifDropdown">
                    <div class="rnd-header">
                        <span class="rnd-title">Notifications</span>
                        <button class="rnd-mark-read" onclick="markAllRead()">Mark all read</button>
                    </div>
                    <div class="rnd-list" id="notifList">
                        <div class="rnd-empty">Loading…</div>
                    </div>
                </div>
            </div>

            <a href="resident.php" class="rp-nav-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="14" height="14"><polyline points="15 18 9 12 15 6"/></svg>
                <span class="btn-label">Back</span>
            </a>
            <a href="resident_logout.php" class="rp-nav-btn logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="14" height="14"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span class="btn-label">Log Out</span>
            </a>
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
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('submitSuccessModal').classList.add('open');
                        });
                    </script>
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
                <div class="report-post" id="report-<?= $rid ?>">

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

    <!-- ── Notification System ── -->
    <script src="../assets/js/report.js"></script>

    <!-- Dark Mode Toggle -->
    <button id="kbc-dark-toggle" aria-label="Toggle dark mode">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
    </button>

    <?php if ($comment_success || $comment_error): ?>
    <!-- Auto-switch to My Reports tab after comment submission -->
    <script>
        document.addEventListener('DOMContentLoaded', () => switchTab('reports'));
    </script>
    <?php endif; ?>

    <!-- ── Submit Success Modal ── -->
    <div id="submitSuccessModal" class="ssm-overlay">
        <div class="ssm-card">
            <div class="ssm-icon">
                <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle class="ssm-circle" cx="26" cy="26" r="24" stroke="#34a853" stroke-width="3" fill="none"/>
                    <polyline class="ssm-check" points="14,27 22,35 38,18" stroke="#34a853" stroke-width="3.5"
                              stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </div>
            <h2 class="ssm-title">Report Submitted!</h2>
            <p class="ssm-sub">Your report has been received and will be reviewed by the barangay within 24&#8211;48 hours.</p>
            <div class="ssm-actions">
                <button class="ssm-btn ssm-btn-primary" onclick="
                    document.getElementById('submitSuccessModal').classList.remove('open');
                    switchTab('reports');
                ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" width="15" height="15">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    View My Reports
                </button>
                <a class="ssm-btn ssm-btn-secondary" href="resident.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" width="15" height="15">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Go Back
                </a>
            </div>
        </div>
    </div>

</body>
</html>