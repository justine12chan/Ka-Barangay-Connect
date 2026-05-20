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
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
    <style>
        body.page-resident { background: #3d0606; overflow: visible; height: auto; }
        html.page-resident-html { height: auto; }

        /* ── NAVBAR ── */
        .kbc-nav {
            position: sticky; top: 0; z-index: 200;
            display: flex; align-items: center;
            height: 64px; padding: 0 32px;
            background: rgba(92,10,10,.96);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(212,169,106,.15);
            box-shadow: 0 2px 24px rgba(0,0,0,.35);
        }
        .kbc-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; margin-right: 28px; }
        .kbc-brand-logo { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .kbc-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .kbc-brand-name { font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 800; color: #fff; line-height: 1.1; display: block; }
        .kbc-brand-sub  { font-size: 10px; color: #d4a96a; font-weight: 600; display: block; }
        .kbc-links { display: flex; align-items: center; gap: 2px; list-style: none; padding: 0; margin: 0; }
        .kbc-links a { display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #d4b8a8; text-decoration: none; transition: background .18s, color .18s; white-space: nowrap; }
        .kbc-links a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .kbc-links a.active { color: #d4a96a; background: rgba(212,169,106,.1); }
        .kbc-links .kbc-divider { width: 1px; height: 20px; background: rgba(255,255,255,.12); margin: 0 6px; }
        .kbc-cta { margin-left: auto; display: flex; align-items: center; gap: 7px; padding: 9px 20px; border-radius: 10px; background: linear-gradient(135deg, #d4a96a, #c49a45); color: #5c0a0a; font-weight: 800; font-size: 13px; text-decoration: none; white-space: nowrap; box-shadow: 0 3px 14px rgba(212,169,106,.3); transition: opacity .18s, transform .1s; font-family: 'Cormorant Garamond', serif; }
        .kbc-cta:hover { opacity: .9; transform: translateY(-1px); color: #5c0a0a; }
        .kbc-hamburger { display: none; margin-left: auto; background: none; border: 1px solid rgba(255,255,255,.18); border-radius: 8px; padding: 7px 9px; flex-direction: column; align-items: center; justify-content: center; gap: 4px; cursor: pointer; }
        .kbc-hamburger span { display: block; width: 20px; height: 2px; background: #fff; border-radius: 2px; transition: all .22s; }
        .kbc-mobile-menu { display: none; flex-direction: column; gap: 4px; background: rgba(92,10,10,.99); border-bottom: 1px solid rgba(212,169,106,.12); padding: 10px 16px 14px; position: sticky; top: 64px; z-index: 199; }
        .kbc-mobile-menu.open { display: flex; }
        .kbc-mobile-menu a { padding: 10px 14px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #d4b8a8; text-decoration: none; transition: background .18s, color .18s; }
        .kbc-mobile-menu a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .kbc-mobile-menu .kbc-cta { margin: 8px 0 0; justify-content: center; margin-left: 0; }
        @media (max-width: 820px) { .kbc-links, .kbc-cta { display: none; } .kbc-hamburger { display: flex; } }
        @media (max-width: 480px) { .kbc-nav { padding: 0 16px; } }

        /* ── HERO ── */
        .hero-wrap { background: linear-gradient(160deg, #6b0f0f 0%, #5c0a0a 55%, #2a0303 100%); min-height: 100vh; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; padding: 48px 0 60px; }
        .hero-wrap::before { content: ''; position: absolute; top: -120px; right: -120px; width: 500px; height: 500px; border-radius: 50%; background: radial-gradient(circle, rgba(212,169,106,.08), transparent 70%); pointer-events: none; }
        .hero-wrap::after  { content: ''; position: absolute; bottom: -80px; left: -80px; width: 350px; height: 350px; border-radius: 50%; background: radial-gradient(circle, rgba(155,31,31,.35), transparent 70%); pointer-events: none; }

        /* ── COMMUNITY BOARD ── */
        .board-wrap { background: #faf0e8; padding: 56px 0 64px; }
        .board-wrap .section-eyebrow { background: #9b1f1f; }

        /* Community Board — larger fonts */
        .panel-top-title      { font-size: 17px !important; font-weight: 700 !important; }
        .panel-top-sub        { font-size: 13px !important; }
        .panel-preview-title  { font-size: 16px !important; font-weight: 700 !important; line-height: 1.4 !important; }
        .panel-preview-desc   { font-size: 14px !important; line-height: 1.7 !important; }
        .panel-preview-date   { font-size: 13px !important; }
        .panel-preview-badge  { font-size: 12px !important; }
        .panel-preview-empty  { font-size: 14px !important; }
        .panel-count          { font-size: 14px !important; }
        .panel-cta            { font-size: 14px !important; font-weight: 700 !important; }

        /* ── HOW IT WORKS ── */
        .hiw-wrap {
            background: linear-gradient(160deg, #fdf5ee 0%, #f5e8d8 60%, #efe0ca 100%);
            padding: 80px 0 88px;
            border-top: 2px solid rgba(155,31,31,.09);
            border-bottom: 2px solid rgba(155,31,31,.09);
            position: relative;
            overflow: hidden;
        }
        .hiw-wrap::before {
            content: '';
            position: absolute; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
            pointer-events: none; opacity: .5;
        }
        .hiw-header {
            display: flex; align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 64px; gap: 24px; flex-wrap: wrap;
        }
        .hiw-eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 10px; font-weight: 700; letter-spacing: .15em;
            text-transform: uppercase; color: #9b1f1f;
            background: rgba(155,31,31,.08); border: 1px solid rgba(155,31,31,.2);
            border-radius: 4px; padding: 4px 12px; margin-bottom: 14px;
        }
        .hiw-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 5vw, 52px); font-weight: 800;
            color: #2e0404; line-height: 1.08; margin: 0;
            letter-spacing: -.02em;
        }
        .hiw-heading em { font-style: italic; color: #9b1f1f; }
        .hiw-sub { font-size: 14px; color: #9a7060; line-height: 1.75; max-width: 300px; margin-top: 8px; margin-bottom: 0; }
        .hiw-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 26px; border-radius: 8px;
            background: linear-gradient(135deg, #9b1f1f, #6b0f0f);
            color: #fff; font-size: 13px; font-weight: 600; text-decoration: none;
            box-shadow: 0 4px 24px rgba(107,15,15,.3);
            transition: transform .18s, box-shadow .18s;
            white-space: nowrap; flex-shrink: 0;
        }
        .hiw-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(107,15,15,.38); color: #fff; }

        /* Steps grid */
        .hiw-steps {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 0; position: relative;
        }
        .hiw-steps::before {
            content: '';
            position: absolute;
            top: 28px;
            left: calc(12.5% + 14px);
            right: calc(12.5% + 14px);
            height: 1px;
            background: linear-gradient(to right,
                rgba(155,31,31,.0) 0%,
                rgba(155,31,31,.18) 8%,
                rgba(155,31,31,.18) 92%,
                rgba(155,31,31,.0) 100%);
        }
        .hiw-step {
            padding: 0 16px; position: relative;
            display: flex; flex-direction: column; align-items: center; text-align: center;
        }
        .hiw-step:first-child { padding-left: 0; }
        .hiw-step:last-child { padding-right: 0; }

        .hiw-step-top { display: flex; justify-content: center; margin-bottom: 20px; }
        .hiw-step-dot {
            width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
            background: #fff; border: 1.5px solid rgba(155,31,31,.2);
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 1;
            box-shadow: 0 2px 12px rgba(155,31,31,.1);
        }
        .hiw-step-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px; font-weight: 800; color: #9b1f1f; line-height: 1;
        }
        .hiw-step-ghost {
            font-family: 'Cormorant Garamond', serif;
            font-size: 100px; font-weight: 800; line-height: .85;
            color: rgba(155,31,31,.05);
            position: absolute; top: -6px; left: 50%; transform: translateX(-50%);
            pointer-events: none; user-select: none;
            letter-spacing: -.04em;
        }
        .hiw-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(155,31,31,.07); border: 1px solid rgba(155,31,31,.14);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px; color: #9b1f1f;
            transition: background .22s, border-color .22s;
        }
        .hiw-step:hover .hiw-icon { background: rgba(155,31,31,.13); border-color: rgba(155,31,31,.28); }
        .hiw-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 21px; font-weight: 800; color: #2e0404;
            margin: 0 0 10px; letter-spacing: -.01em; line-height: 1.1;
        }
        .hiw-card-desc { font-size: 13px; color: #9a7060; line-height: 1.75; margin: 0; }

        /* Status chips */
        .hiw-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 12px; }
        .hiw-chip { font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; letter-spacing: .03em; }
        .hiw-chip-pending  { background: #fdf3e3; color: #8a5e22; border: 1px solid rgba(212,169,106,.3); }
        .hiw-chip-progress { background: #eef4fd; color: #185fa5; border: 1px solid rgba(56,138,221,.25); }
        .hiw-chip-resolved { background: #e8f8ef; color: #0f6e56; border: 1px solid rgba(29,158,117,.25); }

        .hiw-step-rule {
            height: 2px; margin-top: 28px;
            background: linear-gradient(to right, rgba(155,31,31,.18), rgba(155,31,31,.04));
            border-radius: 2px;
        }
        .hiw-note {
            margin-top: 56px; display: flex; flex-direction: column; align-items: center;
            gap: 10px; padding: 24px 28px; border-radius: 12px;
            background: rgba(255,255,255,.6); border: 1px solid rgba(155,31,31,.12);
            max-width: 480px; margin-left: auto; margin-right: auto; text-align: center;
        }
        .hiw-note svg { width: 28px; height: 28px; flex-shrink: 0; color: #9b1f1f; opacity: .7; }
        .hiw-note p { font-size: 12.5px; color: #9a7060; line-height: 1.65; margin: 0; }
        .hiw-note strong { color: #6b1010; font-weight: 600; }

        /* Dark mode */
        html[data-theme="dark"] .hiw-wrap { background: #1a0808 !important; border-color: rgba(212,169,106,.08) !important; }
        html[data-theme="dark"] .hiw-eyebrow { background: rgba(212,169,106,.1) !important; border-color: rgba(212,169,106,.28) !important; color: #d4a96a !important; }
        html[data-theme="dark"] .hiw-heading { color: #f0e8df !important; }
        html[data-theme="dark"] .hiw-heading em { color: #d4a96a !important; }
        html[data-theme="dark"] .hiw-sub { color: #b09080 !important; }
        html[data-theme="dark"] .hiw-step-dot { background: #2a1010 !important; border-color: rgba(212,169,106,.25) !important; box-shadow: none !important; }
        html[data-theme="dark"] .hiw-step-num { color: #d4a96a !important; }
        html[data-theme="dark"] .hiw-step-ghost { color: rgba(212,169,106,.06) !important; }
        html[data-theme="dark"] .hiw-steps::before { background: linear-gradient(to right, rgba(212,169,106,0) 0%, rgba(212,169,106,.15) 8%, rgba(212,169,106,.15) 92%, rgba(212,169,106,0) 100%) !important; }
        html[data-theme="dark"] .hiw-icon { background: rgba(212,169,106,.08) !important; border-color: rgba(212,169,106,.18) !important; color: #d4a96a !important; }
        html[data-theme="dark"] .hiw-step:hover .hiw-icon { background: rgba(212,169,106,.15) !important; border-color: rgba(212,169,106,.35) !important; }
        html[data-theme="dark"] .hiw-card-title { color: #f0e8df !important; }
        html[data-theme="dark"] .hiw-card-desc { color: #b09080 !important; }
        html[data-theme="dark"] .hiw-step-rule { background: linear-gradient(to right, rgba(212,169,106,.15), transparent) !important; }
        html[data-theme="dark"] .hiw-chip-pending  { background: #2e1a00 !important; color: #e8c070 !important; border-color: rgba(232,192,112,.25) !important; }
        html[data-theme="dark"] .hiw-chip-progress { background: #0a1a2e !important; color: #70b0f0 !important; border-color: rgba(112,176,240,.25) !important; }
        html[data-theme="dark"] .hiw-chip-resolved { background: #082a14 !important; color: #50e090 !important; border-color: rgba(80,224,144,.25) !important; }
        html[data-theme="dark"] .hiw-note { background: rgba(42,16,16,.6) !important; border-color: rgba(212,169,106,.15) !important; }
        html[data-theme="dark"] .hiw-note p { color: #b09080 !important; }
        html[data-theme="dark"] .hiw-note strong { color: #d4a96a !important; }

        /* ── Tablet: 2 columns ── */
        @media (max-width: 860px) {
            .hiw-steps { grid-template-columns: repeat(2, 1fr); gap: 2px; }
            .hiw-steps::before { display: none; }
            .hiw-step { padding: 28px 20px 28px; border-bottom: 1px solid rgba(155,31,31,.08); }
            .hiw-step:nth-child(odd) { border-right: 1px solid rgba(155,31,31,.08); }
            .hiw-step:nth-last-child(-n+2) { border-bottom: none; }
            .hiw-step:first-child { padding-left: 20px; }
            .hiw-step:last-child { padding-right: 20px; }
            .hiw-step-ghost { font-size: 80px; }
            .hiw-step-rule { display: none; }
            html[data-theme="dark"] .hiw-step { border-color: rgba(212,169,106,.08) !important; }
        }

        /* ── Phone: 1 column full cards ── */
        @media (max-width: 520px) {
            .hiw-wrap { padding: 48px 0 56px; }
            .hiw-header { flex-direction: column; align-items: flex-start; margin-bottom: 36px; gap: 16px; }
            .hiw-sub { max-width: 100%; }
            .hiw-cta { width: 100%; justify-content: center; }

            .hiw-steps { grid-template-columns: 1fr; gap: 12px; }
            .hiw-steps::before { display: none; }

            .hiw-step {
                padding: 24px 20px;
                background: rgba(255,255,255,.7);
                border: 1px solid rgba(155,31,31,.12);
                border-radius: 16px;
                border-bottom: 1px solid rgba(155,31,31,.12) !important;
                border-right: none !important;
            }
            .hiw-step:first-child { padding-left: 20px; }
            .hiw-step:last-child { padding-right: 20px; }
            .hiw-step-ghost { display: none; }
            .hiw-step-rule { display: none; }
            .hiw-step-top { margin-bottom: 16px; }
            .hiw-step-dot { width: 48px; height: 48px; }
            .hiw-step-num { font-size: 20px; }
            .hiw-icon { width: 40px; height: 40px; margin-bottom: 12px; }
            .hiw-card-title { font-size: 19px; }
            .hiw-card-desc { font-size: 13px; }
            .hiw-chips { justify-content: center; }

            .hiw-note { max-width: 100%; padding: 18px 16px; margin-top: 20px; }

            html[data-theme="dark"] .hiw-step {
                background: #1e0f0f !important;
                border-color: #3a2020 !important;
            }
        }

        @media (max-width: 380px) {
            .hiw-heading { font-size: 30px !important; }
        }

        /* ── FOOTER ── */
        .kbc-footer { background: #1a0303; border-top: 2px solid rgba(212,169,106,.15); padding: 56px 0 0; font-family: 'Lora', serif; color: #d4b8a8; }
        .kbc-footer-grid { display: grid; grid-template-columns: 2fr 1fr 1.4fr; gap: 40px; max-width: 1100px; margin: 0 auto; padding: 0 32px 48px; }
        @media (max-width: 900px) { .kbc-footer-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 540px) { .kbc-footer-grid { grid-template-columns: 1fr; padding: 0 20px 40px; } }
        .footer-brand-logo { width: 52px; height: 52px; border-radius: 50%; overflow: hidden; margin-bottom: 14px; }
        .footer-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .footer-brand-name { font-family: 'Cormorant Garamond', serif; font-size: 17px; font-weight: 800; color: #fff; margin-bottom: 2px; }
        .footer-brand-place { font-size: 12px; color: #d4a96a; font-weight: 600; margin-bottom: 14px; }
        .footer-brand-desc { font-size: 13px; color: #9e7f70; line-height: 1.7; margin-bottom: 20px; }
        .footer-socials { display: flex; gap: 10px; }
        .footer-social-btn { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: #d4b8a8; text-decoration: none; transition: background .18s, border-color .18s, color .18s; }
        .footer-social-btn:hover { background: rgba(212,169,106,.12); border-color: rgba(212,169,106,.3); color: #d4a96a; }
        .footer-social-btn svg { width: 18px; height: 18px; }
        .footer-col-title { font-family: 'Cormorant Garamond', serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #d4a96a; margin-bottom: 16px; }
        .footer-links { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
        .footer-links a { font-size: 13.5px; color: #9e7f70; text-decoration: none; transition: color .18s; display: flex; align-items: center; gap: 7px; }
        .footer-links a:hover { color: #fff; }
        .footer-links a svg { width: 14px; height: 14px; flex-shrink: 0; opacity: .5; }
        .footer-contact-item { display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #9e7f70; margin-bottom: 12px; line-height: 1.5; }
        .footer-contact-item svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; color: #d4a96a; }
        .footer-contact-item a { color: #9e7f70; text-decoration: none; transition: color .18s; }
        .footer-contact-item a:hover { color: #d4a96a; }
        .kbc-footer-bar { border-top: 1px solid rgba(255,255,255,.07); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; max-width: 100%; background: #0f0101; }
        .kbc-footer-bar p { font-size: 12px; color: #7a5040; margin: 0; }
        .kbc-footer-bar a { color: #7a5040; text-decoration: none; }
        .kbc-footer-bar a:hover { color: #d4a96a; }
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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