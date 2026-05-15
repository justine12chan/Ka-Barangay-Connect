<!DOCTYPE html>
<html lang="en" class="page-announcements-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
</head>
<body class="page-announcements">

<?php require_once __DIR__ . '/../connection.php'; ?>

    <nav class="header" style="height:64px; padding:0 28px;">
        <div class="header-logo lg">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="header-title">Ka-Barangay Connect</div>
            <div class="header-sub">San Bartolome</div>
        </div>
        <div class="header-right">
            <a href="resident.php" class="back-btn" style="margin-left:0;">&#8592; Back</a>
        </div>
    </nav>

    <div class="page-banner-wrapper">
        <img src="../assets/img/Announcement.png" alt="Announcements" class="page-banner-img">
    </div>

    <section class="announcements-section">
        <div class="announcements-container">
            <div class="announcements-toolbar">
                <div class="sort-dropdown">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="urgent">Urgent First</option>
                    </select>
                </div>
                <div class="search-box">
                    <input type="text" id="searchAnnouncements" placeholder="Search announcements...">
                </div>
            </div>

            <div class="announcements-feed" id="announcementsFeed">

            <?php
            $result = executeQuery("SELECT * FROM announcements ORDER BY is_urgent DESC, created_at DESC");

            if ($result && mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    $ann_id    = (int) $row['id'];
                    $date_fmt  = date('M j, Y · g:i A', strtotime($row['created_at']));
                    $is_urgent = (int) $row['is_urgent'];
                    $title     = htmlspecialchars($row['title']);
                    $body      = htmlspecialchars($row['body']);
                    $posted_by = htmlspecialchars($row['posted_by']);
                    $img_path  = $row['image_path'] ? htmlspecialchars('../' . $row['image_path']) : '';
            ?>
                <div class="ann-social-card"
                     data-urgent="<?= $is_urgent ?>"
                     data-date="<?= $row['created_at'] ?>"
                     onclick="openAnnModal(<?= $ann_id ?>)"
                     role="button" tabindex="0"
                     aria-label="View details: <?= $title ?>">

                    <div class="issue-social-header">
                        <div class="ann-logo-avatar">
                            <img src="../assets/img/logo.png" alt="<?= $posted_by ?>"
                                 onerror="this.style.display='none'">
                        </div>
                        <div class="issue-reporter-meta">
                            <p class="issue-reporter-name"><?= $posted_by ?></p>
                            <p class="issue-reporter-time"><?= $date_fmt ?></p>
                        </div>
                        <?php if ($is_urgent): ?>
                            <span class="issue-status-pill pill-urgent">URGENT</span>
                        <?php endif; ?>
                    </div>

                    <div class="issue-social-body">
                        <p class="issue-social-title"><?= $title ?></p>
                        <p class="issue-social-desc"><?= $body ?></p>
                        <?php if ($img_path): ?>
                            <div class="issue-social-images">
                                <img src="<?= $img_path ?>"
                                     alt="Announcement image"
                                     class="issue-social-img clickable-img"
                                     onclick="event.stopPropagation(); openLightbox('<?= $img_path ?>')"
                                     onerror="this.closest('.issue-social-images').style.display='none'">
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                
                <div class="issue-modal-backdrop" id="ann-modal-<?= $ann_id ?>" onclick="handleAnnBackdropClick(event,<?= $ann_id ?>)">
                    <div class="issue-modal-sheet" role="dialog" aria-modal="true">
                        <div class="modal-handle"></div>
                        <div class="modal-sheet-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ann-modal-avatar">
                                    <img src="../assets/img/logo.png" alt="<?= $posted_by ?>"
                                         onerror="this.style.display='none';this.parentElement.textContent='SB'">
                                </div>
                                <div>
                                    <p class="proj-author-name" style="margin:0 0 2px;"><?= $posted_by ?></p>
                                    <p class="proj-date-time" style="margin:0;"><?= $date_fmt ?></p>
                                </div>
                            </div>
                            <button class="modal-close-btn" onclick="closeAnnModal(<?= $ann_id ?>)" aria-label="Close">&#215;</button>
                        </div>
                        <div class="modal-sheet-body">
                            <p class="modal-title"><?= $title ?></p>
                            <div class="modal-meta-row">
                                <?php if ($is_urgent): ?>
                                    <span class="issue-status-pill pill-urgent">URGENT</span>
                                <?php endif; ?>
                            </div>
                            <div class="modal-detail-grid">
                                <div><div class="modal-detail-label">Posted by</div><div class="modal-detail-value"><?= $posted_by ?></div></div>
                                <div><div class="modal-detail-label">Date</div><div class="modal-detail-value"><?= date('M j, Y', strtotime($row['created_at'])) ?></div></div>
                                <div><div class="modal-detail-label">Time</div><div class="modal-detail-value"><?= date('g:i A', strtotime($row['created_at'])) ?></div></div>
                                <div><div class="modal-detail-label">Priority</div><div class="modal-detail-value"><?= $is_urgent ? '🔴 Urgent' : '🟢 Normal' ?></div></div>
                            </div>
                            <p class="modal-desc"><?= nl2br($body) ?></p>
                            <?php if ($img_path): ?>
                                <div class="modal-images">
                                    <img src="<?= $img_path ?>"
                                         alt="Announcement image"
                                         class="modal-img clickable-img"
                                         onclick="openLightbox('<?= $img_path ?>')"
                                         onerror="this.closest('.modal-images').style.display='none'">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php
                endwhile;
            else:
            ?>
                <div style="text-align:center; padding:40px; color:#8890b8;">
                    <p>No announcements at the moment.</p>
                </div>
            <?php endif; ?>

            </div>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
 
    const searchBox    = document.getElementById('searchAnnouncements');
    const sortDropdown = document.getElementById('sortBy');
    const feed         = document.getElementById('announcementsFeed');

    searchBox.addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        feed.querySelectorAll('.ann-social-card').forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });


    sortDropdown.addEventListener('change', function () {
        const val   = this.value;
        const cards = Array.from(feed.querySelectorAll('.ann-social-card'));
        cards.sort((a, b) => {
            if (val === 'urgent') {
                return parseInt(b.dataset.urgent) - parseInt(a.dataset.urgent);
            }
            const da = new Date(a.dataset.date), db = new Date(b.dataset.date);
            return val === 'oldest' ? da - db : db - da;
        });
        cards.forEach(c => feed.appendChild(c));
    });


    document.querySelectorAll('.ann-social-card').forEach(card => {
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
    });

    function openAnnModal(id) {
        const m = document.getElementById('ann-modal-' + id);
        if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
    }
    function closeAnnModal(id) {
        const m = document.getElementById('ann-modal-' + id);
        if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
    }
    function handleAnnBackdropClick(e, id) {
        if (e.target === e.currentTarget) closeAnnModal(id);
    }

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
    </script>

    <a href="report.php" class="floating-btn" title="Submit a report">+ Add Report</a>

    <!-- ── Modal & Lightbox styles ── -->
    <style>
        .ann-social-card { cursor: pointer; }

        .issue-modal-backdrop {
            display:none; position:fixed; inset:0;
            background:rgba(4,0,50,.52); backdrop-filter:blur(7px); -webkit-backdrop-filter:blur(7px);
            z-index:1050; align-items:flex-end; justify-content:center;
        }
        .issue-modal-backdrop.open { display:flex; }
        .issue-modal-sheet {
            background:#fff; width:100%; max-width:680px; max-height:90vh;
            border-radius:24px 24px 0 0; overflow-y:auto;
            box-shadow:0 -10px 56px rgba(4,0,90,.22);
            animation:slideUp .28s cubic-bezier(.22,.61,.36,1);
        }
        @keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
        .modal-handle { width:44px; height:4px; background:#e0e3f0; border-radius:2px; margin:14px auto 0; }
        .modal-sheet-header {
            display:flex; align-items:flex-start; justify-content:space-between;
            padding:16px 22px 14px; border-bottom:1px solid #eceef8; gap:12px;
        }
        .ann-modal-avatar {
            width:44px; height:44px; border-radius:50%;
            background:linear-gradient(135deg,#1008b8,#04005a);
            color:#fff; font-size:15px; font-weight:800;
            display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;
            box-shadow:0 2px 10px rgba(8,0,160,.25);
        }
        .ann-modal-avatar img { width:100%; height:100%; object-fit:cover; }
        .modal-close-btn {
            width:34px; height:34px; border:none; background:#f4f5fb;
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            cursor:pointer; flex-shrink:0; color:#5c5e80; font-size:20px;
            transition:background .18s, color .18s;
        }
        .modal-close-btn:hover { background:#e2e3f0; color:#0800a0; }
        .modal-sheet-body   { padding:22px; }
        .modal-title        { font-size:18px; font-weight:800; color:#0b0c24; margin:0 0 12px; line-height:1.35; letter-spacing:-0.02em; }
        .modal-meta-row     { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:16px; }
        .modal-detail-grid  {
            display:grid; grid-template-columns:1fr 1fr; gap:12px;
            padding:15px 18px; background:#f4f5fb; border-radius:14px;
            border:1px solid #e8eaf6; margin-bottom:16px;
        }
        .modal-detail-label { font-size:10px; font-weight:800; color:#a4abcc; text-transform:uppercase; letter-spacing:.09em; margin-bottom:3px; }
        .modal-detail-value { font-size:13.5px; font-weight:700; color:#0b0c24; }
        .modal-desc         { font-size:14.5px; color:#4a5280; line-height:1.75; margin:0 0 16px; }
        .modal-images       { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:8px; border-radius:12px; overflow:hidden; }
        .modal-img          { width:100%; height:200px; object-fit:cover; border-radius:10px; background:#f1f3fa; }

        .clickable-img { cursor: zoom-in; }

        .lightbox-backdrop {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.94); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
            z-index:2000; align-items:center; justify-content:center;
        }
        .lightbox-backdrop.open { display:flex; }
        .lightbox-backdrop img {
            max-width:92vw; max-height:92vh;
            object-fit:contain; border-radius:14px;
            box-shadow:0 12px 80px rgba(0,0,0,.7);
            animation:lbZoom .22s cubic-bezier(.22,.61,.36,1);
        }
        @keyframes lbZoom { from{transform:scale(.88);opacity:0} to{transform:scale(1);opacity:1} }
        .lightbox-close {
            position:fixed; top:18px; right:22px;
            width:42px; height:42px; border-radius:50%;
            background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.1);
            color:#fff; font-size:22px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            transition:background .18s;
        }
        .lightbox-close:hover { background:rgba(255,255,255,.28); }
    </style>

    <!-- Lightbox -->
    <div class="lightbox-backdrop" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">&#215;</button>
        <img id="lightbox-img" src="" alt="Full image">
    </div>

</body>
</html>