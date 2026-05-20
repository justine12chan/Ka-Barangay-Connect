<?php
// resident/resident_login.php
session_start();
require_once __DIR__ . '/../connection.php';

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'report.php';

// Already logged in
if (isset($_SESSION['resident_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$error   = '';
$success = '';
$mode    = $_POST['mode'] ?? 'login';

// ── REGISTER ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');
    $purok     = trim($_POST['purok']     ?? '');

    if (!$full_name || !$username || !$password || !$purok) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $u      = mysqli_real_escape_string($conn, $username);
        $exists = mysqli_fetch_row(executeQuery("SELECT id FROM residents WHERE username='$u'"));
        if ($exists) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $n    = mysqli_real_escape_string($conn, $full_name);
            $p    = mysqli_real_escape_string($conn, $purok);
            $h    = mysqli_real_escape_string($conn, $hash);
            executeQuery("INSERT INTO residents (full_name, username, password, purok)
                          VALUES ('$n', '$u', '$h', '$p')");
            $success = 'Account created! You can now log in.';
            $mode    = 'login';
        }
    }
}

// ── LOGIN ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        $u   = mysqli_real_escape_string($conn, $username);
        $res = executeQuery("SELECT * FROM residents WHERE username='$u' LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;

        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['resident_id']    = $row['id'];
            $_SESSION['resident_name']  = $row['full_name'];
            $_SESSION['resident_purok'] = $row['purok'];
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}

$page_mode = (isset($_GET['mode']) && $_GET['mode'] === 'register')
    ? 'register'
    : ($mode === 'register' ? 'register' : 'login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Login — Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;0,800;1,400&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Lora', serif;
            background: #3d0606;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top nav ── */
        .top-nav {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 28px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            background: rgba(92,10,10,.7);
            backdrop-filter: blur(10px);
            position: sticky; top: 0; z-index: 100;
        }
        .top-nav-logo {
            width: 36px; height: 36px; overflow: hidden;
            background: transparent; border: none;
            display: flex; align-items: center; justify-content: center;
        }
        .top-nav-logo img { width: 100%; height: 100%; object-fit: contain; }
        .top-nav-name { font-family: 'Cormorant Garamond', serif; font-size: 14px; font-weight: 800; color: #fff; }
        .top-nav-sub  { font-size: 11px; color: #d4a96a; font-weight: 600; }
        .top-nav-back {
            margin-left: auto;
            font-size: 12.5px; font-weight: 700; color: #d4b8a8;
            text-decoration: none; padding: 7px 16px;
            border: 1px solid rgba(255,255,255,.15); border-radius: 8px;
            transition: background .18s, color .18s;
        }
        .top-nav-back:hover { background: rgba(255,255,255,.08); color: #fff; }

        /* ── Main wrapper ── */
        .auth-wrapper {
            flex: 1; display: flex; align-items: stretch;
            min-height: calc(100vh - 65px);
        }

        /* ── Left panel (branding) ── */
        .auth-left {
            flex: 1; display: none;
            flex-direction: column; justify-content: center; align-items: flex-start;
            padding: 60px 56px;
            background: linear-gradient(160deg, #3d0606 0%, #5c0a0a 55%, #2a0303 100%);
            position: relative; overflow: hidden;
        }
        @media (min-width: 900px) { .auth-left { display: flex; } }

        /* decorative circles */
        .auth-left::before {
            content: ''; position: absolute; top: -80px; right: -80px;
            width: 340px; height: 340px; border-radius: 50%;
            background: radial-gradient(circle, rgba(212,169,106,.12), transparent 70%);
            pointer-events: none;
        }
        .auth-left::after {
            content: ''; position: absolute; bottom: -60px; left: -60px;
            width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(155,31,31,.4), transparent 70%);
            pointer-events: none;
        }

        .al-logo {
            width: 72px; height: 72px;
            background: transparent; border: none;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 28px; overflow: hidden;
        }
        .al-logo img { width: 100%; height: 100%; object-fit: contain; }
        .al-eyebrow {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .12em; color: #d4a96a; margin-bottom: 12px;
        }
        .al-heading {
            font-family: 'Cormorant Garamond', serif; font-size: 34px; font-weight: 800;
            color: #fff; line-height: 1.2; margin-bottom: 16px;
        }
        .al-heading span { color: #d4a96a; }
        .al-desc { font-size: 14px; color: #9e7f70; line-height: 1.75; max-width: 340px; margin-bottom: 40px; }

        .al-features { display: flex; flex-direction: column; gap: 16px; }
        .al-feature { display: flex; align-items: center; gap: 14px; }
        .al-feature-dot {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            background: rgba(212,169,106,.1); border: 1px solid rgba(212,169,106,.25);
            display: flex; align-items: center; justify-content: center; font-size: 17px;
        }
        .al-feature-label { font-size: 13px; color: #d0d8f0; font-weight: 500; }

        /* ── Right panel (form) ── */
        .auth-right {
            width: 100%; max-width: 480px;
            background: #fff;
            display: flex; flex-direction: column; justify-content: center;
            padding: 40px 36px;
            overflow-y: auto;
        }
        @media (min-width: 900px) { .auth-right { max-width: 440px; } }
        @media (max-width: 500px)  { .auth-right { padding: 32px 22px; } }

        /* Mobile logo (shown only when left panel hidden) */
        .mobile-brand {
            display: flex; align-items: center; gap: 12px; margin-bottom: 28px;
        }
        @media (min-width: 900px) { .mobile-brand { display: none; } }
        .mobile-brand-logo {
            width: 44px; height: 44px; overflow: hidden;
            background: transparent; border: none;
        }
        .mobile-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .mobile-brand-name { font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 800; color: #0d0e2e; }
        .mobile-brand-sub  { font-size: 11px; color: #9e7f70; }

        /* heading */
        .ar-heading {
            font-family: 'Cormorant Garamond', serif; font-size: 24px; font-weight: 800;
            color: #0d0e2e; margin-bottom: 4px;
        }
        .ar-sub { font-size: 13px; color: #9e7f70; margin-bottom: 28px; }

        /* tabs */
        .auth-tabs {
            display: flex; gap: 4px;
            background: #f9f3ee; border-radius: 10px;
            padding: 4px; margin-bottom: 26px;
        }
        .auth-tab {
            flex: 1; text-align: center; padding: 9px;
            border-radius: 8px; font-family: 'Cormorant Garamond', serif;
            font-size: 13px; font-weight: 700; color: #9e7f70;
            cursor: pointer; border: none; background: transparent;
            transition: all .2s;
        }
        .auth-tab.active {
            background: #fff; color: #9b1f1f;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }

        /* fields */
        .af-group { margin-bottom: 16px; }
        .af-label {
            font-size: 11.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: #5c5e80; margin-bottom: 7px; display: block;
        }
        .af-input {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #e2e3f0; border-radius: 10px;
            font-size: 14px; font-family: 'Lora', serif;
            color: #0d0e2e; background: #f8f9fc; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .af-input:focus {
            border-color: #9b1f1f;
            box-shadow: 0 0 0 3px rgba(155,31,31,.08);
            background: #fff;
        }
        select.af-input { cursor: pointer; }

        /* password wrapper */
        .af-pw-wrap { position: relative; }
        .af-pw-wrap .af-input { padding-right: 42px; }
        .af-pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #9e7f70;
            display: flex; align-items: center; padding: 0;
        }
        .af-pw-toggle:hover { color: #9b1f1f; }

        /* submit button */
        .af-btn {
            width: 100%; padding: 13px; margin-top: 6px;
            background: linear-gradient(135deg, #9b1f1f, #ad2424);
            color: #fff; border: none; border-radius: 12px;
            font-family: 'Cormorant Garamond', serif; font-size: 14px; font-weight: 700;
            cursor: pointer; letter-spacing: .04em;
            transition: opacity .2s, transform .1s;
            box-shadow: 0 4px 18px rgba(155,31,31,.25);
        }
        .af-btn:hover  { opacity: .9; transform: translateY(-1px); }
        .af-btn:active { transform: translateY(0); }

        /* alert */
        .af-alert {
            padding: 12px 14px; border-radius: 10px;
            font-size: 13px; font-weight: 600; margin-bottom: 18px;
        }
        .af-alert.error   { background: #fff0f0; color: #c0001a; border: 1.5px solid #f7a0aa; }
        .af-alert.success { background: #e6faed; color: #128548; border: 1.5px solid #7de0a4; }

        /* divider */
        .af-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 18px 0; color: #c8cadf; font-size: 12px; font-weight: 600;
        }
        .af-divider::before, .af-divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e3f0;
        }

        /* tab panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* footer */
        .ar-footer {
            margin-top: 24px; text-align: center;
            font-size: 12px; color: #9e7f70;
        }

        /* ════════════════════════════════════════════════
           DARK MODE — resident_login.php specific overrides
           ════════════════════════════════════════════════ */
        html[data-theme="dark"] body {
            background: radial-gradient(circle at top right, #4a0808, #1a0404) !important;
        }
        html[data-theme="dark"] .top-nav {
            background: rgba(20,5,5,0.97) !important;
            border-bottom-color: rgba(212,169,106,0.22) !important;
        }
        html[data-theme="dark"] .top-nav-name { color: #f0e8df !important; }
        html[data-theme="dark"] .top-nav-sub  { color: #b09080 !important; }
        html[data-theme="dark"] .top-nav-back { color: #d4a96a !important; border-color: rgba(255,255,255,.15) !important; }
        html[data-theme="dark"] .auth-left    { background: linear-gradient(160deg,#3d0606 0%,#1a0404 100%) !important; }
        html[data-theme="dark"] .al-heading   { color: #f5ede3 !important; }
        html[data-theme="dark"] .al-sub       { color: #b09080 !important; }
        html[data-theme="dark"] .al-feature-label { color: #c0a898 !important; }
        html[data-theme="dark"] .auth-right   { background: #1a0808 !important; }
        html[data-theme="dark"] .ar-heading   { color: #f5ede3 !important; }
        html[data-theme="dark"] .ar-sub       { color: #b09080 !important; }
        html[data-theme="dark"] .af-label     { color: #b09080 !important; }
        html[data-theme="dark"] .af-input     { background: #2a1010 !important; border-color: #4a2020 !important; color: #f0e8df !important; }
        html[data-theme="dark"] .af-input:focus { background: #321414 !important; border-color: #d4a96a !important; box-shadow: 0 0 0 3px rgba(212,169,106,0.15) !important; }
        html[data-theme="dark"] .af-input::placeholder { color: #8a6050 !important; }
        html[data-theme="dark"] .af-input-wrap { background: #2a1010 !important; border-color: #4a2020 !important; }
        html[data-theme="dark"] .auth-tabs    { background: #2a1010 !important; border-color: #3a2020 !important; }
        html[data-theme="dark"] .auth-tab     { color: #b09080 !important; }
        html[data-theme="dark"] .auth-tab.active { background: #3d0606 !important; color: #f0e8df !important; }
        html[data-theme="dark"] .af-divider   { color: #6a4a40 !important; }
        html[data-theme="dark"] .af-divider::before,
        html[data-theme="dark"] .af-divider::after { background: #3a2020 !important; }
        html[data-theme="dark"] .ar-footer    { color: #7a5a50 !important; }
        html[data-theme="dark"] .af-alert.error   { background: #2a0808 !important; color: #f09090 !important; border-color: #4a1010 !important; }
        html[data-theme="dark"] .af-alert.success { background: #082a14 !important; color: #50e090 !important; border-color: #0a4a22 !important; }
        html[data-theme="dark"] .af-pw-toggle { color: #8a6a5a !important; }
        html[data-theme="dark"] .mobile-brand-name { color: #f0e8df !important; }
        html[data-theme="dark"] .mobile-brand-sub  { color: #b09080 !important; }
    </style>
    <!-- Dark mode: load before first paint to avoid flash -->
    <script src="../assets/js/main.js"></script>
</head>
<body>

    <!-- Top nav -->
    <nav class="top-nav">
        <div class="top-nav-logo">
            <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div>
            <div class="top-nav-name">Ka-Barangay Connect</div>
            <div class="top-nav-sub">San Bartolome</div>
        </div>
        <a href="resident.php" class="top-nav-back">&#8592; Back</a>
    </nav>

    <div class="auth-wrapper">

        <!-- ── LEFT PANEL ── -->
        <div class="auth-left">
            <div class="al-logo">
                <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="al-eyebrow">Resident Portal</div>
            <h1 class="al-heading">Your voice<br>for <span>San Bartolome</span></h1>
            <p class="al-desc">
                Log in or register to file reports, track your concerns,
                and engage with your barangay community in real time.
            </p>
            <div class="al-features">
                <div class="al-feature">
                    <div class="al-feature-dot">📋</div>
                    <span class="al-feature-label">File and track community reports</span>
                </div>
                <div class="al-feature">
                    <div class="al-feature-dot">🔔</div>
                    <span class="al-feature-label">Stay updated on barangay announcements</span>
                </div>
                <div class="al-feature">
                    <div class="al-feature-dot">💬</div>
                    <span class="al-feature-label">Leave comments and feedback on issues</span>
                </div>
                <div class="al-feature">
                    <div class="al-feature-dot">🏡</div>
                    <span class="al-feature-label">Contribute to a safer, cleaner barangay</span>
                </div>
            </div>
        </div>

        <!-- ── RIGHT PANEL ── -->
        <div class="auth-right">

            <!-- Mobile branding -->
            <div class="mobile-brand">
                <div class="mobile-brand-logo">
                    <img src="../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
                </div>
                <div>
                    <div class="mobile-brand-name">Ka-Barangay Connect</div>
                    <div class="mobile-brand-sub">San Bartolome</div>
                </div>
            </div>

            <h2 class="ar-heading"><?= $page_mode === 'register' ? 'Create Account' : 'Welcome Back' ?></h2>
            <p class="ar-sub"><?= $page_mode === 'register' ? 'Register as a barangay resident' : 'Log in to your resident account' ?></p>

            <?php if ($error): ?>
            <div class="af-alert error">✗ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="af-alert success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab <?= $page_mode === 'login'    ? 'active' : '' ?>"
                        onclick="switchTab('login')">Log In</button>
                <button class="auth-tab <?= $page_mode === 'register' ? 'active' : '' ?>"
                        onclick="switchTab('register')">Register</button>
            </div>

            <!-- LOGIN FORM -->
            <div id="panel-login" class="tab-panel <?= $page_mode === 'login' ? 'active' : '' ?>">
                <form method="POST" action="">
                    <input type="hidden" name="mode"     value="login">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <div class="af-group">
                        <label class="af-label">Username</label>
                        <input type="text" name="username" class="af-input"
                               placeholder="Enter your username" required autocomplete="username">
                    </div>
                    <div class="af-group">
                        <label class="af-label">Password</label>
                        <div class="af-pw-wrap">
                            <input type="password" name="password" id="pw-login" class="af-input"
                                   placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="af-pw-toggle" onclick="togglePw('pw-login',this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="af-btn">Log In</button>
                </form>
                <div class="af-divider">or</div>
                <p style="text-align:center; font-size:13px; color:#9e7f70;">
                    Don't have an account?
                    <a href="#" onclick="switchTab('register')" style="color:#9b1f1f; font-weight:700; text-decoration:none;">Register here</a>
                </p>
            </div>

            <!-- REGISTER FORM -->
            <div id="panel-register" class="tab-panel <?= $page_mode === 'register' ? 'active' : '' ?>">
                <form method="POST" action="">
                    <input type="hidden" name="mode"     value="register">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <div class="af-group">
                        <label class="af-label">Full Name</label>
                        <input type="text" name="full_name" class="af-input"
                               placeholder="Your full name" required>
                    </div>
                    <div class="af-group">
                        <label class="af-label">Purok / Area</label>
                        <select name="purok" class="af-input" required>
                            <option value="" disabled selected>Select your purok...</option>
                            <?php
                            $puroks = ['Purok 1','Purok 2','Purok 3','Purok 4',
                                       'Purok 5','Purok 6','Purok 7','PMK','Morgan'];
                            foreach ($puroks as $pk): ?>
                            <option value="<?= $pk ?>"><?= $pk ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="af-group">
                        <label class="af-label">Username</label>
                        <input type="text" name="username" class="af-input"
                               placeholder="Choose a username" required autocomplete="username">
                    </div>
                    <div class="af-group">
                        <label class="af-label">Password</label>
                        <div class="af-pw-wrap">
                            <input type="password" name="password" id="pw-reg" class="af-input"
                                   placeholder="At least 6 characters" required autocomplete="new-password">
                            <button type="button" class="af-pw-toggle" onclick="togglePw('pw-reg',this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="af-group">
                        <label class="af-label">Confirm Password</label>
                        <div class="af-pw-wrap">
                            <input type="password" name="confirm" id="pw-confirm" class="af-input"
                                   placeholder="Repeat your password" required autocomplete="new-password">
                            <button type="button" class="af-pw-toggle" onclick="togglePw('pw-confirm',this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="af-btn">Create Account</button>
                </form>
                <div class="af-divider">or</div>
                <p style="text-align:center; font-size:13px; color:#9e7f70;">
                    Already have an account?
                    <a href="#" onclick="switchTab('login')" style="color:#9b1f1f; font-weight:700; text-decoration:none;">Log in here</a>
                </p>
            </div>

            <div class="ar-footer">
                © Ka-Barangay Connect · Barangay San Bartolome
            </div>
        </div>

    </div><!-- /auth-wrapper -->

    <script>
    function switchTab(mode) {
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        const idx = mode === 'login' ? 0 : 1;
        document.querySelectorAll('.auth-tab')[idx].classList.add('active');
        document.getElementById('panel-' + mode).classList.add('active');

        const h = document.querySelector('.ar-heading');
        const s = document.querySelector('.ar-sub');
        if (mode === 'login') {
            h.textContent = 'Welcome Back';
            s.textContent = 'Log in to your resident account';
        } else {
            h.textContent = 'Create Account';
            s.textContent = 'Register as a barangay resident';
        }
    }

    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.innerHTML = isText
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
               </svg>`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
               </svg>`;
    }
    </script>
</body>
</html>