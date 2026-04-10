<!DOCTYPE html>
<html lang="en" class="page-resident-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/resident.css">
</head>
<body class="page-resident">

<?php
require_once 'connection.php';

// Live stats from DB
$r_open     = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='pending'"))[0]     ?? 0;
$r_progress = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='in-progress'"))[0] ?? 0;
$r_resolved = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM reports WHERE status='resolved'"))[0]    ?? 0;
$ann_today  = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements WHERE DATE(created_at)=CURDATE()"))[0] ?? 0;
$prog_active= mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status IN ('ongoing','planned')"))[0]  ?? 0;

// Latest 3 issues for the preview
$latest_issues = executeQuery("SELECT * FROM reports ORDER BY created_at DESC LIMIT 3");

// Latest announcement count (for panel badge)
$ann_count = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements"))[0] ?? 0;

// Latest single announcement for preview
$ann_result   = executeQuery("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$latest_ann   = $ann_result ? mysqli_fetch_assoc($ann_result) : null;

// Latest single project for preview
$proj_result  = executeQuery("SELECT * FROM programs ORDER BY created_at DESC LIMIT 1");
$latest_proj  = $proj_result ? mysqli_fetch_assoc($proj_result) : null;

// Helper: truncate text
function truncate(string $text, int $limit = 80): string {
    $text = strip_tags($text);
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '…' : $text;
}

// Project status badge map
$proj_status_map = [
    'ongoing'   => ['label' => 'Ongoing',   'class' => 'b-progress'],
    'planned'   => ['label' => 'Planned',   'class' => 'b-open'],
    'completed' => ['label' => 'Completed', 'class' => 'b-done'],
];
?>

    <!-- HEADER -->
    <nav class="header" style="height:64px; padding:0 28px;">
        <div class="header-logo lg">
            <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="header-title">Ka-Barangay Connect</div>
            <div class="header-sub">San Bartolome</div>
        </div>
        <div class="header-right">
            <a href="index.html" class="back-btn" style="margin-left:0;">&#8592; Back</a>
        </div>
    </nav>

    <div class="snap-container">

        <!-- SECTION 1 — Hero + Vision / Mission -->
        <section class="snap-section">
            <div class="container">

                <div class="info-box-wrapper">
                    <div class="info-box">
                        <img src="assets/img/barangay_header.jpg" alt="Barangay San Bartolome">
                        <div class="info-box-overlay"></div>
                        <div class="info-box-content">
                            <div class="info-box-label">
                                <div class="label-dot"></div>
                                Official Barangay Portal
                            </div>
                            <h2 class="info-box-heading">Barangay San Bartolome</h2>
                        </div>
                    </div>
                </div>

                <div class="row g-3 justify-content-center">
                    <div class="col-12 col-md-5">
                        <div class="vm-card">
                            <div class="vm-bg"></div>
                            <div class="vm-strip"></div>
                            <div class="vm-circle1"></div>
                            <div class="vm-circle2"></div>
                            <div class="vm-inner">
                                <div class="vm-header">
                                    <div class="vm-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </div>
                                    <p class="vm-label">Vision</p>
                                </div>
                                <div class="vm-divider"></div>
                                <p class="vm-text">A progressive, orderly and safe barangay where every citizen helps one another, stays united, and shows compassion for others to achieve a high quality of life.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="vm-card">
                            <div class="vm-bg"></div>
                            <div class="vm-strip"></div>
                            <div class="vm-circle1"></div>
                            <div class="vm-circle2"></div>
                            <div class="vm-inner">
                                <div class="vm-header">
                                    <div class="vm-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        </svg>
                                    </div>
                                    <p class="vm-label">Mission</p>
                                </div>
                                <div class="vm-divider"></div>
                                <p class="vm-text">To provide efficient and fair services to all residents, promote development through active community participation, and ensure the safety and well-being of every individual.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="scroll-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    Scroll for more
                </div>
            </div>
        </section>

        <!-- SECTION 2 — Community Board -->
        <section class="snap-section">
            <div class="container">

                <div class="section-header">
                    <span class="section-eyebrow">Community Board</span>
                    <div class="section-line"></div>
                </div>

                <div class="row g-3">

                    <!-- Left: Announcements + Projects -->
                    <div class="col-12 col-md-4 d-flex flex-column gap-3">

                        <!-- ANNOUNCEMENTS PANEL -->
                        <div class="panel-card">
                            <div class="panel-top">
                                <div class="panel-top-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="panel-top-title">Announcements</p>
                                    <p class="panel-top-sub">Latest official notices</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <?php if ($latest_ann): ?>
                                    <div class="panel-preview-item">
                                        <div class="panel-preview-meta">
                                            <span class="panel-preview-date">
                                                <?= date('M j, Y', strtotime($latest_ann['created_at'])) ?>
                                            </span>
                                            <?php if (!empty($latest_ann['is_urgent']) && $latest_ann['is_urgent']): ?>
                                                <span class="panel-preview-badge urgent">Urgent</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="panel-preview-title"><?= htmlspecialchars($latest_ann['title']) ?></p>
                                        <?php if (!empty($latest_ann['content'])): ?>
                                            <p class="panel-preview-desc"><?= htmlspecialchars(truncate($latest_ann['content'], 80)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="panel-preview-empty">No announcements yet.</p>
                                <?php endif; ?>
                            </div>
                            <div class="panel-footer">
                                <span class="panel-count"><?= $ann_count ?> total</span>
                                <a href="announcement.php" class="panel-cta">View All</a>
                            </div>
                        </div>

                        <!-- PROJECTS PANEL -->
                        <div class="panel-card">
                            <div class="panel-top">
                                <div class="panel-top-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round">
                                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="panel-top-title">Projects</p>
                                    <p class="panel-top-sub">Ongoing programs</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <?php if ($latest_proj): ?>
                                    <?php
                                        $proj_st    = strtolower($latest_proj['status'] ?? 'planned');
                                        $proj_badge = $proj_status_map[$proj_st] ?? $proj_status_map['planned'];
                                    ?>
                                    <div class="panel-preview-item">
                                        <div class="panel-preview-meta">
                                            <span class="panel-preview-date">
                                                <?= date('M j, Y', strtotime($latest_proj['created_at'])) ?>
                                            </span>
                                            <span class="panel-preview-badge <?= $proj_badge['class'] ?>"><?= $proj_badge['label'] ?></span>
                                        </div>
                                        <p class="panel-preview-title"><?= htmlspecialchars($latest_proj['name'] ?? $latest_proj['title'] ?? '') ?></p>
                                        <?php if (!empty($latest_proj['description'])): ?>
                                            <p class="panel-preview-desc"><?= htmlspecialchars(truncate($latest_proj['description'], 80)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="panel-preview-empty">No projects yet.</p>
                                <?php endif; ?>
                            </div>
                            <div class="panel-footer">
                                <span class="panel-count"><?= $prog_active ?> active</span>
                                <a href="project.php" class="panel-cta">View All</a>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Community Issues -->
                    <div class="col-12 col-md-8">
                        <div class="issues-card">

                            <div class="issues-top">
                                <p class="issues-eyebrow">Resident Reports</p>
                                <h3 class="issues-title">Community Issues</h3>
                                <p class="issues-subtitle">Browse or submit concerns raised by residents</p>
                            </div>

                            <div class="issues-stats">
                                <div class="i-stat">
                                    <span class="i-stat-num"><?= $r_open ?></span>
                                    <span class="i-stat-label">Open</span>
                                </div>
                                <div class="i-stat">
                                    <span class="i-stat-num"><?= $r_progress ?></span>
                                    <span class="i-stat-label">In Progress</span>
                                </div>
                                <div class="i-stat">
                                    <span class="i-stat-num"><?= $r_resolved ?></span>
                                    <span class="i-stat-label">Resolved</span>
                                </div>
                            </div>

                            <div class="issues-body">
<?php
$badge_map = [
    'pending'     => ['label' => 'Open',        'class' => 'b-open'],
    'in-progress' => ['label' => 'In Progress',  'class' => 'b-progress'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'b-done'],
];
$dot_map   = ['pending' => 'open', 'in-progress' => 'progress', 'resolved' => 'done'];

if ($latest_issues && mysqli_num_rows($latest_issues) > 0):
    while ($issue = mysqli_fetch_assoc($latest_issues)):
        $st  = $issue['status'];
        $bi  = $badge_map[$st] ?? $badge_map['pending'];
        $dot = $dot_map[$st]   ?? 'open';
?>
                                <div class="issue-row">
                                    <div class="i-dot <?= $dot ?>"></div>
                                    <span class="issue-text"><?= htmlspecialchars($issue['title']) ?></span>
                                    <span class="i-badge <?= $bi['class'] ?>"><?= $bi['label'] ?></span>
                                </div>
<?php
    endwhile;
else:
?>
                                <div style="padding:12px; font-size:13px; color:#8890b8;">No reports yet.</div>
<?php endif; ?>
                            </div>

                            <div class="issues-footer">
                                <span class="issues-footer-text">Last updated: today</span>
                                <a href="community-issues.php" class="issues-cta">See All Issues</a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </section>

    </div>

    <a href="report.php" class="floating-btn" title="Submit a report">+</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>