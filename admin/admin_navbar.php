<?php
// admin/includes/admin_navbar.php
// Shared top navbar for admin panel — replaces the sidebar + top-nav combo.
// Usage: include __DIR__ . '/includes/admin_navbar.php';  (from an admin/ subfolder page)
// Or:    include __DIR__ . '/../admin/includes/admin_navbar.php'; (from root)

$_current_page = $current_page ?? basename($_SERVER['PHP_SELF'], '.php');
$_admin_name   = htmlspecialchars($_SESSION['admin_full_name'] ?? $_SESSION['admin_user'] ?? 'Admin');
$_admin_pos    = htmlspecialchars($_SESSION['admin_position']  ?? 'Administrator');

$_nav_items = [
    'admin_dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-tachometer',  'href' => 'admin_dashboard.php'],
    'admin_report'    => ['label' => 'Reports',   'icon' => 'fa-flag',        'href' => 'admin_report.php'],
    'admin_program'   => ['label' => 'Programs',  'icon' => 'fa-briefcase',   'href' => 'admin_program.php'],
];
?>
<style>
/* ── Admin Navbar ── */
.admin-navbar {
    position: sticky; top: 0; z-index: 300;
    height: 62px;
    background: #fff;
    border-bottom: 1.5px solid #e8eaf0;
    box-shadow: 0 2px 16px rgba(26,28,46,.07);
    display: flex; align-items: center;
    padding: 0 20px; gap: 0;
}

.an-brand {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0; margin-right: 20px;
}
.an-brand-logo {
    width: 36px; height: 36px; border-radius: 50%; overflow: hidden;
    background: #f0f1ff; border: 1.5px solid rgba(26,86,219,.18);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.an-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
.an-brand-name {
    font-family: 'Sora', sans-serif; font-size: 14px;
    font-weight: 800; color: #1a1c2e; white-space: nowrap;
}
.an-brand-sub { font-size: 10px; color: #8890b8; white-space: nowrap; }

/* Nav links */
.an-links {
    display: flex; align-items: center; gap: 2px;
    list-style: none; padding: 0; margin: 0; flex: 1;
}
.an-links a {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 14px; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #8890b8;
    text-decoration: none;
    transition: background .16s, color .16s;
    white-space: nowrap;
}
.an-links a:hover { background: #f4f5fb; color: #1a1c2e; }
.an-links a.active {
    background: #e8f0fe; color: #1a56db;
    font-weight: 700;
}
.an-links a i { font-size: 13px; }

.an-divider { width: 1px; height: 22px; background: #e8eaf0; margin: 0 10px; flex-shrink: 0; }

/* Right side */
.an-right {
    margin-left: auto; display: flex; align-items: center; gap: 10px; flex-shrink: 0;
}

.an-user {
    display: flex; align-items: center; gap: 9px;
    padding: 6px 12px; border-radius: 10px;
    border: 1.5px solid #e8eaf0; background: #f8f9fc;
    cursor: default;
}
.an-user-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #1a56db, #0800a0);
    color: #fff; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.an-user-name { font-size: 12.5px; font-weight: 700; color: #1a1c2e; white-space: nowrap; }
.an-user-pos  { font-size: 10.5px; color: #8890b8; white-space: nowrap; }

.an-logout-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 9px;
    background: #fff; border: 1.5px solid #e8eaf0;
    font-size: 12.5px; font-weight: 700; color: #c0001a;
    cursor: pointer; text-decoration: none;
    transition: background .16s, border-color .16s;
    white-space: nowrap;
}
.an-logout-btn:hover { background: #fff0f0; border-color: #f7a0aa; color: #c0001a; }
.an-logout-btn i { font-size: 12px; }

/* Mobile hamburger */
.an-hamburger {
    display: none; margin-left: auto;
    background: none; border: 1.5px solid #e8eaf0;
    border-radius: 8px; padding: 7px 9px;
    flex-direction: column; align-items: center;
    justify-content: center; gap: 4px; cursor: pointer;
}
.an-hamburger span { display: block; width: 18px; height: 2px; background: #474960; border-radius: 2px; transition: all .2s; }

/* Mobile dropdown */
.an-mobile-menu {
    display: none; flex-direction: column;
    background: #fff; border-bottom: 1.5px solid #e8eaf0;
    padding: 10px 16px 14px;
    position: sticky; top: 62px; z-index: 299;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
}
.an-mobile-menu.open { display: flex; }
.an-mobile-menu a {
    display: flex; align-items: center; gap: 9px;
    padding: 10px 12px; border-radius: 8px;
    font-size: 13.5px; font-weight: 600; color: #474960;
    text-decoration: none; transition: background .16s, color .16s;
}
.an-mobile-menu a:hover  { background: #f4f5fb; color: #1a1c2e; }
.an-mobile-menu a.active { background: #e8f0fe; color: #1a56db; }
.an-mobile-menu .an-mobile-logout {
    margin-top: 6px;
    border-top: 1px solid #e8eaf0; padding-top: 12px;
    color: #c0001a;
}

@media (max-width: 840px) {
    .an-links, .an-divider, .an-user { display: none; }
    .an-logout-btn { display: none; }
    .an-hamburger { display: flex; margin-left: auto; }
}
@media (max-width: 480px) {
    .admin-navbar { padding: 0 14px; }
}
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
    <a href="#" class="an-mobile-logout" onclick="return confirmLogout()">
        <i class="fa fa-sign-out"></i> Logout
    </a>
</div>

<script>
(function () {
    const btn  = document.getElementById('anHamburgerBtn');
    const menu = document.getElementById('anMobileMenu');
    if (btn && menu) {
        btn.addEventListener('click', function () {
            menu.classList.toggle('open');
        });
    }
})();
</script>