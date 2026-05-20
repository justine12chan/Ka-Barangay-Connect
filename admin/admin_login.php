<?php
session_start();
require_once __DIR__ . '/../connection.php';

if (isset($_SESSION['userID'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$php_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $php_error = 'Please fill in all fields.';
    } else {
        $u       = mysqli_real_escape_string($conn, $username);
        $res     = executeQuery("SELECT * FROM officials WHERE username='$u' LIMIT 1");
        $official = $res ? mysqli_fetch_assoc($res) : null;

        if ($official && $password === $official['password']) {
            session_regenerate_id(true);
            $_SESSION['userID']          = $official['id'];
            $_SESSION['admin_user']      = $official['username'];
            $_SESSION['admin_full_name'] = $official['full_name'];
            $_SESSION['admin_position']  = $official['position'];
            $_SESSION['logged_in']       = true;
            header('Location: admin_dashboard.php');
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
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* ── Login-only extras not in admin.css ── */
        .login-error {
            display: none;
            align-items: center; gap: 9px;
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            background: rgba(224,80,80,.08);
            border: 1px solid rgba(224,80,80,.18);
            color: #f08080;
            font-size: 13px;
            margin-bottom: 16px;
            font-family: var(--font-body);
        }
        .login-error.show { display: flex; }
        .login-error svg  { flex-shrink: 0; width: 16px; height: 16px; }

        .form-extras {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 7px;
            font-size: 12.5px; color: var(--text-muted);
            cursor: pointer; user-select: none;
            font-family: var(--font-body);
        }
        .remember-label input[type="checkbox"] {
            accent-color: var(--gold);
            width: 14px; height: 14px;
        }

        .toggle-pw {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); display: flex; align-items: center;
            padding: 0; transition: color 0.18s;
        }
        .toggle-pw:hover { color: var(--text-secondary); }
        .toggle-pw svg { width: 16px; height: 16px; }

        .btn-login {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px;
            background: var(--gold);
            color: var(--obsidian-deep);
            border: none; border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 14px; font-weight: 700;
            cursor: pointer; letter-spacing: 0.02em;
            transition: var(--transition);
            box-shadow: 0 4px 18px rgba(201,168,76,.2);
        }
        .btn-login:hover {
            background: var(--gold-warm);
            box-shadow: 0 6px 24px rgba(201,168,76,.32);
            transform: translateY(-1px);
        }
        .btn-login:active { transform: scale(0.98); }
        .btn-login svg { width: 16px; height: 16px; }

        .login-footer-note {
            text-align: center;
            font-size: 11px; color: var(--text-muted);
            margin: 20px 0 0;
            font-family: var(--font-body);
            letter-spacing: 0.04em;
        }

        /* Ambient blobs */
        .blob {
            position: fixed; border-radius: 50%;
            pointer-events: none; filter: blur(80px); opacity: 0.07;
        }
        .blob-1 {
            width: 480px; height: 480px;
            background: var(--gold);
            top: -120px; left: -120px;
        }
        .blob-2 {
            width: 380px; height: 380px;
            background: var(--signal-blue);
            bottom: -80px; right: -80px;
        }
    </style>
</head>
<body class="page-login">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">

        <a href="../resident/resident.php" class="back-btn" style="margin-left:0; display:inline-flex; width:fit-content; margin-bottom: 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Back
        </a>

        <div class="login-header">
            <div class="login-logo">
                <img src="../assets/img/logo.png" alt="Barangay Logo">
            </div>
            <div>
                <div class="login-title">Official Portal</div>
                <div class="login-sub">San Bartolome, Sto. Tomas</div>
            </div>
        </div>

        <div class="login-divider"></div>

        <div class="login-error <?= $php_error ? 'show' : '' ?>" id="errorMsg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="errorText"><?= htmlspecialchars($php_error) ?></span>
        </div>

        <form id="loginForm" method="POST" action="admin_login.php" novalidate>

            <div class="form-group-login">
                <label class="form-label-custom" for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" class="form-input"
                           placeholder="Enter your username" autocomplete="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group-login">
                <label class="form-label-custom" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" class="form-input pw-field"
                           placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Log In
            </button>

        </form>

        <p class="login-footer-note">Authorized personnel only &bull; Ka-Barangay Connect</p>

    </div>

    <script src="../assets/js/main.js"></script>
    <script>
    const togglePw = document.getElementById('togglePw');
    const pwInput  = document.getElementById('password');
    const eyeIcon  = document.getElementById('eyeIcon');

    const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeClosed = `<line x1="17.94" y1="17.94" x2="3.06" y2="3.06" stroke-linecap="round"/>
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
        <path d="M6.53 6.53A10.02 10.02 0 0 0 1 12s4 8 11 8a10 10 0 0 0 5.47-1.53"/>
        <circle cx="12" cy="12" r="3"/>`;

    togglePw.addEventListener('click', () => {
        const isHidden = pwInput.type === 'password';
        pwInput.type      = isHidden ? 'text' : 'password';
        eyeIcon.innerHTML = isHidden ? eyeClosed : eyeOpen;
    });

    ['username', 'password'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            document.getElementById('errorMsg').classList.remove('show');
        });
    });
    </script>
</body>
</html>