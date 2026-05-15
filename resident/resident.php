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
    <style>
        body.page-resident { background: #04003a; overflow: visible; height: auto; }
        html.page-resident-html { height: auto; }

        /* ── NAVBAR ── */
        .kbc-nav {
            position: sticky; top: 0; z-index: 200;
            display: flex; align-items: center;
            height: 64px; padding: 0 32px;
            background: rgba(4,0,58,.96);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(245,204,0,.15);
            box-shadow: 0 2px 24px rgba(0,0,0,.35);
        }
        .kbc-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; margin-right: 28px; }
        .kbc-brand-logo { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .kbc-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .kbc-brand-name { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 800; color: #fff; line-height: 1.1; display: block; }
        .kbc-brand-sub  { font-size: 10px; color: #f5cc00; font-weight: 600; display: block; }
        .kbc-links { display: flex; align-items: center; gap: 2px; list-style: none; padding: 0; margin: 0; }
        .kbc-links a { display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #b0b8d8; text-decoration: none; transition: background .18s, color .18s; white-space: nowrap; }
        .kbc-links a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .kbc-links a.active { color: #f5cc00; background: rgba(245,204,0,.1); }
        .kbc-links .kbc-divider { width: 1px; height: 20px; background: rgba(255,255,255,.12); margin: 0 6px; }
        .kbc-cta { margin-left: auto; display: flex; align-items: center; gap: 7px; padding: 9px 20px; border-radius: 10px; background: linear-gradient(135deg, #f5cc00, #e6b800); color: #04005a; font-weight: 800; font-size: 13px; text-decoration: none; white-space: nowrap; box-shadow: 0 3px 14px rgba(245,204,0,.3); transition: opacity .18s, transform .1s; font-family: 'Sora', sans-serif; }
        .kbc-cta:hover { opacity: .9; transform: translateY(-1px); color: #04005a; }
        .kbc-hamburger { display: none; margin-left: auto; background: none; border: 1px solid rgba(255,255,255,.18); border-radius: 8px; padding: 7px 9px; flex-direction: column; align-items: center; justify-content: center; gap: 4px; cursor: pointer; }
        .kbc-hamburger span { display: block; width: 20px; height: 2px; background: #fff; border-radius: 2px; transition: all .22s; }
        .kbc-mobile-menu { display: none; flex-direction: column; gap: 4px; background: rgba(4,0,58,.99); border-bottom: 1px solid rgba(245,204,0,.12); padding: 10px 16px 14px; position: sticky; top: 64px; z-index: 199; }
        .kbc-mobile-menu.open { display: flex; }
        .kbc-mobile-menu a { padding: 10px 14px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #b0b8d8; text-decoration: none; transition: background .18s, color .18s; }
        .kbc-mobile-menu a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .kbc-mobile-menu .kbc-cta { margin: 8px 0 0; justify-content: center; margin-left: 0; }
        @media (max-width: 820px) { .kbc-links, .kbc-cta { display: none; } .kbc-hamburger { display: flex; } }
        @media (max-width: 480px) { .kbc-nav { padding: 0 16px; } }

        /* ── HERO ── */
        .hero-wrap { background: linear-gradient(160deg, #06006e 0%, #04005a 55%, #02003a 100%); min-height: 100vh; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; padding: 48px 0 60px; }
        .hero-wrap::before { content: ''; position: absolute; top: -120px; right: -120px; width: 500px; height: 500px; border-radius: 50%; background: radial-gradient(circle, rgba(245,204,0,.08), transparent 70%); pointer-events: none; }
        .hero-wrap::after  { content: ''; position: absolute; bottom: -80px; left: -80px; width: 350px; height: 350px; border-radius: 50%; background: radial-gradient(circle, rgba(8,0,160,.35), transparent 70%); pointer-events: none; }

        /* ── COMMUNITY BOARD ── */
        .board-wrap { background: #f0f2fa; padding: 56px 0 64px; }
        .board-wrap .section-eyebrow { background: #0800a0; }

        /* ── HOW IT WORKS — CARD SECTION ── */
        .hiw-wrap {
            background: linear-gradient(160deg, #06006e 0%, #04005a 60%, #03004a 100%);
            padding: 72px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .hiw-wrap::before {
            content: '';
            position: absolute; top: -100px; left: 50%;
            transform: translateX(-50%);
            width: 700px; height: 700px; border-radius: 50%;
            background: radial-gradient(circle, rgba(245,204,0,.05), transparent 70%);
            pointer-events: none;
        }
        .hiw-eyebrow {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .12em; color: #f5cc00;
            background: rgba(245,204,0,.1); border: 1px solid rgba(245,204,0,.25);
            border-radius: 20px; padding: 4px 14px; margin-bottom: 16px;
        }
        .hiw-heading {
            font-family: 'Sora', sans-serif; font-size: 32px; font-weight: 800;
            color: #fff; margin-bottom: 10px; line-height: 1.2;
        }
        .hiw-sub { font-size: 14px; color: #8890b8; margin-bottom: 48px; max-width: 460px; }

        /* Step cards */
        .hiw-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }

        .hiw-card {
            background: rgba(255,255,255,.04);
            border: 1.5px solid rgba(255,255,255,.09);
            border-radius: 20px;
            padding: 28px 24px 26px;
            position: relative;
            transition: border-color .22s, transform .22s, box-shadow .22s;
        }
        .hiw-card:hover {
            border-color: rgba(245,204,0,.35);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,.25);
        }

        /* Step number badge */
        .hiw-step-num {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(245,204,0,.12); border: 1.5px solid rgba(245,204,0,.35);
            color: #f5cc00; font-family: 'Sora', sans-serif;
            font-size: 13px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 18px;
        }

        /* Icon circle */
        .hiw-icon {
            width: 52px; height: 52px; border-radius: 14px;
            background: rgba(245,204,0,.08); border: 1.5px solid rgba(245,204,0,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 18px;
        }

        .hiw-card-title {
            font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 800;
            color: #fff; margin-bottom: 8px;
        }
        .hiw-card-desc { font-size: 13px; color: #8890b8; line-height: 1.65; margin: 0; }

        /* Connector line between cards (desktop only) */
        @media (min-width: 768px) {
            .hiw-card::after {
                content: '→';
                position: absolute; right: -18px; top: 50%;
                transform: translateY(-50%);
                color: rgba(245,204,0,.3); font-size: 22px; font-weight: 300;
                z-index: 1;
            }
            .hiw-card:last-child::after { display: none; }
        }

        .hiw-cta-wrap {
            margin-top: 44px; text-align: center;
        }
        .hiw-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 28px; border-radius: 12px;
            background: linear-gradient(135deg, #f5cc00, #e6b800);
            color: #04005a; font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: 14px; text-decoration: none;
            box-shadow: 0 4px 20px rgba(245,204,0,.3);
            transition: opacity .18s, transform .1s;
        }
        .hiw-cta:hover { opacity: .9; transform: translateY(-2px); color: #04005a; }

        /* ── FOOTER ── */
        .kbc-footer { background: #02001e; border-top: 2px solid rgba(245,204,0,.15); padding: 56px 0 0; font-family: 'DM Sans', sans-serif; color: #b0b8d8; }
        .kbc-footer-grid { display: grid; grid-template-columns: 2fr 1fr 1.4fr; gap: 40px; max-width: 1100px; margin: 0 auto; padding: 0 32px 48px; }
        @media (max-width: 900px) { .kbc-footer-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 540px) { .kbc-footer-grid { grid-template-columns: 1fr; padding: 0 20px 40px; } }
        .footer-brand-logo { width: 52px; height: 52px; border-radius: 50%; overflow: hidden; margin-bottom: 14px; }
        .footer-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .footer-brand-name { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 800; color: #fff; margin-bottom: 2px; }
        .footer-brand-place { font-size: 12px; color: #f5cc00; font-weight: 600; margin-bottom: 14px; }
        .footer-brand-desc { font-size: 13px; color: #8890b8; line-height: 1.7; margin-bottom: 20px; }
        .footer-socials { display: flex; gap: 10px; }
        .footer-social-btn { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: #b0b8d8; text-decoration: none; transition: background .18s, border-color .18s, color .18s; }
        .footer-social-btn:hover { background: rgba(245,204,0,.12); border-color: rgba(245,204,0,.3); color: #f5cc00; }
        .footer-social-btn svg { width: 18px; height: 18px; }
        .footer-col-title { font-family: 'Sora', sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #f5cc00; margin-bottom: 16px; }
        .footer-links { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
        .footer-links a { font-size: 13.5px; color: #8890b8; text-decoration: none; transition: color .18s; display: flex; align-items: center; gap: 7px; }
        .footer-links a:hover { color: #fff; }
        .footer-links a svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .5; }
        .footer-contact-item { display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #8890b8; margin-bottom: 12px; line-height: 1.5; }
        .footer-contact-item svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; color: #f5cc00; }
        .footer-contact-item a { color: #8890b8; text-decoration: none; transition: color .18s; }
        .footer-contact-item a:hover { color: #f5cc00; }
        .kbc-footer-bar { border-top: 1px solid rgba(255,255,255,.07); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; max-width: 100%; background: #01000f; }
        .kbc-footer-bar p { font-size: 12px; color: #5c6080; margin: 0; }
        .kbc-footer-bar a { color: #5c6080; text-decoration: none; }
        .kbc-footer-bar a:hover { color: #f5cc00; }
        .footer-bar-links { display: flex; gap: 20px; }
    </style>
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
        <li><a href="#about" class="active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a></li>
        <li><a href="announcement.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Announcements
        </a></li>
        <li><a href="project.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="15" height="15"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Programs
        </a></li>
        <li><div class="kbc-divider"></div></li>
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="#about-section">About</a></li>
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
    <a href="resident.php">🏠 Home</a>
    <a href="announcement.php">🔔 Announcements</a>
    <a href="project.php">📋 Programs</a>
    <a href="#how-it-works">❓ How It Works</a>
    <a href="#about-section">ℹ️ About</a>
    <a href="#contact">📞 Contact</a>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
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

<!-- ══ HOW IT WORKS — CARD SECTION ══ -->
<section class="hiw-wrap" id="how-it-works">
    <div class="container" style="position:relative;z-index:1;">
        <div class="hiw-eyebrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            Step-by-Step Guide
        </div>
        <h2 class="hiw-heading">How It Works</h2>
        <p class="hiw-sub">Get started in minutes. Here's how Ka-Barangay Connect helps you engage with your barangay.</p>

        <div class="hiw-cards">

            <!-- Step 1 -->
            <div class="hiw-card">
                <div class="hiw-step-num">1</div>
                <div class="hiw-icon">📝</div>
                <div class="hiw-card-title">Register</div>
                <p class="hiw-card-desc">Create your free resident account in seconds. All you need is your full name, purok, and a username to get started.</p>
            </div>

            <!-- Step 2 -->
            <div class="hiw-card">
                <div class="hiw-step-num">2</div>
                <div class="hiw-icon">📢</div>
                <div class="hiw-card-title">Report</div>
                <p class="hiw-card-desc">Submit a community concern with a title, category, description, and an optional photo. Your voice reaches the barangay directly.</p>
            </div>

            <!-- Step 3 -->
            <div class="hiw-card">
                <div class="hiw-step-num">3</div>
                <div class="hiw-icon">🔍</div>
                <div class="hiw-card-title">Track</div>
                <p class="hiw-card-desc">Follow the real-time status of your report — from <strong style="color:#f5cc00;">Pending</strong> to <strong style="color:#3b7ef8;">In Progress</strong> to <strong style="color:#22cc77;">Resolved</strong>.</p>
            </div>

            <!-- Step 4 -->
            <div class="hiw-card">
                <div class="hiw-step-num">4</div>
                <div class="hiw-icon">📡</div>
                <div class="hiw-card-title">Stay Informed</div>
                <p class="hiw-card-desc">Check announcements and programs posted by the barangay. Leave feedback on your own reports and engage with the community.</p>
            </div>

        </div>

        <div class="hiw-cta-wrap">
            <a href="resident_login.php?redirect=report.php" class="hiw-cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Get Started — Submit a Report
            </a>
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
            <div style="margin-top:20px; padding:14px 16px; background:rgba(245,204,0,.07); border:1px solid rgba(245,204,0,.18); border-radius:12px;">
                <p style="font-size:11.5px; color:#f5cc00; font-weight:700; margin:0 0 6px; text-transform:uppercase; letter-spacing:.07em;">Office Hours</p>
                <p style="font-size:12.5px; color:#8890b8; margin:0; line-height:1.65;">Monday – Friday<br>8:00 AM – 5:00 PM<br><span style="color:#5c6080; font-size:11.5px;">Closed on weekends &amp; holidays</span></p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
document.getElementById('hamburgerBtn').addEventListener('click', function () {
    document.getElementById('mobileMenu').classList.toggle('open');
});
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
            document.getElementById('mobileMenu').classList.remove('open');
        }
    });
});
const sections = document.querySelectorAll('[id]');
const navLinks = document.querySelectorAll('.kbc-links a');
window.addEventListener('scroll', () => {
    let cur = '';
    sections.forEach(s => { if (window.scrollY >= s.offsetTop - 80) cur = s.id; });
    navLinks.forEach(a => {
        a.classList.remove('active');
        if (a.getAttribute('href') === '#' + cur) a.classList.add('active');
    });
});
</script>
</body>
</html>