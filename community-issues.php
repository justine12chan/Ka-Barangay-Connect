<!DOCTYPE html>
<html lang="en" class="page-community-issues-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Issues - Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/resident.css">
</head>
<body class="page-community-issues">

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
        <img src="assets/img/Community Issue.png" alt="Community Issues" class="page-banner-img">
    </div>

    <!-- MAIN CONTENT -->
    <section class="issues-list-section">
        <div class="issues-container">

            <!-- FILTER TABS -->
            <div class="issues-filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="pending">Open</button>
                <button class="filter-tab" data-filter="in-progress">In Progress</button>
                <button class="filter-tab" data-filter="resolved">Resolved</button>
            </div>

            <!-- ISSUES FEED — generated from DB -->
            <div class="issues-feed">

<?php
$result = executeQuery("SELECT * FROM reports ORDER BY created_at DESC");

$pill_map = [
    'pending'     => ['label' => 'Open',        'class' => 'pill-open'],
    'in-progress' => ['label' => 'In Progress',  'class' => 'pill-progress'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'pill-resolved'],
];

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)):
        $status    = $row['status'];
        $pill      = $pill_map[$status] ?? ['label' => 'Open', 'class' => 'pill-open'];
        $date_fmt  = date('M j, Y · g:i A', strtotime($row['created_at']));
        $is_anon   = (int)$row['is_anonymous'];
        $reporter  = $is_anon ? 'Anonymous' : htmlspecialchars($row['reporter']);
        $title     = htmlspecialchars($row['title']);
        $desc      = htmlspecialchars($row['description']);
        $img_path  = $row['image_path'] ? htmlspecialchars($row['image_path']) : '';

        // Build initials avatar (first letters of first two words)
        $words    = explode(' ', $reporter);
        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
        if ($is_anon) $initials = '?';
?>
                <div class="issue-social-card" data-status="<?= htmlspecialchars($status) ?>">
                    <div class="issue-social-header">
                        <div class="issue-reporter-avatar"><?= $initials ?></div>
                        <div class="issue-reporter-meta">
                            <p class="issue-reporter-name"><?= $reporter ?></p>
                            <p class="issue-reporter-time"><?= $date_fmt ?></p>
                        </div>
                        <span class="issue-status-pill <?= $pill['class'] ?>"><?= $pill['label'] ?></span>
                    </div>
                    <div class="issue-social-body">
                        <p class="issue-social-title"><?= $title ?></p>
                        <p class="issue-social-desc"><?= $desc ?></p>
                        <?php if ($img_path): ?>
                        <div class="issue-social-images">
                            <img src="<?= $img_path ?>" alt="Issue photo" class="issue-social-img">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
<?php
    endwhile;
else:
?>
                <div style="text-align:center; padding:40px; color:#8890b8;">
                    <p>No community issues reported yet.</p>
                </div>
<?php endif; ?>

            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const filterTabs  = document.querySelectorAll('.filter-tab');
        const issueCards  = document.querySelectorAll('.issue-social-card');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const filter = this.getAttribute('data-filter');
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                issueCards.forEach(card => {
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