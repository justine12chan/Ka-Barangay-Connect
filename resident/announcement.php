<!DOCTYPE html>
<html lang="en" class="page-announcements-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
    <link rel="stylesheet" href="../assets/css/resident-darkmode-append.css">
    <link rel="stylesheet" href="../assets/css/announcement.css">
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
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
        <img src="../assets/img/Announcement.jpg" alt="Announcements" class="page-banner-img">
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
    <script src="../assets/js/announcement.js"></script>

    <a href="report.php" class="floating-btn" title="Submit a report">+ Add Report</a>

    <!-- Dark Mode Toggle -->
    <button id="kbc-dark-toggle" aria-label="Toggle dark mode">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
    </button>

    <link rel="stylesheet" href="../assets/css/announcement.css">

    <!-- Lightbox -->
    <div class="lightbox-backdrop" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">&#215;</button>
        <img id="lightbox-img" src="" alt="Full image">
    </div>

</body>
</html>