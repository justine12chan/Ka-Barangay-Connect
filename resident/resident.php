<?php
require_once __DIR__ . '/../connection.php';

// --- Live stats ---
$ann_count   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM announcements"))[0]                       ?? 0;
$prog_active = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status IN ('ongoing','planned')"))[0] ?? 0;

// --- Latest previews ---
$ann_result  = executeQuery("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$latest_ann  = $ann_result ? mysqli_fetch_assoc($ann_result) : null;
$proj_result = executeQuery("SELECT * FROM programs ORDER BY created_at DESC LIMIT 1");
$latest_proj = $proj_result ? mysqli_fetch_assoc($proj_result) : null;

function truncate(string $text, int $limit = 80): string {
    $text = strip_tags($text);
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '…' : $text;
}

$proj_status_map = [
    'ongoing'   => ['label' => 'Ongoing',   'class' => 'b-progress'],
    'planned'   => ['label' => 'Planned',   'class' => 'b-open'],
    'completed' => ['label' => 'Completed', 'class' => 'b-done'],
];
?>
<!DOCTYPE html>
<html lang="en" class="page-resident-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
    <link rel="stylesheet" href="../assets/css/resident-darkmode-append.css">
    <link rel="stylesheet" href="../assets/css/resident-page.css">
</head>
<body class="page-resident">

<!-- ══ NAVBAR ══ -->
<nav class="kbc-nav">
    <a href="resident.php" class="kbc-brand">
        <div class="kbc-brand-logo">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <span class="kbc-brand-name">Ka-Barangay Connect</span>
            <span class="kbc-brand-sub">San Bartolome</span>
        </div>
    </a>

    <ul class="kbc-links">
        <li><a href="#about">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a></li>
        <li><a href="announcement.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Announcements
        </a></li>
        <li><a href="project.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Projects
        </a></li>
        <li><div class="kbc-divider"></div></li>
        <li><a href="#officials">About</a></li>
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="#contact">Contact</a></li>
    </ul>

    <a href="resident_login.php?redirect=report.php" class="kbc-cta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Submit a Report
    </a>

    <button class="kbc-hamburger" id="hamburgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile menu -->
<div class="kbc-mobile-menu" id="mobileMenu">
    <a href="resident.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Home</a>
    <a href="announcement.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Announcements</a>
    <a href="project.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Programs</a>
    <a href="#how-it-works"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> How It Works</a>
    <a href="#officials"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> About</a>
    <a href="#contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.6 3.36 2 2 0 0 1 3.57 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.64a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg> Contact</a>
    <a href="resident_login.php?redirect=report.php" class="kbc-cta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Submit a Report
    </a>
</div>

<!-- ══ HERO ══ -->
<section class="hero-wrap" id="about">
    <div class="container" style="position:relative;z-index:1;">
        <div class="info-box-wrapper">
            <div class="info-box">
                <img src="../assets/img/barangay_header.jpg" alt="Barangay San Bartolome">
                <div class="info-box-overlay"></div>
                <div class="info-box-content">
                    <h2 class="info-box-heading">Barangay San Bartolome</h2>
                </div>
            </div>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-12 col-md-5">
                <div class="vm-card">
                    <div class="vm-bg"></div><div class="vm-strip"></div>
                    <div class="vm-circle1"></div><div class="vm-circle2"></div>
                    <div class="vm-inner">
                        <div class="vm-header">
                            <div class="vm-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#c49a45" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
                    <div class="vm-bg"></div><div class="vm-strip"></div>
                    <div class="vm-circle1"></div><div class="vm-circle2"></div>
                    <div class="vm-inner">
                        <div class="vm-header">
                            <div class="vm-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#c49a45" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <p class="vm-label">Mission</p>
                        </div>
                        <div class="vm-divider"></div>
                        <p class="vm-text">To provide efficient and fair services to all residents, promote development through active community participation, and ensure the safety and well-being of every individual.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ BARANGAY OFFICIALS ══ -->
<section class="officials-wrap" id="officials">
    <div class="container">

        <!-- Section header -->
        <div class="officials-header">
            <span class="officials-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" width="11" height="11" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Barangay Officials
            </span>
            <h2 class="officials-heading">Your <em>Leadership</em> &amp; Administration</h2>
            <p class="officials-sub">Serving the residents of Barangay San Bartolome, City of Sto. Tomas, Batangas</p>
        </div>

        <!-- ── 1. BARANGAY CAPTAIN ── -->
        <div class="officials-group-label">
            <span>Punong Barangay &mdash; Barangay Captain</span>
        </div>

        <div class="officials-chairman-row">
            <div class="official-card official-card--chairman">
                <div class="official-photo-wrap official-photo-wrap--chairman">
                    <img src="../assets/img/officials/fermin_solis.jpg"
                         alt="Hon. Fermin E. Solis"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <div class="official-chairman-info">
                    <span class="official-badge official-badge--chairman">Barangay Captain</span>
                    <h3 class="official-name official-name--chairman">Hon. Fermin E. Solis</h3>
                    <p class="official-pos">Punong Barangay</p>
                    <p class="official-place">Brgy. San Bartolome, Sto. Tomas, Batangas</p>
                    <div class="official-divider"></div>
                    <p class="official-desc">Leads and oversees all barangay affairs, programs, and services for the community's welfare and progress.</p>
                </div>
            </div>
        </div>

        <!-- ── 2. BARANGAY COUNCILORS ── -->
        <div class="officials-group-label" style="margin-top:52px;">
            <span>Sangguniang Barangay &mdash; Barangay Councilors</span>
        </div>

        <div class="officials-councilors-row">

            <!-- 1. Manzo -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/carldarren_manzo.jpg"
                         alt="Hon. Carl-Darren M. Manzo"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Carl-Darren M. Manzo</h3>
                <p class="official-committee">Committee on Land Utilization, Housing &amp; Senior Citizen</p>
            </div>

            <!-- 2. Rodel Evangelista -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/rodel_evangelista.jpg"
                         alt="Hon. Rodel S. Evangelista"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Rodel S. Evangelista</h3>
                <p class="official-committee">Committee on Natural Resources &amp; Environmental, Committee on Agriculture</p>
            </div>

            <!-- 3. Mateo Evangelista -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/mateo_evangelista.jpg"
                         alt="Hon. Mateo M. Evangelista"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Mateo M. Evangelista</h3>
                <p class="official-committee">Committee on Public Works</p>
            </div>

            <!-- 4. Gregorio Meer -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/gregorio_meer.jpg"
                         alt="Hon. Gregorio A. Meer"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Gregorio A. Meer</h3>
                <p class="official-committee">Committee on Finances, Ways and Means</p>
            </div>

            <!-- 5. John Lexter Pecho -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/johnlexter_pecho.jpg"
                         alt="Hon. John Lexter M. Pecho"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. John Lexter M. Pecho</h3>
                <p class="official-committee">Committee on Education</p>
            </div>

            <!-- 6. Marites Caringal -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/marites_caringal.jpg"
                         alt="Hon. Marites M. Caringal"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Marites M. Caringal</h3>
                <p class="official-committee">Committee on Health</p>
            </div>

            <!-- 7. Philip De Leus -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/philip_deleus.jpg"
                         alt="Hon. Philip P. De Leus"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--councilor">Brgy. Councilor</span>
                <h3 class="official-name">Hon. Philip P. De Leus</h3>
                <p class="official-committee">Committee on Peace and Order, Rules and Ordinances</p>
            </div>

        </div>

        <!-- ── 3. BOTTOM ROW: SK + Administrator + Secretary + Treasurer ── -->
        <div class="officials-group-label" style="margin-top:52px;">
            <span>SK Chairman &amp; Barangay Administration</span>
        </div>

        <div class="officials-admin-row">

            <!-- Tormon — SK Chairman (left-most, matches wall photo) -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/cedrick_tormon.jpg"
                         alt="Hon. Cedrick John M. Tormon"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--sk">SK Chairman</span>
                <h3 class="official-name">Hon. Cedrick John M. Tormon</h3>
                <p class="official-committee">Committee on Youth and Sports Development</p>
            </div>

            <!-- Leycano — Administrator -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/amando_leycano.jpg"
                         alt="Amando M. Leycano"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--admin">Barangay Administrator</span>
                <h3 class="official-name">Amando M. Leycano</h3>
                <p class="official-pos">Barangay Administrator</p>
            </div>

            <!-- Manny De Leus — Secretary -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/manny_deleus.jpg"
                         alt="Manny C. De Leus"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--admin">Barangay Secretary</span>
                <h3 class="official-name">Manny C. De Leus</h3>
                <p class="official-pos">Barangay Secretary</p>
            </div>

            <!-- Roberto Meer — Treasurer -->
            <div class="official-card">
                <div class="official-photo-wrap">
                    <img src="../assets/img/officials/roberto_meer.jpg"
                         alt="Roberto A. Meer"
                         onerror="this.src='../assets/img/logo.png'">
                    <div class="official-photo-shine"></div>
                </div>
                <span class="official-badge official-badge--admin">Barangay Treasurer</span>
                <h3 class="official-name">Roberto A. Meer</h3>
                <p class="official-pos">Barangay Treasurer</p>
            </div>

        </div>

    </div>
</section>

<!-- ══ COMMUNITY BOARD ══ -->
<section class="board-wrap" id="about-section">
    <div class="container">
        <div class="section-header">
            <span class="section-eyebrow">Community Board</span>
            <div class="section-line"></div>
        </div>
        <div class="row g-3 justify-content-center">
            <!-- Announcements Panel -->
            <div class="col-12 col-md-5">
                <div class="panel-card" onclick="location.href='announcement.php'">
                    <div class="panel-top">
                        <div class="panel-top-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#c49a45" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
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
                                    <span class="panel-preview-date"><?= date('M j, Y', strtotime($latest_ann['created_at'])) ?></span>
                                    <?php if (!empty($latest_ann['is_urgent']) && $latest_ann['is_urgent']): ?>
                                        <span class="panel-preview-badge urgent">Urgent</span>
                                    <?php endif; ?>
                                </div>
                                <p class="panel-preview-title"><?= htmlspecialchars($latest_ann['title']) ?></p>
                                <?php if (!empty($latest_ann['body'])): ?>
                                    <p class="panel-preview-desc"><?= htmlspecialchars(truncate($latest_ann['body'], 80)) ?></p>
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
            </div>
            <!-- Programs Panel -->
            <div class="col-12 col-md-5">
                <div class="panel-card" onclick="location.href='project.php'">
                    <div class="panel-top">
                        <div class="panel-top-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#c49a45" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </div>
                        <div>
                            <p class="panel-top-title">Programs &amp; Projects</p>
                            <p class="panel-top-sub">Ongoing barangay programs</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <?php if ($latest_proj):
                            $proj_st    = strtolower($latest_proj['status'] ?? 'planned');
                            $proj_badge = $proj_status_map[$proj_st] ?? $proj_status_map['planned'];
                        ?>
                            <div class="panel-preview-item">
                                <div class="panel-preview-meta">
                                    <span class="panel-preview-date"><?= date('M j, Y', strtotime($latest_proj['created_at'])) ?></span>
                                    <span class="panel-preview-badge <?= $proj_badge['class'] ?>"><?= $proj_badge['label'] ?></span>
                                </div>
                                <p class="panel-preview-title"><?= htmlspecialchars($latest_proj['title'] ?? $latest_proj['name'] ?? '') ?></p>
                                <?php if (!empty($latest_proj['description'])): ?>
                                    <p class="panel-preview-desc"><?= htmlspecialchars(truncate($latest_proj['description'], 80)) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="panel-preview-empty">No programs yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="panel-footer">
                        <span class="panel-count"><?= $prog_active ?> active</span>
                        <a href="project.php" class="panel-cta">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="hiw-wrap" id="how-it-works">
    <div class="container" style="position:relative;z-index:1;">

        <div class="hiw-header">
            <div>
                <div class="hiw-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="11" height="11" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    How it works
                </div>
                <h2 class="hiw-heading">Four steps.<br><em>One community.</em></h2>
                <p class="hiw-sub">Connect with your barangay in minutes — report concerns, track progress, stay informed.</p>
            </div>
            <a href="resident_login.php?redirect=report.php" class="hiw-cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Get Started
            </a>
        </div>

        <div class="hiw-steps">

            <!-- Step 1 -->
            <div class="hiw-step">
                <div class="hiw-step-ghost" aria-hidden="true">1</div>
                <div class="hiw-step-top">
                    <div class="hiw-step-dot"><span class="hiw-step-num">1</span></div>
                </div>
                <div class="hiw-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="22" height="22" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="hiw-card-title">Register</div>
                <p class="hiw-card-desc">Create your free account using your full name and purok. No paperwork — takes under a minute.</p>
                <div class="hiw-step-rule"></div>
            </div>

            <!-- Step 2 -->
            <div class="hiw-step">
                <div class="hiw-step-ghost" aria-hidden="true">2</div>
                <div class="hiw-step-top">
                    <div class="hiw-step-dot"><span class="hiw-step-num">2</span></div>
                </div>
                <div class="hiw-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="22" height="22" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div class="hiw-card-title">Report</div>
                <p class="hiw-card-desc">Submit a community concern with a title, category, and description. Attach a photo if you have one.</p>
                <div class="hiw-step-rule"></div>
            </div>

            <!-- Step 3 -->
            <div class="hiw-step">
                <div class="hiw-step-ghost" aria-hidden="true">3</div>
                <div class="hiw-step-top">
                    <div class="hiw-step-dot"><span class="hiw-step-num">3</span></div>
                </div>
                <div class="hiw-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="22" height="22" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="hiw-card-title">Track</div>
                <p class="hiw-card-desc">Monitor your report's status in real time as the barangay takes action.</p>
                <div class="hiw-chips">
                    <span class="hiw-chip hiw-chip-pending">Pending</span>
                    <span class="hiw-chip hiw-chip-progress">In Progress</span>
                    <span class="hiw-chip hiw-chip-resolved">Resolved</span>
                </div>
                <div class="hiw-step-rule"></div>
            </div>

            <!-- Step 4 -->
            <div class="hiw-step">
                <div class="hiw-step-ghost" aria-hidden="true">4</div>
                <div class="hiw-step-top">
                    <div class="hiw-step-dot"><span class="hiw-step-num">4</span></div>
                </div>
                <div class="hiw-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="22" height="22" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div class="hiw-card-title">Stay Informed</div>
                <p class="hiw-card-desc">Browse official announcements and barangay programs. Leave feedback and stay part of the conversation.</p>
                <div class="hiw-step-rule"></div>
            </div>

        </div>

        <div class="hiw-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <p><strong>Your identity is protected.</strong> All reports are tied to your verified resident account and handled with full confidentiality by barangay staff.</p>
        </div>

    </div>
</section>

<!-- ══ FOOTER ══ -->
<footer class="kbc-footer" id="contact">
    <div class="kbc-footer-grid">
        <!-- Brand column -->
        <div>
            <div class="footer-brand-logo"><img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'"></div>
            <div class="footer-brand-name">Ka-Barangay Connect</div>
            <div class="footer-brand-place">Barangay San Bartolome</div>
            <p class="footer-brand-desc">An online platform connecting residents of Barangay San Bartolome with their local government — for faster, more transparent community services.</p>
            <div class="footer-socials">
                <a href="https://www.facebook.com/profile.php?id=61554903165913&mibextid=wwXIfr" target="_blank" rel="noopener" class="footer-social-btn" title="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="mailto:sanbartolome1123@gmail.com" class="footer-social-btn" title="Email">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </a>
            </div>
        </div>

        <!-- Quick Links column -->
        <div>
            <div class="footer-col-title">Quick Links</div>
            <ul class="footer-links">
                <li><a href="resident.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Home</a></li>
                <li><a href="announcement.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>Announcements</a></li>
                <li><a href="project.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Programs</a></li>
                <li><a href="resident_login.php?redirect=report.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Submit a Report</a></li>
                <li><a href="../index.html"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Main Portal</a></li>
            </ul>
        </div>

        <!-- Contact column -->
        <div>
            <div class="footer-col-title">Contact Us</div>
            <div class="footer-contact-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Barangay San Bartolome, Santo Tomas, Batangas, Philippines</span>
            </div>
            <div class="footer-contact-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <a href="mailto:sanbartolome1123@gmail.com">sanbartolome1123@gmail.com</a>
            </div>
            <div class="footer-contact-item">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                <a href="https://www.facebook.com/profile.php?id=61554903165913&mibextid=wwXIfr" target="_blank" rel="noopener">Barangay San Bartolome Official</a>
            </div>
            <div style="margin-top:20px; padding:14px 16px; background:rgba(212,169,106,.07); border:1px solid rgba(212,169,106,.18); border-radius:12px;">
                <p style="font-size:11.5px; color:#d4a96a; font-weight:700; margin:0 0 6px; text-transform:uppercase; letter-spacing:.07em;">Office Hours</p>
                <p style="font-size:12.5px; color:#9e7f70; margin:0; line-height:1.65;">Monday – Friday<br>8:00 AM – 5:00 PM<br><span style="color:#7a5040; font-size:11.5px;">Closed on weekends &amp; holidays</span></p>
            </div>
        </div>
    </div>

    <div class="kbc-footer-bar">
        <p>© <?= date('Y') ?> Ka-Barangay Connect · Barangay San Bartolome. All rights reserved.</p>
        <div class="footer-bar-links">
            <a href="#about">About</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#contact">Contact</a>
        </div>
    </div>
</footer>

<!-- ══ SCRIPTS — loaded at bottom so DOM is ready ══ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
    <script src="../assets/js/resident-page.js"></script>
<!-- Dark Mode Toggle -->
<button id="kbc-dark-toggle" aria-label="Toggle dark mode">
    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
</button>

</body>
</html>