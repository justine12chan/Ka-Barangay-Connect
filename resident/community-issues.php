<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Issues - Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">

    <style>
        body { background: var(--gray-50); min-height: 100vh; }

        /* ══ MOBILE TOP BAR ══ */
        .mobile-topbar {
            background: var(--blue-deep);
            border-bottom: 3px solid var(--yellow-vivid);
            padding: 10px 16px;
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 200;
            display: flex; align-items: center; gap: 10px;
        }
        .mobile-topbar .brand-logo { width:36px;height:36px;border-radius:50%;overflow:hidden;flex-shrink:0; }
        .mobile-topbar .brand-logo img { width:100%;height:100%;object-fit:contain; }
        .mobile-topbar .brand-text { flex:1; }
        .mobile-topbar .brand-name { font-family:'Sora',sans-serif;font-size:13px;font-weight:700;color:#fff;line-height:1.2; }
        .mobile-topbar .brand-sub  { font-size:10px;font-weight:500;color:var(--yellow-vivid);text-transform:uppercase;letter-spacing:.1em; }

        .mobile-filter-row {
            display: flex; gap: 6px;
            overflow-x: auto; scrollbar-width: none;
            padding: 8px 16px 4px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: fixed; top: 59px; left: 0; right: 0;
            z-index: 199;
        }
        .mobile-filter-row::-webkit-scrollbar { display:none; }

        /* ══ PAGE WRAPPER ══ */
        .page-wrapper { padding-top: 110px; }

        @media (min-width:1200px) {
            .page-wrapper { padding-top: 24px; }
            .mobile-topbar, .mobile-filter-row { display: none !important; }
        }

        /* ══ SIDEBAR ══ */
        .sidebar-card {
            background: var(--blue-deep);
            border-radius: 20px; padding: 28px 24px;
            color: #fff;
            box-shadow: 0 8px 32px rgba(4,0,90,.22);
            border: 1px solid rgba(255,255,255,.08);
            position: relative; overflow: hidden;
        }
        .sidebar-card::before {
            content:''; position:absolute; right:-40px;top:-40px;
            width:200px;height:200px;
            background:radial-gradient(circle,rgba(245,204,0,.12),transparent 70%);
            pointer-events:none;
        }
        .sidebar-logo-wrap {
            width:72px;height:72px;border-radius:50%;
            background:rgba(255,255,255,.1);
            border:2px solid rgba(255,255,255,.2);
            overflow:hidden;display:flex;align-items:center;justify-content:center;
            margin: 0 auto 16px;
        }
        .sidebar-logo-wrap img { width:100%;height:100%;object-fit:contain; }
        .sidebar-org-name { font-family:'Sora',sans-serif;font-size:16px;font-weight:800;text-align:center;margin-bottom:4px; }
        .sidebar-org-loc  { font-size:11px;font-weight:500;color:var(--yellow-vivid);text-align:center;letter-spacing:.1em;text-transform:uppercase;margin-bottom:20px; }

        .sidebar-stats {
            display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px;
        }
        .stat-box { background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:12px 10px;text-align:center; }
        .stat-box .stat-num   { font-family:'Sora',sans-serif;font-size:22px;font-weight:800;color:var(--yellow-vivid);line-height:1; }
        .stat-box .stat-label { font-size:10px;font-weight:600;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.07em;margin-top:4px; }

        .sidebar-filters { display:flex;flex-direction:column;gap:6px; }
        .sidebar-filter-btn {
            display:flex;align-items:center;gap:10px;
            background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
            border-radius:10px;padding:10px 14px;
            color:rgba(255,255,255,.8);font-size:13px;font-weight:500;
            font-family:'DM Sans',sans-serif;cursor:pointer;
            transition:all .18s ease;text-align:left;width:100%;
        }
        .sidebar-filter-btn .dot  { width:9px;height:9px;border-radius:50%;flex-shrink:0; }
        .sidebar-filter-btn .count { margin-left:auto;font-size:11px;font-weight:700;background:rgba(255,255,255,.12);padding:2px 8px;border-radius:20px; }
        .sidebar-filter-btn:hover,
        .sidebar-filter-btn.active { background:rgba(245,204,0,.15);border-color:rgba(245,204,0,.35);color:#fff; }
        .sidebar-filter-btn.active { font-weight:700; }
        .sidebar-filter-btn.active .count { background:var(--yellow-vivid);color:var(--blue-deep); }
        .dot-all      { background:rgba(255,255,255,.5); }
        .dot-pending  { background:#ffc107; }
        .dot-progress { background:#4e8ef7; }
        .dot-resolved { background:#28c76f; }

        .sidebar-submit-btn {
            display:block;text-align:center;
            background:var(--yellow-vivid);color:var(--blue-deep);
            font-family:'Sora',sans-serif;font-size:13px;font-weight:700;
            padding:12px;border-radius:12px;text-decoration:none;
            margin-top:14px;letter-spacing:.02em;
            box-shadow:0 4px 14px rgba(245,204,0,.25);
            transition:opacity .15s,transform .15s;
        }
        .sidebar-submit-btn:hover { opacity:.88;transform:translateY(-1px);color:var(--blue-deep); }

        .sidebar-back {
            display:flex;align-items:center;gap:8px;margin-top:18px;
            color:rgba(255,255,255,.55);font-size:12px;font-weight:600;
            text-decoration:none;letter-spacing:.04em;font-family:'Sora',sans-serif;
            transition:color .15s;
        }
        .sidebar-back:hover { color:var(--yellow-vivid); }

        /* ══ MAIN CARD ══ */
        .main-card { background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden; }

        .main-card-topnav {
            position:sticky;top:0;
            background:rgba(255,255,255,.92);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
            border-bottom:1px solid var(--border);padding:0 24px;
            display:flex;align-items:center;justify-content:space-between;
            z-index:10;min-height:56px;
        }
        .main-card-topnav .page-heading { font-family:'Sora',sans-serif;font-size:16px;font-weight:800;color:var(--blue-deep); }
        .main-card-topnav .page-sub     { font-size:12px;color:var(--muted);margin-top:1px; }

        /* ── filter tabs (shared mobile + desktop) ── */
        .filter-tab {
            background:none;border:1px solid transparent;border-radius:20px;
            padding:5px 14px;font-size:12px;font-weight:600;color:var(--gray-600);
            font-family:'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;
            transition:all .15s;
        }
        .filter-tab:hover { background:var(--blue-faint);color:var(--blue-main); }
        .filter-tab.active { background:var(--blue-deep);color:#fff;border-color:var(--blue-deep); }

        /* ── Feed ── */
        .issues-feed-area { padding:20px 20px 40px;display:grid;gap:14px; }

        /* ══ ISSUE CARDS ══ */
        .issue-social-card {
            background:#fff;border:1px solid var(--border);border-radius:14px;
            overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);
            cursor:pointer;transition:box-shadow .2s,transform .15s;
        }
        .issue-social-card:hover { box-shadow:0 6px 20px rgba(8,0,160,.12);transform:translateY(-2px); }
        .issue-social-card:active { transform:translateY(0); }

        .issue-social-header { display:flex;align-items:center;gap:10px;padding:14px 16px 10px; }
        .issue-reporter-avatar {
            width:42px;height:42px;border-radius:50%;
            background:var(--blue-light);color:var(--blue-main);
            font-family:'Sora',sans-serif;font-size:13px;font-weight:700;
            display:flex;align-items:center;justify-content:center;
            flex-shrink:0;border:2px solid var(--border);
        }
        .issue-reporter-meta { flex:1;min-width:0; }
        .issue-reporter-name { font-family:'Sora',sans-serif;font-size:13.5px;font-weight:700;color:var(--text-main);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .issue-reporter-time { font-size:11.5px;color:var(--muted);margin:0; }
        .issue-status-pill   { font-size:10.5px;font-weight:700;padding:3px 11px;border-radius:20px;flex-shrink:0;letter-spacing:.03em;text-transform:uppercase;border:1px solid transparent; }
        .pill-open     { background:#fff3cd;color:#856404;border-color:#ffc107; }
        .pill-progress { background:#e8f0fe;color:#1a56db;border-color:#93b4f7; }
        .pill-resolved { background:#e6faed;color:#128548;border-color:#6dd98d; }

        .issue-social-body { padding:0 16px 14px; }
        .issue-category-pill {
            display:inline-flex;align-items:center;gap:4px;
            background:var(--blue-faint);color:var(--blue-mid);
            border:1px solid var(--blue-light);
            font-size:10.5px;font-weight:600;padding:3px 10px;border-radius:20px;
            letter-spacing:.04em;text-transform:uppercase;margin-bottom:8px;
        }
        .issue-social-title { font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:var(--text-main);margin:0 0 5px;line-height:1.4; }
        .issue-social-desc  { font-size:13px;color:var(--gray-600);margin:0 0 10px;line-height:1.65;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden; }
        .issue-social-images { display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:5px;border-radius:8px;overflow:hidden; }
        .issue-social-img { width:100%;height:160px;object-fit:cover;display:block;border-radius:6px;background:var(--gray-100); }

        .card-view-hint {
            display:flex;align-items:center;justify-content:flex-end;gap:4px;
            font-size:11px;font-weight:600;color:var(--blue-mid);
            padding:8px 16px 12px;border-top:1px solid var(--gray-100);margin-top:4px;
        }

        /* ══ MODAL ══ */
        .issue-modal-backdrop {
            display:none;position:fixed;inset:0;
            background:rgba(4,0,90,.45);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
            z-index:1050;align-items:flex-end;justify-content:center;
        }
        .issue-modal-backdrop.open { display:flex; }
        .issue-modal-sheet {
            background:#fff;width:100%;max-width:680px;max-height:88vh;
            border-radius:20px 20px 0 0;overflow-y:auto;
            box-shadow:0 -8px 40px rgba(4,0,90,.18);
            animation:slideUp .26s cubic-bezier(.22,.61,.36,1);
        }
        @keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
        .modal-handle { width:40px;height:4px;background:var(--gray-200);border-radius:2px;margin:12px auto 0; }
        .modal-sheet-header {
            display:flex;align-items:flex-start;justify-content:space-between;
            padding:14px 20px 12px;border-bottom:1px solid var(--gray-200);gap:10px;
        }
        .modal-reporter-avatar {
            width:44px;height:44px;border-radius:50%;
            background:linear-gradient(135deg,var(--blue-mid),var(--blue-deep));
            color:#fff;font-family:'Sora',sans-serif;font-size:15px;font-weight:700;
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
        }
        .modal-close-btn {
            width:32px;height:32px;border:none;background:var(--gray-100);
            border-radius:50%;display:flex;align-items:center;justify-content:center;
            cursor:pointer;flex-shrink:0;color:var(--gray-600);font-size:18px;
            transition:background .15s;
        }
        .modal-close-btn:hover { background:var(--gray-200); }
        .modal-sheet-body { padding:20px; }
        .modal-title { font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:var(--text-main);margin:0 0 10px;line-height:1.4; }
        .modal-meta-row { display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:14px; }
        .modal-detail-grid {
            display:grid;grid-template-columns:1fr 1fr;gap:10px;
            padding:14px 16px;background:var(--blue-faint);border-radius:12px;margin-bottom:14px;
        }
        .modal-detail-label { font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px; }
        .modal-detail-value { font-size:13.5px;font-weight:600;color:var(--text-main); }
        .modal-desc   { font-size:14px;color:var(--gray-600);line-height:1.7;margin:0 0 14px; }
        .modal-images { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;border-radius:10px;overflow:hidden; }
        .modal-img    { width:100%;height:200px;object-fit:cover;border-radius:8px;background:var(--gray-100); }

        @media (min-width:500px) {
            .issue-modal-backdrop { align-items:center;padding:20px; }
            .issue-modal-sheet    { border-radius:20px;max-height:82vh; }
        }

        .empty-state { text-align:center;padding:60px 20px;color:var(--muted); }
        .empty-state .empty-icon { font-size:40px;margin-bottom:12px; }
        .empty-state p { font-size:14px;margin:0; }
    </style>
</head>
<body>

<?php
require_once __DIR__ . '/../connection.php';

$pill_map = [
    'pending'     => ['label' => 'Open',        'class' => 'pill-open'],
    'in-progress' => ['label' => 'In Progress',  'class' => 'pill-progress'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'pill-resolved'],
];

$category_icons = [
    'roads'         => '🛣️',
    'drainage'      => '🌊',
    'electricity'   => '⚡',
    'garbage'       => '🗑️',
    'water'         => '💧',
    'streetlights'  => '💡',
    'noise'         => '🔊',
    'public safety' => '🚨',
    'vandalism'     => '🖌️',
    'flooding'      => '🌧️',
    'health'        => '🏥',
    'other'         => '📋',
];

$result = executeQuery("SELECT * FROM reports ORDER BY created_at DESC");
$all_reports = [];
$counts = ['all' => 0, 'pending' => 0, 'in-progress' => 0, 'resolved' => 0];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_reports[] = $row;
        $counts['all']++;
        $s = $row['status'];
        if (isset($counts[$s])) $counts[$s]++;
    }
}
?>

<!-- Mobile top bar -->
<div class="mobile-topbar d-xl-none">
    <div class="brand-logo">
        <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
    </div>
    <div class="brand-text">
        <div class="brand-name">Ka-Barangay Connect</div>
        <div class="brand-sub">San Bartolome</div>
    </div>
    <a href="resident.php" class="back-btn" style="margin-left:0;font-size:11px;">&#8592; Back</a>
</div>

<!-- Mobile filter strip -->
<div class="mobile-filter-row d-xl-none">
    <button class="filter-tab active" data-filter="all">All (<?= $counts['all'] ?>)</button>
    <button class="filter-tab" data-filter="pending">Open (<?= $counts['pending'] ?>)</button>
    <button class="filter-tab" data-filter="in-progress">In Progress (<?= $counts['in-progress'] ?>)</button>
    <button class="filter-tab" data-filter="resolved">Resolved (<?= $counts['resolved'] ?>)</button>
</div>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="row g-4">

            <!-- ══ SIDEBAR (col-xl-3) ══ -->
            <div class="col-12 col-xl-3 d-none d-xl-block">
                <div class="position-sticky" style="top:24px;">
                    <div class="sidebar-card">
                        <div class="sidebar-logo-wrap">
                            <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
                        </div>
                        <div class="sidebar-org-name">Ka-Barangay Connect</div>
                        <div class="sidebar-org-loc">San Bartolome</div>

                        <div class="sidebar-stats">
                            <div class="stat-box"><div class="stat-num"><?= $counts['all'] ?></div><div class="stat-label">Total</div></div>
                            <div class="stat-box"><div class="stat-num"><?= $counts['pending'] ?></div><div class="stat-label">Open</div></div>
                            <div class="stat-box"><div class="stat-num"><?= $counts['in-progress'] ?></div><div class="stat-label">In Progress</div></div>
                            <div class="stat-box"><div class="stat-num"><?= $counts['resolved'] ?></div><div class="stat-label">Resolved</div></div>
                        </div>

                        <div class="sidebar-filters">
                            <button class="sidebar-filter-btn active" data-filter="all"><span class="dot dot-all"></span>All Issues<span class="count"><?= $counts['all'] ?></span></button>
                            <button class="sidebar-filter-btn" data-filter="pending"><span class="dot dot-pending"></span>Open<span class="count"><?= $counts['pending'] ?></span></button>
                            <button class="sidebar-filter-btn" data-filter="in-progress"><span class="dot dot-progress"></span>In Progress<span class="count"><?= $counts['in-progress'] ?></span></button>
                            <button class="sidebar-filter-btn" data-filter="resolved"><span class="dot dot-resolved"></span>Resolved<span class="count"><?= $counts['resolved'] ?></span></button>
                        </div>

                        <a href="report.php" class="sidebar-submit-btn">+ Submit a Report</a>
                        <a href="resident.php" class="sidebar-back">&#8592; Back to Dashboard</a>
                    </div>
                </div>
            </div>

            <!-- ══ MAIN CONTENT (col-xl-9) ══ -->
            <div class="col-12 col-xl-9 mb-5">
                <div class="main-card">

                    <!-- Sticky top nav (desktop only) -->
                    <div class="main-card-topnav d-none d-xl-flex">
                        <div>
                            <div class="page-heading">Community Issues</div>
                            <div class="page-sub">Reports submitted by residents</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="filter-tab active" data-filter="all">All</button>
                            <button class="filter-tab" data-filter="pending">Open</button>
                            <button class="filter-tab" data-filter="in-progress">In Progress</button>
                            <button class="filter-tab" data-filter="resolved">Resolved</button>
                        </div>
                    </div>

                    <!-- Feed -->
                    <div class="issues-feed-area" id="issues-feed">

                    <?php if (empty($all_reports)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No community issues reported yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_reports as $row):
                            $status    = $row['status'];
                            $pill      = $pill_map[$status] ?? ['label' => 'Open', 'class' => 'pill-open'];
                            $date_fmt  = date('M j, Y · g:i A', strtotime($row['created_at']));
                            $is_anon   = (int) $row['is_anonymous'];
                            $reporter  = $is_anon ? 'Anonymous' : htmlspecialchars($row['reporter']);
                            $title     = htmlspecialchars($row['title']);
                            $desc      = htmlspecialchars($row['description']);
                            $img_path  = $row['image_path'] ? htmlspecialchars($row['image_path']) : '';
                            $category  = isset($row['category']) ? strtolower(trim($row['category'])) : 'other';
                            $cat_label = ucfirst($category ?: 'Other');
                            $cat_icon  = $category_icons[$category] ?? $category_icons['other'];
                            $report_id = (int) $row['id'];
                            $words     = explode(' ', $reporter);
                            $initials  = strtoupper(substr($words[0],0,1).(isset($words[1])?substr($words[1],0,1):''));
                            if ($is_anon) $initials = '?';
                        ?>

                        <!-- Card -->
                        <div class="issue-social-card"
                             data-status="<?= htmlspecialchars($status) ?>"
                             onclick="openIssueModal(<?= $report_id ?>)"
                             role="button" tabindex="0"
                             aria-label="View details: <?= $title ?>">

                            <div class="issue-social-header">
                                <div class="issue-reporter-avatar"><?= $initials ?></div>
                                <div class="issue-reporter-meta">
                                    <p class="issue-reporter-name"><?= $reporter ?></p>
                                    <p class="issue-reporter-time"><?= $date_fmt ?></p>
                                </div>
                                <span class="issue-status-pill <?= $pill['class'] ?>"><?= $pill['label'] ?></span>
                            </div>

                            <div class="issue-social-body">
                                <span class="issue-category-pill"><?= $cat_icon ?> <?= $cat_label ?></span>
                                <p class="issue-social-title"><?= $title ?></p>
                                <p class="issue-social-desc"><?= $desc ?></p>
                                <?php if ($img_path): ?>
                                    <div class="issue-social-images">
                                        <img src="<?= $img_path ?>" alt="Issue photo" class="issue-social-img">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-view-hint">View full details &rsaquo;</div>
                        </div>

                        <!-- Modal -->
                        <div class="issue-modal-backdrop" id="modal-<?= $report_id ?>" onclick="handleBackdropClick(event,<?= $report_id ?>)">
                            <div class="issue-modal-sheet" role="dialog" aria-modal="true">
                                <div class="modal-handle"></div>
                                <div class="modal-sheet-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="modal-reporter-avatar"><?= $initials ?></div>
                                        <div>
                                            <p class="issue-reporter-name" style="margin:0 0 2px;"><?= $reporter ?></p>
                                            <p class="issue-reporter-time" style="margin:0;"><?= $date_fmt ?></p>
                                        </div>
                                    </div>
                                    <button class="modal-close-btn" onclick="closeIssueModal(<?= $report_id ?>)" aria-label="Close">&#215;</button>
                                </div>
                                <div class="modal-sheet-body">
                                    <p class="modal-title"><?= $title ?></p>
                                    <div class="modal-meta-row">
                                        <span class="issue-status-pill <?= $pill['class'] ?>"><?= $pill['label'] ?></span>
                                        <span class="issue-category-pill"><?= $cat_icon ?> <?= $cat_label ?></span>
                                    </div>
                                    <div class="modal-detail-grid">
                                        <div><div class="modal-detail-label">Reported by</div><div class="modal-detail-value"><?= $reporter ?></div></div>
                                        <div><div class="modal-detail-label">Category</div><div class="modal-detail-value"><?= $cat_label ?></div></div>
                                        <div><div class="modal-detail-label">Date Filed</div><div class="modal-detail-value"><?= date('M j, Y', strtotime($row['created_at'])) ?></div></div>
                                        <div><div class="modal-detail-label">Time</div><div class="modal-detail-value"><?= date('g:i A', strtotime($row['created_at'])) ?></div></div>
                                    </div>
                                    <p class="modal-desc"><?= nl2br($desc) ?></p>
                                    <?php if ($img_path): ?>
                                        <div class="modal-images">
                                            <img src="<?= $img_path ?>" alt="Issue photo" class="modal-img">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    <?php endif; ?>

                    </div><!-- /issues-feed-area -->
                </div><!-- /main-card -->
            </div>

        </div><!-- /row -->
    </div><!-- /container-xl -->
</div><!-- /page-wrapper -->

<!-- Mobile submit FAB -->
<a href="report.php" class="floating-btn d-xl-none" title="Submit a report">+</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Filter: syncs sidebar + topnav + mobile strip ── */
const allFilterBtns = document.querySelectorAll('.filter-tab, .sidebar-filter-btn');
const issueCards    = document.querySelectorAll('.issue-social-card');

function applyFilter(filter) {
    allFilterBtns.forEach(btn => btn.classList.toggle('active', btn.getAttribute('data-filter') === filter));
    issueCards.forEach(card => {
        card.style.display = (filter === 'all' || card.getAttribute('data-status') === filter) ? '' : 'none';
    });
}

allFilterBtns.forEach(btn => btn.addEventListener('click', () => applyFilter(btn.getAttribute('data-filter'))));

/* Keyboard nav for cards */
issueCards.forEach(card => {
    card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); } });
});

/* ── Modal ── */
function openIssueModal(id) {
    const m = document.getElementById('modal-' + id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeIssueModal(id) {
    const m = document.getElementById('modal-' + id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function handleBackdropClick(e, id) {
    if (e.target === e.currentTarget) closeIssueModal(id);
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.issue-modal-backdrop.open').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
    }
});
</script>
</body>
</html>