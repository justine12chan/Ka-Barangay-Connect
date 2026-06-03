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
    <link rel="stylesheet" href="../assets/css/resident-login.css">
    <link rel="stylesheet" href="../assets/css/resident-darkmode-append.css">
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

    <script src="../assets/js/resident-login.js"></script>

    <!-- Dark Mode Toggle -->
    <button id="kbc-dark-toggle" aria-label="Toggle dark mode">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>
    </button>
</body>
</html>