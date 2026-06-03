/* ============================================================
   Ka-Barangay Connect — admin_login.php scripts
   assets/js/admin-login.js
============================================================ */

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
