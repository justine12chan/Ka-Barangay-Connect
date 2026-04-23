<?php
require_once __DIR__ . '/../connection.php';

$pill_map = [
    'pending'     => ['label' => 'Open',        'class' => 'pill-open'],
    'in-progress' => ['label' => 'In Progress',  'class' => 'pill-progress'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'pill-resolved'],
];

$category_icons = [
    'Infrastructure'   => '🏗️',
    'Kalikasan'        => '🌿',
    'Serbisyo Publiko' => '🏛️',
    'Kapayapaan'       => '☮️',
    'Publiko'          => '👥',
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
<!DOCTYPE html>
<html lang="en" class="page-projects-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Issues - Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
</head>
<body class="page-projects">

    <!-- TOP NAV — identical to project.php -->
    <nav class="header" style="height:64px; padding:0 28px;">
        <div class="header-logo lg">
            <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="header-title">Ka-Barangay Connect</div>
            <div class="header-sub">San Bartolome</div>
        </div>
        <div class="header-right">
            <a href="resident.php" class="back-btn" style="margin-left:0;">&#8592; Back</a>
        </div>
    </nav>

    <!-- BANNER -->
    <div class="page-banner-wrapper">
        <img src="../assets/img/Community Issue.png" alt="Community Issues" class="page-banner-img">
    </div>

    <!-- MAIN CONTENT -->
    <section class="projects-section">
        <div class="projects-container">

            <!-- FILTER TABS -->
            <div class="projects-filter-tabs">
                <button class="filter-tab active" data-filter="all">All Issues</button>
                <button class="filter-tab" data-filter="pending">Open</button>
                <button class="filter-tab" data-filter="in-progress">In Progress</button>
                <button class="filter-tab" data-filter="resolved">Resolved</button>
            </div>

            <!-- ISSUES FEED -->
            <div class="projects-feed">

<?php if (empty($all_reports)): ?>
                <div style="text-align:center; padding:40px; color:#8890b8;">
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
        $img_path  = $row['image_path'] ? htmlspecialchars('../' . $row['image_path']) : '';
        $raw_cat   = isset($row['category']) ? trim($row['category']) : 'Publiko';
        // Category stored as "Group|Specific Issue" or just "Group" for old records
        $cat_parts    = explode('|', $raw_cat, 2);
        $category     = $cat_parts[0];                                                        // broad group for icon/color lookup
        $cat_label    = isset($cat_parts[1]) ? $category . ' · ' . $cat_parts[1] : $category; // "Kalikasan · Baradong kanal"
        $cat_icon     = $category_icons[$category] ?? '📋';
        $report_id = (int) $row['id'];
        $purok     = isset($row['purok']) && $row['purok'] !== '' ? htmlspecialchars($row['purok']) : 'N/A';
        $words     = explode(' ', $reporter);
        $initials  = strtoupper(substr($words[0],0,1).(isset($words[1])?substr($words[1],0,1):''));
        if ($is_anon) $initials = '?';
    ?>

                <!-- Issue Card — mirrors proj-social-card structure -->
                <div class="proj-social-card issue-social-card"
                     data-status="<?= htmlspecialchars($status) ?>"
                     onclick="openIssueModal(<?= $report_id ?>)"
                     role="button" tabindex="0"
                     aria-label="View details: <?= $title ?>">

                    <div class="proj-card-header">
                        <div class="proj-author-info">
                            <div class="proj-avatar issue-reporter-avatar">
                                <?= $initials ?>
                            </div>
                            <div class="proj-author-meta">
                                <p class="proj-author-name"><?= $reporter ?></p>
                                <p class="proj-date-time">📍<?= $purok ?> · <?= $date_fmt ?></p>
                            </div>
                        </div>
                        <span class="proj-status-badge <?= $pill['class'] ?>"><?= $pill['label'] ?></span>
                    </div>

                    <div class="proj-social-body">
                        <span class="issue-category-pill"
                              data-category="<?= htmlspecialchars($cat_label) ?>"
                              data-group="<?= htmlspecialchars($category) ?>"><?= $cat_icon ?> <?= $cat_label ?></span>
                        <p class="proj-social-title"><?= $title ?></p>
                        <p class="proj-social-desc"><?= $desc ?></p>
                    </div>

                    <?php if ($img_path): ?>
                    <div class="proj-image-container">
                        <img src="<?= $img_path ?>"
                             alt="Issue photo"
                             class="proj-card-image clickable-img"
                             onclick="event.stopPropagation(); openLightbox('<?= $img_path ?>')"
                             onerror="this.closest('.proj-image-container').style.display='none'">
                    </div>
                    <?php endif; ?>

                    <div class="proj-details-grid">
                        <div class="proj-detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value"><?= $pill['label'] ?></span>
                        </div>
                        <div class="proj-detail-item">
                            <span class="detail-label">Category</span>
                            <span class="detail-value"><?= $cat_icon ?> <?= $cat_label ?></span>
                        </div>
                        <div class="proj-detail-item">
                            <span class="detail-label">Purok</span>
                            <span class="detail-value">📍<?= $purok ?></span>
                        </div>
                    </div>
                </div>

                <!-- Modal — preserved from original -->
                <div class="issue-modal-backdrop" id="modal-<?= $report_id ?>" onclick="handleBackdropClick(event,<?= $report_id ?>)">
                    <div class="issue-modal-sheet" role="dialog" aria-modal="true">
                        <div class="modal-handle"></div>
                        <div class="modal-sheet-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-reporter-avatar"><?= $initials ?></div>
                                <div>
                                    <p class="proj-author-name" style="margin:0 0 2px;"><?= $reporter ?></p>
                                    <p class="proj-date-time" style="margin:0;"><?= $date_fmt ?></p>
                                </div>
                            </div>
                            <button class="modal-close-btn" onclick="closeIssueModal(<?= $report_id ?>)" aria-label="Close">&#215;</button>
                        </div>
                        <div class="modal-sheet-body">
                            <p class="modal-title"><?= $title ?></p>
                            <div class="modal-meta-row">
                                <span class="proj-status-badge <?= $pill['class'] ?>"><?= $pill['label'] ?></span>
                                <span class="issue-category-pill"
                                      data-category="<?= htmlspecialchars($cat_label) ?>"
                                      data-group="<?= htmlspecialchars($category) ?>"><?= $cat_icon ?> <?= $cat_label ?></span>
                            </div>
                            <div class="modal-detail-grid">
                                <div><div class="modal-detail-label">Reported by</div><div class="modal-detail-value"><?= $reporter ?></div></div>
                                <div><div class="modal-detail-label">Purok</div><div class="modal-detail-value">📍 <?= $purok ?></div></div>
                                <div><div class="modal-detail-label">Category</div><div class="modal-detail-value"><?= $cat_label ?></div></div>
                                <div><div class="modal-detail-label">Date Filed</div><div class="modal-detail-value"><?= date('M j, Y', strtotime($row['created_at'])) ?></div></div>
                                <div><div class="modal-detail-label">Time</div><div class="modal-detail-value"><?= date('g:i A', strtotime($row['created_at'])) ?></div></div>
                            </div>
                            <p class="modal-desc"><?= nl2br($desc) ?></p>
                            <?php if ($img_path): ?>
                                <div class="modal-images">
                                    <img src="<?= $img_path ?>"
                                         alt="Issue photo"
                                         class="modal-img clickable-img"
                                         onclick="openLightbox('<?= $img_path ?>')"
                                         onerror="this.closest('.modal-images').style.display='none'">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

    <?php endforeach; ?>
<?php endif; ?>

            </div><!-- /projects-feed -->
        </div><!-- /projects-container -->
    </section>

    <a href="report.php" class="floating-btn" title="Submit a report">+</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── Category pill colors ── */
        const catColors = {
            'Infrastructure':   { bg: '#fff3e0', color: '#c47200', border: '#ffd580' },
            'Kalikasan':        { bg: '#e6faed', color: '#128548', border: '#7de0a4' },
            'Serbisyo Publiko': { bg: '#e8f0fe', color: '#1a56db', border: '#90aef8' },
            'Kapayapaan':       { bg: '#fce8ff', color: '#8b00c7', border: '#d48cf7' },
            'Publiko':          { bg: '#fff0f0', color: '#c0001a', border: '#f7a0aa' },
        };

        function applyCatColors() {
            document.querySelectorAll('.issue-category-pill[data-category]').forEach(pill => {
                // data-group = broad category for colors; data-category = specific issue label
                const group = pill.dataset.group || pill.dataset.category;
                const c = catColors[group];
                if (c) {
                    pill.style.background    = c.bg;
                    pill.style.color         = c.color;
                    pill.style.borderColor   = c.border;
                }
            });
        }
        applyCatColors();

        /* ── Filter tabs ── */
        const filterTabs  = document.querySelectorAll('.filter-tab');
        const issueCards  = document.querySelectorAll('.issue-social-card');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const filter = this.getAttribute('data-filter');
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                issueCards.forEach(card => {
                    card.style.display = (filter === 'all' || card.getAttribute('data-status') === filter) ? '' : 'none';
                });
            });
        });

        /* ── Keyboard nav for cards ── */
        issueCards.forEach(card => {
            card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
            });
        });

        /* ── Lightbox ── */
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeLightbox();
                document.querySelectorAll('.issue-modal-backdrop.open').forEach(m => m.classList.remove('open'));
                document.body.style.overflow = '';
            }
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
    </script>

    <!-- Modal styles (self-contained, no sidebar needed) -->
    <style>
        .issue-modal-backdrop {
            display:none; position:fixed; inset:0;
            background:rgba(4,0,90,.45); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px);
            z-index:1050; align-items:flex-end; justify-content:center;
        }
        .issue-modal-backdrop.open { display:flex; }
        .issue-modal-sheet {
            background:#fff; width:100%; max-width:680px; max-height:88vh;
            border-radius:20px 20px 0 0; overflow-y:auto;
            box-shadow:0 -8px 40px rgba(4,0,90,.18);
            animation:slideUp .26s cubic-bezier(.22,.61,.36,1);
        }
        @keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
        .modal-handle { width:40px; height:4px; background:#e2e8f0; border-radius:2px; margin:12px auto 0; }
        .modal-sheet-header {
            display:flex; align-items:flex-start; justify-content:space-between;
            padding:14px 20px 12px; border-bottom:1px solid #e2e8f0; gap:10px;
        }
        .modal-reporter-avatar {
            width:44px; height:44px; border-radius:50%;
            background:linear-gradient(135deg,#4e8ef7,#04005a);
            color:#fff; font-size:15px; font-weight:700;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .modal-close-btn {
            width:32px; height:32px; border:none; background:#f1f5f9;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            cursor:pointer; flex-shrink:0; color:#64748b; font-size:18px;
            transition:background .15s;
        }
        .modal-close-btn:hover { background:#e2e8f0; }
        .modal-sheet-body   { padding:20px; }
        .modal-title        { font-size:17px; font-weight:700; color:#0f172a; margin:0 0 10px; line-height:1.4; }
        .modal-meta-row     { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:14px; }
        .modal-detail-grid  {
            display:grid; grid-template-columns:1fr 1fr; gap:10px;
            padding:14px 16px; background:#f0f4ff; border-radius:12px; margin-bottom:14px;
        }
        .modal-detail-label { font-size:10.5px; font-weight:700; color:#8890b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:3px; }
        .modal-detail-value { font-size:13.5px; font-weight:600; color:#0f172a; }
        .modal-desc         { font-size:14px; color:#475569; line-height:1.7; margin:0 0 14px; }
        .modal-images       { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:8px; border-radius:10px; overflow:hidden; }
        .modal-img          { width:100%; height:200px; object-fit:cover; border-radius:8px; background:#f1f5f9; }

        /* Category pill — colors applied dynamically via catColors JS */
        .issue-category-pill {
            display:inline-flex; align-items:center; gap:4px;
            font-size:10.5px; font-weight:600; padding:3px 10px; border-radius:20px;
            letter-spacing:.04em; text-transform:uppercase; margin-bottom:8px;
            border:1px solid #e2e8f0; background:#f8fafc; color:#475569;
        }

        /* Status pill overrides for issue statuses */
        .pill-open     { background:#fff3cd; color:#856404; border:1px solid #ffc107; }
        .pill-progress { background:#e8f0fe; color:#1a56db; border:1px solid #93b4f7; }
        .pill-resolved { background:#e6faed; color:#128548; border:1px solid #6dd98d; }

        /* Make issue avatar show initials (no image) */
        .proj-avatar.issue-reporter-avatar {
            background:#eef2ff; color:#4e8ef7;
            font-size:14px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
        }
        .proj-avatar.issue-reporter-avatar img { display:none; }

        /* ── Clickable image cursor ── */
        .clickable-img { cursor: zoom-in; }

        /* ── Lightbox ── */
        .lightbox-backdrop {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.92); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
            z-index:2000; align-items:center; justify-content:center;
        }
        .lightbox-backdrop.open { display:flex; }
        .lightbox-backdrop img {
            max-width:92vw; max-height:92vh;
            object-fit:contain; border-radius:10px;
            box-shadow:0 8px 60px rgba(0,0,0,.6);
            animation:lbZoom .22s cubic-bezier(.22,.61,.36,1);
        }
        @keyframes lbZoom { from{transform:scale(.88);opacity:0} to{transform:scale(1);opacity:1} }
        .lightbox-close {
            position:fixed; top:18px; right:22px;
            width:40px; height:40px; border-radius:50%;
            background:rgba(255,255,255,.12); border:none;
            color:#fff; font-size:22px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            transition:background .15s;
        }
        .lightbox-close:hover { background:rgba(255,255,255,.25); }
    </style>

    <!-- Lightbox -->
    <div class="lightbox-backdrop" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">&#215;</button>
        <img id="lightbox-img" src="" alt="Full image">
    </div>

</body>
</html>