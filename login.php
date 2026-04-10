<?php
session_start();
require_once 'connection.php';

$php_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $php_error = 'Please fill in all fields.';
    } else {
        $u = mysqli_real_escape_string($conn, $username);
        $res = executeQuery("SELECT * FROM officials WHERE username='$u' LIMIT 1");
        $official = $res ? mysqli_fetch_assoc($res) : null;

        if ($official && $password === $official['password']) {
            session_regenerate_id(true);
            $_SESSION['admin_user']      = $official['username'];
            $_SESSION['admin_full_name'] = $official['full_name'];
            $_SESSION['admin_position']  = $official['position'];
            $_SESSION['logged_in']       = true;
            header("Location: official.php");
            exit;
        } else {
            $php_error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Login — Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/shared.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="page-login">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">

        <!-- Back button -->
        <a href="index.html" class="back-btn" style="margin-left:0; display:inline-flex; width:fit-content;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Back
        </a>

        <!-- Header -->
        <div class="login-header">
            <div class="login-logo">
                <img src="assets/img/logo.png" alt="Barangay Logo">
            </div>
            <div>
                <div class="login-title">Official Portal</div>
                <div class="login-sub">San Bartolome, Sto. Tomas</div>
            </div>
        </div>

        <div class="login-divider"></div>

        <!-- Error message (PHP-driven) -->
        <div class="login-error <?= $php_error ? 'show' : '' ?>" id="errorMsg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="errorText"><?= htmlspecialchars($php_error) ?></span>
        </div>

        <!-- Form — actual POST to self -->
        <form id="loginForm" method="POST" action="login.php" novalidate>

            <div class="form-group-login">
                <label class="form-label-custom" for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" autocomplete="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group-login">
                <label class="form-label-custom" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" class="form-input pw-field" placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-extras">
                <label class="remember-label">
                    <input type="checkbox" id="rememberMe"> Remember me
                </label>
            </div>

            <button type="submit" class="btn-login">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Log In
            </button>

        </form>

        <p class="login-footer-note">Authorized personnel only &bull; Ka-Barangay Connect</p>

    </div>

    <script src="assets/js/main.js"></script>
    <script>
    // Password show/hide toggle
    const togglePw = document.getElementById('togglePw');
    const pwInput  = document.getElementById('password');
    const eyeIcon  = document.getElementById('eyeIcon');

    const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeClosed = `<line x1="17.94" y1="17.94" x2="3.06" y2="3.06" stroke-linecap="round"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M6.53 6.53A10.02 10.02 0 0 0 1 12s4 8 11 8a10 10 0 0 0 5.47-1.53"/><circle cx="12" cy="12" r="3"/>`;

    togglePw.addEventListener('click', () => {
        const isHidden = pwInput.type === 'password';
        pwInput.type = isHidden ? 'text' : 'password';
        eyeIcon.innerHTML = isHidden ? eyeClosed : eyeOpen;
    });

    // Store username in sessionStorage for sidebar display on admin pages
    <?php if (isset($_SESSION['admin_user'])): ?>
    sessionStorage.setItem('adminUser',    '<?= addslashes($_SESSION['admin_user']) ?>');
    sessionStorage.setItem('adminName',    '<?= addslashes($_SESSION['admin_full_name'] ?? '') ?>');
    sessionStorage.setItem('adminPosition','<?= addslashes($_SESSION['admin_position'] ?? '') ?>');
    <?php endif; ?>

    // Hide error on input
    ['username', 'password'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            document.getElementById('errorMsg').classList.remove('show');
        });
    });
    </script>

</body>
</html>