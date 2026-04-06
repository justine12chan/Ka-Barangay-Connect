<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Login — Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
</head>
<body class="page-login">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">

        <!-- Back button -->
        <a href="index.html" class="back-btn">
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

        <!-- Error message -->
        <div class="login-error" id="errorMsg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span id="errorText">Invalid username or password.</span>
        </div>

        <!-- Form -->
        <form id="loginForm" novalidate>

            <div class="form-group-login">
                <label class="form-label-custom" for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" class="form-input" placeholder="Enter your username" autocomplete="username" required>
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
                    <input type="password" id="password" class="form-input pw-field" placeholder="Enter your password" autocomplete="current-password" required>
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
        // Password visibility toggle
        const togglePw = document.getElementById('togglePw');
        const pwInput  = document.getElementById('password');
        const eyeIcon  = document.getElementById('eyeIcon');

        const eyeOpen   = <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>;
        const eyeClosed = <line x1="17.94" y1="17.94" x2="3.06" y2="3.06" stroke-linecap="round"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M6.53 6.53A10.02 10.02 0 0 0 1 12s4 8 11 8a10 10 0 0 0 5.47-1.53"/><circle cx="12" cy="12" r="3"/>;

        togglePw.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            eyeIcon.innerHTML = isHidden ? eyeClosed : eyeOpen;
        });

        // Form submit — replace DEMO_USER/DEMO_PASS with real auth logic
        const form     = document.getElementById('loginForm');
        const errorMsg = document.getElementById('errorMsg');
        const errorTxt = document.getElementById('errorText');

        const DEMO_USER = 'admin';
        const DEMO_PASS = 'barangay123';

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            errorMsg.classList.remove('show');

            const user = document.getElementById('username').value.trim();
            const pass = document.getElementById('password').value;

            if (!user || !pass) {
                errorTxt.textContent = 'Please fill in all fields.';
                errorMsg.classList.add('show');
                return;
            }

            if (user === DEMO_USER && pass === DEMO_PASS) {
                window.location.href = 'official.php';
            } else {
                errorTxt.textContent = 'Invalid username or password.';
                errorMsg.classList.add('show');
                document.getElementById('password').value = '';
                document.getElementById('password').focus();
            }
        });

        ['username', 'password'].forEach(id => {
            document.getElementById(id).addEventListener('input', () => {
                errorMsg.classList.remove('show');
            });
        });
    </script>

</body>
</html>