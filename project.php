<!DOCTYPE html>
<html lang="en" class="page-projects-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/resident.css">
</head>
<body class="page-projects">

<?php require_once 'connection.php'; ?>

    <nav class="header" style="height:64px; padding:0 28px;">
        <div class="header-logo lg">
            <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
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
        <img src="assets/img/Project.png" alt="Projects" class="page-banner-img">
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
        $status     = $row['status'];
        $badge_cls  = $badge_map[$status]  ?? 'status-planned';
        $label      = $label_map[$status]  ?? 'Planned';
        $title      = htmlspecialchars($row['title']);
        $dept       = htmlspecialchars($row['department']);
        $desc       = htmlspecialchars($row['description']);
        $img_path   = $row['image_path'] ? htmlspecialchars($row['image_path']) : '';
        $start_raw  = $row['start_date'];
        $start_fmt  = $start_raw ? date('M j, Y', strtotime($start_raw)) : 'TBD';
        $start_verb = ($status === 'completed') ? 'Completed' : (($status === 'planned') ? 'Starts' : 'Started');
?>
                <div class="proj-social-card" data-status="<?= htmlspecialchars($status) ?>">
                    <div class="proj-card-header">
                        <div class="proj-author-info">
                            <div class="proj-avatar">
                                <img src="assets/img/logo.png" alt="<?= $dept ?>">
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
                        <img src="<?= $img_path ?>" alt="<?= $title ?>" class="proj-card-image">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const filterTabs   = document.querySelectorAll('.filter-tab');
        const projectCards = document.querySelectorAll('.proj-social-card');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const filter = this.getAttribute('data-filter');
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                projectCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = '';
                    } else {
                        card.style.display = card.getAttribute('data-status') === filter ? '' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>