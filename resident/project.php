<!DOCTYPE html>
<html lang="en" class="page-projects-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
    <link rel="stylesheet" href="../assets/css/resident-darkmode-append.css">
    <link rel="stylesheet" href="../assets/css/project.css">
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
</head>
<body class="page-projects">

<?php require_once __DIR__ . '/../connection.php'; ?>

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
        <img src="../assets/img/Project.png" alt="Projects" class="page-banner-img">
    </div>

    <!-- MAIN CONTENT -->
    <section class="projects-section">
        <div class="projects-container">

            <!-- FILTER TABS -->
            <div class="projects-filter-tabs">
                <button class="filter-tab active" data-filter="all">All Projects</button>
                <button class="filter-tab" data-filter="ongoing">Ongoing</button>
                <button class="filter-tab" data-filter="completed">Completed</button>
                <button class="filter-tab" data-filter="planned">Planned</button>
            </div>

            <!-- PROJECTS FEED — generated from DB -->
            <div class="projects-feed">

<?php
$result = executeQuery("SELECT * FROM programs ORDER BY FIELD(status,'ongoing','planned','completed'), start_date ASC");

$badge_map = [
    'ongoing'   => 'status-ongoing',
    'planned'   => 'status-planned',
    'completed' => 'status-completed',
];
$label_map = [
    'ongoing'   => 'Ongoing',
    'planned'   => 'Planned',
    'completed' => 'Completed',
];

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)):
        $proj_id    = (int) $row['id'];
        $status     = $row['status'];
        $badge_cls  = $badge_map[$status]  ?? 'status-planned';
        $label      = $label_map[$status]  ?? 'Planned';
        $title      = htmlspecialchars($row['title']);
        $dept       = htmlspecialchars($row['department']);
        $desc       = htmlspecialchars($row['description']);
        $img_path   = $row['image_path'] ? htmlspecialchars('../' . $row['image_path']) : '';
        $start_raw  = $row['start_date'];
        $end_raw    = isset($row['end_date']) ? $row['end_date'] : null;
        $start_fmt  = $start_raw ? date('M j, Y', strtotime($start_raw)) : 'TBD';
        $end_fmt    = $end_raw   ? date('M j, Y', strtotime($end_raw))   : 'TBD';
        $start_verb = ($status === 'completed') ? 'Completed' : (($status === 'planned') ? 'Starts' : 'Started');
?>
                <div class="proj-social-card" data-status="<?= htmlspecialchars($status) ?>"
                     onclick="openProjModal(<?= $proj_id ?>)"
                     role="button" tabindex="0"
                     aria-label="View details: <?= $title ?>">

                    <div class="proj-card-header">
                        <div class="proj-author-info">
                            <div class="proj-avatar">
                                <img src="../assets/img/logo.png" alt="<?= $dept ?>">
                            </div>
                            <div class="proj-author-meta">
                                <p class="proj-author-name"><?= $dept ?></p>
                                <p class="proj-date-time"><?= $start_verb ?> <?= $start_fmt ?></p>
                            </div>
                        </div>
                        <span class="proj-status-badge <?= $badge_cls ?>"><?= $label ?></span>
                    </div>
                    <div class="proj-social-body">
                        <p class="proj-social-title"><?= $title ?></p>
                        <p class="proj-social-desc"><?= $desc ?></p>
                    </div>
                    <?php if ($img_path): ?>
                    <div class="proj-image-container">
                        <img src="<?= $img_path ?>"
                             alt="<?= $title ?>"
                             class="proj-card-image clickable-img"
                             onclick="event.stopPropagation(); openLightbox('<?= $img_path ?>')"
                             onerror="this.closest('.proj-image-container').style.display='none'">
                    </div>
                    <?php endif; ?>
                    <div class="proj-details-grid">
                        <div class="proj-detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value <?= $badge_cls ?>-text"><?= $label ?></span>
                        </div>
                        <div class="proj-detail-item">
                            <span class="detail-label"><?= $start_verb ?></span>
                            <span class="detail-value"><?= $start_fmt ?></span>
                        </div>
                    </div>
                </div>

                <!-- Modal for this project -->
                <div class="issue-modal-backdrop" id="proj-modal-<?= $proj_id ?>" onclick="handleProjBackdropClick(event,<?= $proj_id ?>)">
                    <div class="issue-modal-sheet" role="dialog" aria-modal="true">
                        <div class="modal-handle"></div>
                        <div class="modal-sheet-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="proj-modal-avatar">
                                    <img src="../assets/img/logo.png" alt="<?= $dept ?>"
                                         onerror="this.style.display='none';this.parentElement.textContent='SB'">
                                </div>
                                <div>
                                    <p class="proj-author-name" style="margin:0 0 2px;"><?= $dept ?></p>
                                    <p class="proj-date-time" style="margin:0;"><?= $start_verb ?> <?= $start_fmt ?></p>
                                </div>
                            </div>
                            <button class="modal-close-btn" onclick="closeProjModal(<?= $proj_id ?>)" aria-label="Close">&#215;</button>
                        </div>
                        <div class="modal-sheet-body">
                            <p class="modal-title"><?= $title ?></p>
                            <div class="modal-meta-row">
                                <span class="proj-status-badge <?= $badge_cls ?>"><?= $label ?></span>
                            </div>
                            <div class="modal-detail-grid">
                                <div><div class="modal-detail-label">Department</div><div class="modal-detail-value"><?= $dept ?></div></div>
                                <div><div class="modal-detail-label">Status</div><div class="modal-detail-value"><?= $label ?></div></div>
                                <div><div class="modal-detail-label"><?= $start_verb ?></div><div class="modal-detail-value"><?= $start_fmt ?></div></div>
                                <?php if ($end_raw): ?>
                                <div><div class="modal-detail-label">End Date</div><div class="modal-detail-value"><?= $end_fmt ?></div></div>
                                <?php endif; ?>
                            </div>
                            <p class="modal-desc"><?= nl2br($desc) ?></p>
                            <?php if ($img_path): ?>
                                <div class="modal-images">
                                    <img src="<?= $img_path ?>"
                                         alt="<?= $title ?>"
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
                    <p>No programs/projects found.</p>
                </div>
<?php endif; ?>

            </div>
        </div>
    </section>

    <a href="report.php" class="floating-btn" title="Submit a report">+</a>

    <!-- Dark Mode Toggle -->
    <button id="kbc-dark-toggle" aria-label="Toggle dark mode">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
    </button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/project.js"></script>

    <link rel="stylesheet" href="../assets/css/project.css">

    <!-- Lightbox -->
    <div class="lightbox-backdrop" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">&#215;</button>
        <img id="lightbox-img" src="" alt="Full image">
    </div>

</body>
</html>