<!DOCTYPE html>
<html lang="en" class="page-announcements-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/resident.css">
</head>
<body class="page-announcements">

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
        <img src="assets/img/Announcement.png" alt="Announcements" class="page-banner-img">
    </div>

    <!-- MAIN CONTENT -->
    <section class="announcements-section">
        <div class="announcements-container">

            <!-- SORT & SEARCH TOOLBAR -->
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
                    <input type="text" placeholder="Search announcements..." id="searchAnnouncements">
                </div>
            </div>

            <!-- ANNOUNCEMENTS FEED — generated from DB -->
            <div class="announcements-feed" id="announcementsFeed">

<?php
$result = executeQuery("SELECT * FROM announcements ORDER BY is_urgent DESC, created_at DESC");

if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_assoc($result)):
        $date_fmt = date('M j, Y · g:i A', strtotime($row['created_at']));
        $is_urgent = (int)$row['is_urgent'];
        $title     = htmlspecialchars($row['title']);
        $body      = htmlspecialchars($row['body']);
        $posted_by = htmlspecialchars($row['posted_by']);
        $img_path  = $row['image_path'] ? htmlspecialchars($row['image_path']) : '';
?>
                <div class="ann-social-card" data-urgent="<?= $is_urgent ?>" data-date="<?= $row['created_at'] ?>">
                    <div class="issue-social-header">
                        <div class="ann-logo-avatar">
                            <img src="assets/img/logo.png" alt="<?= $posted_by ?>" onerror="this.style.display='none'">
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
                            <img src="<?= $img_path ?>" alt="Announcement image" class="issue-social-img">
                        </div>
                        <?php endif; ?>
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
        const searchBox        = document.getElementById('searchAnnouncements');
        const sortDropdown     = document.getElementById('sortBy');
        const feed             = document.getElementById('announcementsFeed');

        searchBox.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            feed.querySelectorAll('.ann-social-card').forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        sortDropdown.addEventListener('change', function () {
            const cards = Array.from(feed.querySelectorAll('.ann-social-card'));
            const val   = this.value;
            cards.sort((a, b) => {
                if (val === 'urgent') {
                    return parseInt(b.dataset.urgent) - parseInt(a.dataset.urgent);
                }
                const da = new Date(a.dataset.date), db = new Date(b.dataset.date);
                return val === 'oldest' ? da - db : db - da;
            });
            cards.forEach(c => feed.appendChild(c));
        });
    </script>
</body>
</html>