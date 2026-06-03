/* ============================================================
   Ka-Barangay Connect — resident_login.php page scripts
   assets/js/resident-login.js
============================================================ */

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
