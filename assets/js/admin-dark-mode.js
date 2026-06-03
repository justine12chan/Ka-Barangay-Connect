/* ============================================================
   Ka-Barangay Connect — Admin dark mode controller
   Shared across all admin pages.
   assets/js/admin-dark-mode.js
============================================================ */

// ── Admin Dark Mode Controller ─────────────────────────────────────────────
// Uses sessionStorage so dark mode persists when navigating between admin
// pages (dashboard → report → program) but resets when browser is closed.
// Resident side controls it via BroadcastChannel('kbc-theme').
// ──────────────────────────────────────────────────────────────────────────
(function () {
var SESSION_KEY = 'kbc_admin_dark';

function applyDark(on) {
    document.body.classList.toggle('dark-mode', on);
    try { sessionStorage.setItem(SESSION_KEY, on ? '1' : '0'); } catch(e) {}
}

// Apply saved session state immediately on load
try {
    if (sessionStorage.getItem(SESSION_KEY) === '1') {
        document.body.classList.add('dark-mode');
    }
} catch(e) {}

// Manual toggle from FAB
window.toggleAdminDarkMode = function () {
    applyDark(!document.body.classList.contains('dark-mode'));
};
window.toggleDarkMode = window.toggleAdminDarkMode;

// Listen for resident-side broadcasts
try {
    var ch = new BroadcastChannel('kbc-theme');
    ch.onmessage = function (e) {
        if (e.data && typeof e.data.dark !== 'undefined') {
            applyDark(!!e.data.dark);
        }
    };
} catch(e) {}
})();
