<?php
// admin/includes/admin_navbar.php

$_current_page = $current_page ?? basename($_SERVER['PHP_SELF'], '.php');
$_admin_name   = htmlspecialchars($_SESSION['admin_full_name'] ?? $_SESSION['admin_user'] ?? 'Admin');
$_admin_pos    = htmlspecialchars($_SESSION['admin_position']  ?? 'Administrator');

$_nav_items = [
    'admin_dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-tachometer',  'href' => 'admin_dashboard.php'],
    'admin_report'    => ['label' => 'Reports',   'icon' => 'fa-flag',        'href' => 'admin_report.php'],
    'admin_program'   => ['label' => 'Programs',  'icon' => 'fa-briefcase',   'href' => 'admin_program.php'],
];

// ── Notification query — only reports from the last 10 minutes ──
$_notif_count = 0;
$_notif_items = [];

$_notif_count_res = isset($conn)
    ? @mysqli_query($conn, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending' AND created_at >= NOW() - INTERVAL 10 MINUTE")
    : false;
if ($_notif_count_res) {
    $_notif_count = (int) mysqli_fetch_assoc($_notif_count_res)['total'];
}

$_notif_res = isset($conn)
    ? @mysqli_query($conn, "SELECT id, reporter, title, created_at FROM reports WHERE status = 'pending' AND created_at >= NOW() - INTERVAL 10 MINUTE ORDER BY created_at DESC LIMIT 5")
    : false;
if ($_notif_res) {
    while ($r = mysqli_fetch_assoc($_notif_res)) {
        $_notif_items[] = $r;
    }
}

function _notif_time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<style>
/* ════════════════════════════════════════════════════════
   ADMIN NAVBAR — Light Mode
   ════════════════════════════════════════════════════════ */
.admin-navbar {
    position: sticky; top: 0; z-index: 300;
    height: 62px;
    background: #fff;
    border-bottom: 2px solid #e8d5c4;
    box-shadow: 0 2px 16px rgba(92,10,10,.08);
    display: flex; align-items: center;
    padding: 0 20px; gap: 0;
    transition: background .2s, border-color .2s, box-shadow .2s;
}

/* Brand */
.an-brand {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0; margin-right: 20px;
}
.an-brand-logo { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.an-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
.an-brand-name { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 800; color: #2a1208; white-space: nowrap; }
.an-brand-sub  { font-size: 10px; color: #9e7f70; white-space: nowrap; }

/* Nav links */
.an-links { display: flex; align-items: center; gap: 2px; list-style: none; padding: 0; margin: 0; flex: 1; }
.an-links a {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #9e7f70;
    text-decoration: none; transition: background .16s, color .16s; white-space: nowrap;
}
.an-links a:hover  { background: #fdf5f0; color: #2a1208; }
.an-links a.active {
    background: #9b1f1f; color: #fff; font-weight: 700;
    box-shadow: 0 2px 10px rgba(155,31,31,.25);
}
.an-links a i { font-size: 13px; }

.an-divider { width: 1px; height: 22px; background: #e8d5c4; margin: 0 10px; flex-shrink: 0; }
.an-right { margin-left: auto; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* ── Bell ── */
.an-bell-wrap { position: relative; }
.an-bell-btn {
    width: 38px; height: 38px; border-radius: 9px;
    background: #fdf5f0; border: 1.5px solid #e8d5c4;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .16s, border-color .16s;
    position: relative;
}
.an-bell-btn:hover { background: #fdeaea; border-color: #c49a45; }
.an-bell-btn i { font-size: 15px; color: #7a5040; }

.an-bell-badge {
    position: absolute; top: -5px; right: -5px;
    min-width: 18px; height: 18px;
    background: #c0001a; color: #fff;
    font-size: 10px; font-weight: 700;
    border-radius: 99px; padding: 0 5px;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff; line-height: 1;
    animation: an-badge-pulse 2s ease-in-out infinite;
}
.an-bell-badge.hidden { display: none; animation: none; }

@keyframes an-badge-pulse {
    0%,100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(192,0,26,.4); }
    50%      { transform: scale(1.15); box-shadow: 0 0 0 5px rgba(192,0,26,.0); }
}

/* ── Notification Dropdown ── */
.an-notif-dropdown {
    display: none;
    position: absolute; top: calc(100% + 10px); right: 0;
    width: 320px;
    background: #fff;
    border: 1.5px solid #e8d5c4;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(92,10,10,.15);
    overflow: hidden;
    z-index: 400;
}
.an-notif-dropdown.open { display: block; }

.an-notif-head {
    padding: 13px 16px;
    border-bottom: 1px solid #e8d5c4;
    display: flex; align-items: center; justify-content: space-between;
}
.an-notif-head-title { font-size: 13.5px; font-weight: 700; color: #2a1208; }
.an-notif-head-sub   { font-size: 11px; color: #9e7f70; }
.an-notif-head-badge {
    font-size: 11px; font-weight: 700;
    background: #fff0f0; color: #c0001a;
    border: 1px solid #fca5a5;
    padding: 2px 9px; border-radius: 99px;
}

.an-notif-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 16px;
    border-bottom: 1px solid #fdf5f0;
    text-decoration: none;
    transition: background .14s; cursor: pointer;
}
.an-notif-item:last-of-type { border-bottom: none; }
.an-notif-item:hover { background: #fdf5f0; }

.an-notif-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #c0001a; flex-shrink: 0; margin-top: 5px;
}
.an-notif-title {
    font-size: 13px; font-weight: 600; color: #2a1208;
    line-height: 1.4; margin: 0 0 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 230px;
}
.an-notif-meta { font-size: 11.5px; color: #9e7f70; margin: 0; }

.an-notif-empty {
    padding: 28px 16px; text-align: center;
    font-size: 13px; color: #9e7f70;
}
.an-notif-empty i { font-size: 24px; display: block; margin-bottom: 8px; color: #d4a96a; }

.an-notif-footer {
    padding: 10px 16px; border-top: 1px solid #e8d5c4; text-align: center;
}
.an-notif-footer a {
    font-size: 12.5px; font-weight: 700; color: #9b1f1f;
    text-decoration: none; transition: color .14s;
}
.an-notif-footer a:hover { color: #5c0a0a; }

/* ── User pill ── */
.an-user {
    display: flex; align-items: center; gap: 9px;
    padding: 6px 12px; border-radius: 10px;
    border: 1.5px solid #e8d5c4; background: #fdf5f0; cursor: default;
}
.an-user-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #9b1f1f, #5c0a0a);
    color: #f0e0d0; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    border: 1.5px solid rgba(212,169,106,.4);
}
.an-user-name { font-size: 12.5px; font-weight: 700; color: #2a1208; white-space: nowrap; }
.an-user-pos  { font-size: 10.5px; color: #9e7f70; white-space: nowrap; }

.an-logout-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 9px;
    background: #fff; border: 1.5px solid #e8d5c4;
    font-size: 12.5px; font-weight: 700; color: #c0001a;
    cursor: pointer; text-decoration: none;
    transition: background .16s, border-color .16s; white-space: nowrap;
}
.an-logout-btn:hover { background: #fff0f0; border-color: #f7a0aa; color: #9b1f1f; }
.an-logout-btn i { font-size: 12px; }

/* ── Hamburger ── */
.an-hamburger {
    display: none; margin-left: 10px;
    background: none; border: 1.5px solid #e8d5c4;
    border-radius: 8px; padding: 7px 9px;
    flex-direction: column; align-items: center;
    justify-content: center; gap: 4px; cursor: pointer;
}
.an-hamburger span { display: block; width: 18px; height: 2px; background: #7a5040; border-radius: 2px; transition: all .2s; }

/* ── Mobile menu ── */
.an-mobile-menu {
    display: none; flex-direction: column;
    background: #fff; border-bottom: 1.5px solid #e8d5c4;
    padding: 10px 16px 14px;
    position: sticky; top: 62px; z-index: 299;
    box-shadow: 0 4px 16px rgba(92,10,10,.06);
}
.an-mobile-menu.open { display: flex; }
.an-mobile-menu a {
    display: flex; align-items: center; gap: 9px;
    padding: 10px 12px; border-radius: 8px;
    font-size: 13.5px; font-weight: 600; color: #7a5040;
    text-decoration: none; transition: background .16s, color .16s;
}
.an-mobile-menu a:hover  { background: #fdf5f0; color: #2a1208; }
.an-mobile-menu a.active { background: #9b1f1f; color: #fff; }
.an-mobile-menu .an-mobile-logout { margin-top: 6px; border-top: 1px solid #e8d5c4; padding-top: 12px; color: #c0001a; }
.an-mobile-bell-row {
    display: flex; align-items: center; gap: 9px;
    padding: 10px 12px; border-radius: 8px;
    font-size: 13.5px; font-weight: 600; color: #7a5040;
    cursor: pointer; transition: background .16s, color .16s;
}
.an-mobile-bell-row:hover { background: #fdf5f0; color: #2a1208; }
.an-mobile-bell-count {
    margin-left: auto; font-size: 11px; font-weight: 700;
    background: #c0001a; color: #fff;
    padding: 2px 8px; border-radius: 99px;
}

/* ════════════════════════════════════════════════════════
   ADMIN NAVBAR — Dark Mode
   ════════════════════════════════════════════════════════ */
body.dark-mode .admin-navbar {
    background: #1a0505;
    border-bottom-color: #4a1515;
    box-shadow: 0 2px 20px rgba(0,0,0,.5);
}

body.dark-mode .an-brand-name { color: #f0ddd8; }
body.dark-mode .an-brand-sub  { color: #9a6060; }

body.dark-mode .an-links a         { color: #a07060; }
body.dark-mode .an-links a:hover   { background: #2a0808; color: #f0ddd8; }
body.dark-mode .an-links a.active  {
    background: #9b1f1f;
    color: #fff;
    box-shadow: 0 2px 14px rgba(155,31,31,.5);
}

body.dark-mode .an-divider { background: #3a1010; }

/* Bell - dark */
body.dark-mode .an-bell-btn {
    background: #220808;
    border-color: #4a1515;
}
body.dark-mode .an-bell-btn:hover {
    background: #2e0a0a;
    border-color: #d4a96a;
}
body.dark-mode .an-bell-btn i { color: #d4a96a; }
body.dark-mode .an-bell-badge { border-color: #1a0505; }

/* Notification dropdown - dark */
body.dark-mode .an-notif-dropdown {
    background: #1a0505;
    border-color: #4a1515;
    box-shadow: 0 8px 40px rgba(0,0,0,.6);
}
body.dark-mode .an-notif-head {
    border-bottom-color: #3a1010;
    background: #1e0606;
}
body.dark-mode .an-notif-head-title { color: #f0ddd8; }
body.dark-mode .an-notif-head-sub   { color: #9a6060; }
body.dark-mode .an-notif-head-badge {
    background: rgba(192,0,26,.18);
    color: #ff8090;
    border-color: rgba(192,0,26,.35);
}
body.dark-mode .an-notif-item {
    border-bottom-color: #2a0808;
}
body.dark-mode .an-notif-item:hover { background: #220808; }
body.dark-mode .an-notif-title  { color: #f0ddd8; }
body.dark-mode .an-notif-meta   { color: #9a6060; }
body.dark-mode .an-notif-empty  { color: #9a6060; }
body.dark-mode .an-notif-empty i { color: #5a2020; }
body.dark-mode .an-notif-footer { border-top-color: #3a1010; background: #1e0606; }
body.dark-mode .an-notif-footer a       { color: #e07070; }
body.dark-mode .an-notif-footer a:hover { color: #ff9090; }

/* User pill - dark */
body.dark-mode .an-user {
    background: #220808;
    border-color: #4a1515;
}
body.dark-mode .an-user-avatar {
    background: linear-gradient(135deg, #9b1f1f, #5c0a0a);
    border-color: rgba(212,169,106,.5);
    color: #f0ddd8;
}
body.dark-mode .an-user-name { color: #f0ddd8; }
body.dark-mode .an-user-pos  { color: #9a6060; }

/* Logout - dark */
body.dark-mode .an-logout-btn {
    background: #220808;
    border-color: #4a1515;
    color: #ff8080;
}
body.dark-mode .an-logout-btn:hover {
    background: #2e0a0a;
    border-color: #c0001a;
    color: #ff6060;
}

/* Hamburger - dark */
body.dark-mode .an-hamburger { border-color: #4a1515; }
body.dark-mode .an-hamburger span { background: #d4a96a; }

/* Mobile menu - dark */
body.dark-mode .an-mobile-menu {
    background: #1a0505;
    border-bottom-color: #4a1515;
    box-shadow: 0 4px 20px rgba(0,0,0,.4);
}
body.dark-mode .an-mobile-menu a          { color: #b08070; }
body.dark-mode .an-mobile-menu a:hover    { background: #2a0808; color: #f0ddd8; }
body.dark-mode .an-mobile-menu a.active   { background: #9b1f1f; color: #fff; }
body.dark-mode .an-mobile-menu .an-mobile-logout { border-top-color: #3a1010; color: #ff8080; }
body.dark-mode .an-mobile-bell-row        { color: #b08070; }
body.dark-mode .an-mobile-bell-row:hover  { background: #2a0808; color: #f0ddd8; }

/* ── Responsive ── */
@media (max-width: 840px) {
    .an-links, .an-divider, .an-user, .an-logout-btn { display: none; }
    .an-hamburger { display: flex; }
}
@media (max-width: 480px) { .admin-navbar { padding: 0 14px; } }
</style>

<nav class="admin-navbar">
    <a href="admin_dashboard.php" class="an-brand">
        <div class="an-brand-logo">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="an-brand-name">Ka-Barangay</div>
            <div class="an-brand-sub">Admin Panel</div>
        </div>
    </a>

    <ul class="an-links">
        <?php foreach ($_nav_items as $key => $item): ?>
        <li>
            <a href="<?= $item['href'] ?>"
               class="<?= $_current_page === $key ? 'active' : '' ?>">
                <i class="fa <?= $item['icon'] ?>"></i>
                <?= $item['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="an-divider"></div>

    <div class="an-right">

        <!-- ── BELL ── -->
        <div class="an-bell-wrap" id="anBellWrap">
            <button class="an-bell-btn" id="anBellBtn" aria-label="Notifications">
                <i class="fa fa-bell"></i>
                <span class="an-bell-badge <?= $_notif_count === 0 ? 'hidden' : '' ?>" id="anBellBadge">
                    <?= $_notif_count > 9 ? '9+' : $_notif_count ?>
                </span>
            </button>

            <div class="an-notif-dropdown" id="anNotifDropdown">
                <div class="an-notif-head">
                    <div>
                        <div class="an-notif-head-title">🔔 New Reports</div>
                        <div class="an-notif-head-sub">Last 10 minutes</div>
                    </div>
                    <?php if ($_notif_count > 0): ?>
                    <span class="an-notif-head-badge"><?= $_notif_count ?> new</span>
                    <?php endif; ?>
                </div>

                <?php if (empty($_notif_items)): ?>
                <div class="an-notif-empty">
                    <i class="fa fa-check-circle"></i>
                    No new reports in the last 10 minutes
                </div>
                <?php else: ?>
                    <?php foreach ($_notif_items as $n): ?>
                    <a class="an-notif-item" href="admin_report.php?id=<?= (int)$n['id'] ?>">
                        <span class="an-notif-dot"></span>
                        <div style="min-width:0; flex:1;">
                            <p class="an-notif-title"><?= htmlspecialchars($n['title']) ?></p>
                            <p class="an-notif-meta">
                                <i class="fa fa-user" style="font-size:10px;"></i>
                                <?= htmlspecialchars($n['reporter']) ?>
                                &middot;
                                <i class="fa fa-clock-o" style="font-size:10px;"></i>
                                <?= _notif_time_ago($n['created_at']) ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="an-notif-footer">
                    <a href="admin_report.php">View all reports &rarr;</a>
                </div>
            </div>
        </div>

        <!-- ── USER ── -->
        <div class="an-user">
            <div class="an-user-avatar">
                <?= strtoupper(substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <div class="an-user-name"><?= $_admin_name ?></div>
                <div class="an-user-pos"><?= $_admin_pos ?></div>
            </div>
        </div>

        <a href="#" class="an-logout-btn" onclick="return confirmLogout()">
            <i class="fa fa-sign-out"></i> Logout
        </a>
    </div>

    <button class="an-hamburger" id="anHamburgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile menu -->
<div class="an-mobile-menu" id="anMobileMenu">
    <?php foreach ($_nav_items as $key => $item): ?>
    <a href="<?= $item['href'] ?>" class="<?= $_current_page === $key ? 'active' : '' ?>">
        <i class="fa <?= $item['icon'] ?>"></i>
        <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>

    <a href="admin_report.php" class="an-mobile-bell-row">
        <i class="fa fa-bell"></i> New Reports
        <?php if ($_notif_count > 0): ?>
        <span class="an-mobile-bell-count"><?= $_notif_count > 9 ? '9+' : $_notif_count ?></span>
        <?php endif; ?>
    </a>

    <a href="#" class="an-mobile-logout" onclick="return confirmLogout()">
        <i class="fa fa-sign-out"></i> Logout
    </a>
</div>

<script>
(function () {
    /* Hamburger */
    var hamburger   = document.getElementById('anHamburgerBtn');
    var mobileMenu  = document.getElementById('anMobileMenu');
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', function () {
            mobileMenu.classList.toggle('open');
        });
    }

    /* Bell dropdown toggle */
    var bellBtn      = document.getElementById('anBellBtn');
    var bellDropdown = document.getElementById('anNotifDropdown');
    var bellWrap     = document.getElementById('anBellWrap');
    if (bellBtn && bellDropdown) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            bellDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!bellWrap.contains(e.target)) {
                bellDropdown.classList.remove('open');
            }
        });
    }
})();
</script>