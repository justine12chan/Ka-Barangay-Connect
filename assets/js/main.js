/* ============================================================
   Ka-Barangay Connect — Shared JavaScript
   assets/js/main.js
   ============================================================ */

/* ── Report page: image picker ── */
function showImageOptions() {
    var choice  = confirm('Open Camera?\n\nTap OK for Camera, Cancel to choose from Gallery.');
    var inputId = choice ? 'cameraInput' : 'fileInput';
    var el      = document.getElementById(inputId);
    if (el) el.click();
}

function handleFileChange(e) {
    var file    = e.target.files[0];
    var display = document.getElementById('fileNameDisplay');
    if (file && display) {
        display.style.display = 'block';
        display.textContent   = '✓ ' + file.name;
    }
}


/* ════════════════════════════════════════════════════════════
   DARK MODE — fully self-contained
   All styles injected via <style> tag — no external CSS needed
   Persisted in localStorage key: "kbc-theme"
   ════════════════════════════════════════════════════════════ */

var DARK_CSS = [

'/* Page backgrounds */',
'html[data-theme="dark"],html[data-theme="dark"] body,',
'html[data-theme="dark"] body.page-report,',
'html[data-theme="dark"] body.page-community-issues,',
'html[data-theme="dark"] body.page-announcements,',
'html[data-theme="dark"] body.page-projects,',
'html[data-theme="dark"] body.page-resident {',
'  background:#130808 !important; color:#f0e8df !important; }',

'/* General text */',
'html[data-theme="dark"] p,',
'html[data-theme="dark"] span:not(.kbc-icon-moon):not(.kbc-icon-sun),',
'html[data-theme="dark"] li,',
'html[data-theme="dark"] td,',
'html[data-theme="dark"] th { color:#f0e8df; }',

'html[data-theme="dark"] h1,html[data-theme="dark"] h2,',
'html[data-theme="dark"] h3,html[data-theme="dark"] h4,',
'html[data-theme="dark"] h5,html[data-theme="dark"] h6 { color:#f5ede3 !important; }',

'html[data-theme="dark"] .text-muted,',
'html[data-theme="dark"] .card-hero-sub,html[data-theme="dark"] .panel-top-sub,',
'html[data-theme="dark"] .issues-subtitle,html[data-theme="dark"] .announcement-date,',
'html[data-theme="dark"] .hiw-card-desc,html[data-theme="dark"] .hiw-sub,',
'html[data-theme="dark"] .picker-sub,html[data-theme="dark"] .panel-count,',
'html[data-theme="dark"] .scroll-hint,html[data-theme="dark"] .issues-footer-text,',
'html[data-theme="dark"] .announcement-meta { color:#b09080 !important; }',

'html[data-theme="dark"] .vm-text           { color:rgba(240,232,223,0.82) !important; }',
'html[data-theme="dark"] .announcement-excerpt { color:#c8b0a0 !important; }',
'html[data-theme="dark"] .issue-text        { color:#c8b0a0 !important; }',
'html[data-theme="dark"] .announcement-title { color:#f5ede3 !important; }',

'/* Navbar */',
'html[data-theme="dark"] .kbc-nav { background:rgba(20,5,5,0.97) !important; border-bottom-color:rgba(212,169,106,0.22) !important; }',
'html[data-theme="dark"] .kbc-links a      { color:#d4b8a8 !important; }',
'html[data-theme="dark"] .kbc-links a:hover{ color:#fff !important; }',
'html[data-theme="dark"] .kbc-links a.active{ color:#d4a96a !important; }',
'html[data-theme="dark"] .kbc-mobile-menu  { background:rgba(16,4,4,0.99) !important; }',
'html[data-theme="dark"] .kbc-mobile-menu a{ color:#d4b8a8 !important; }',

'/* Cards */',
'html[data-theme="dark"] .panel-card,html[data-theme="dark"] .issues-card,',
'html[data-theme="dark"] .report-card,html[data-theme="dark"] .report-item-card,',
'html[data-theme="dark"] .announcement-card,html[data-theme="dark"] .project-card,',
'html[data-theme="dark"] .submit-card,html[data-theme="dark"] .projects-container,',
'html[data-theme="dark"] .ann-social-card,html[data-theme="dark"] .proj-social-card,',
'html[data-theme="dark"] .hiw-card {',
'  background:#1e0f0f !important; border-color:#3d2020 !important; color:#f0e8df !important; }',

'html[data-theme="dark"] .hiw-card-title { color:#f0e8df !important; }',
'html[data-theme="dark"] .hiw-card-desc  { color:#b09080 !important; }',
'html[data-theme="dark"] .hiw-heading    { color:#f0e8df !important; }',
'html[data-theme="dark"] .hiw-sub        { color:#b09080 !important; }',

'html[data-theme="dark"] .panel-body { background:#1e0f0f !important; }',
'html[data-theme="dark"] .panel-footer { background:linear-gradient(180deg,transparent,rgba(16,6,6,0.6)) !important; border-top-color:#3d2020 !important; }',
'html[data-theme="dark"] .ph { background:#3a2020 !important; }',

'/* Announcement meta borders */',
'html[data-theme="dark"] .announcement-meta { border-top-color:#3d2020 !important; border-bottom-color:#3d2020 !important; color:#9a7060 !important; }',

'/* Issue rows & stats */',
'html[data-theme="dark"] .i-stat { background:linear-gradient(180deg,#2a1010,#1e0808) !important; border-right-color:#3d2020 !important; }',
'html[data-theme="dark"] .i-stat-num   { color:#e8a090 !important; }',
'html[data-theme="dark"] .i-stat-label { color:#9a7060 !important; }',
'html[data-theme="dark"] .issue-row { background:linear-gradient(135deg,#2a1010,#1e0808) !important; border-color:#3d2020 !important; }',
'html[data-theme="dark"] .issues-footer { border-top-color:#3d2020 !important; }',

'/* Status badges */',
'html[data-theme="dark"] .b-open     { background:#2e1a00 !important; color:#e8c070 !important; }',
'html[data-theme="dark"] .b-progress { background:#2a0808 !important; color:#f09090 !important; }',
'html[data-theme="dark"] .b-done     { background:#082a14 !important; color:#50e090 !important; }',
'html[data-theme="dark"] .status-ongoing   { background:#2a0808 !important; color:#f09090 !important; }',
'html[data-theme="dark"] .status-completed { background:#082a14 !important; color:#50e090 !important; }',
'html[data-theme="dark"] .status-planned   { background:#0a1a2e !important; color:#70b0f0 !important; }',
'html[data-theme="dark"] .urgent-badge { background:#2e1a00 !important; color:#e8c070 !important; }',

'/* Section wrappers */',
'html[data-theme="dark"] .board-wrap,html[data-theme="dark"] .hiw-wrap { background:#1a0808 !important; border-top-color:rgba(212,169,106,0.12) !important; border-bottom-color:rgba(212,169,106,0.12) !important; }',
'html[data-theme="dark"] .hiw-eyebrow { background:rgba(212,169,106,0.12) !important; border-color:rgba(212,169,106,0.28) !important; color:#d4a96a !important; }',
'html[data-theme="dark"] .section-line { background:linear-gradient(90deg,#3d2020,transparent) !important; }',

'/* Page body */',
'html[data-theme="dark"] .page-body { background:transparent !important; }',

'/* Form elements */',
'html[data-theme="dark"] .field-input,html[data-theme="dark"] .sf-input,',
'html[data-theme="dark"] .comment-input,html[data-theme="dark"] .af-input,',
'html[data-theme="dark"] .sort-dropdown select,html[data-theme="dark"] .search-box input {',
'  background:#2a1010 !important; border-color:#4a2424 !important; color:#f0e8df !important; }',

'html[data-theme="dark"] .field-input:focus,html[data-theme="dark"] .comment-input:focus,',
'html[data-theme="dark"] .search-box input:focus {',
'  background:#321414 !important; border-color:#d4a96a !important; box-shadow:0 0 0 3px rgba(212,169,106,0.15) !important; }',

'html[data-theme="dark"] .field-input::placeholder,',
'html[data-theme="dark"] .search-box input::placeholder { color:#8a6050 !important; }',

'html[data-theme="dark"] .sort-dropdown label { color:#b09080 !important; }',
'html[data-theme="dark"] .field-label         { color:#b09080 !important; }',
'html[data-theme="dark"] .form-line           { background:#3d2020 !important; }',

'html[data-theme="dark"] .image-picker { background:#2a1010 !important; border-color:#5a2828 !important; }',
'html[data-theme="dark"] .picker-title { color:#d4a96a !important; }',

'/* Filter tabs */',
'html[data-theme="dark"] .projects-filter-tabs .filter-tab { background:#2a1010 !important; border-color:#4a2424 !important; color:#b09080 !important; }',
'html[data-theme="dark"] .projects-filter-tabs .filter-tab:hover { border-color:#d4a96a !important; color:#d4a96a !important; }',

'/* Login */',
'html[data-theme="dark"] .card-glass { background:rgba(40,8,8,0.55) !important; border-color:rgba(212,169,106,0.14) !important; }',
'html[data-theme="dark"] .auth-tab { background:#2a1010 !important; color:#b09080 !important; border-color:#3d2020 !important; }',
'html[data-theme="dark"] .auth-tab.active { background:#3d0606 !important; color:#f0e8df !important; }',

'/* Anon / report */',
'html[data-theme="dark"] .anon-toggle { background:#2a1010 !important; border-color:#4a2424 !important; color:#d4a96a !important; }',
'html[data-theme="dark"] .anon-badge  { background:rgba(212,169,106,0.08) !important; border-color:rgba(212,169,106,0.25) !important; color:#d4a96a !important; }',
'html[data-theme="dark"] .back-btn.light { background:#2a1010 !important; border-color:#4a2424 !important; color:#d4a96a !important; }',
'html[data-theme="dark"] .rp-nav { background:linear-gradient(90deg,#2e0808,#3a0a0a) !important; }',

'/* Footer */',
'html[data-theme="dark"] .kbc-footer { background:#0d0404 !important; color:#b09080 !important; }',
'html[data-theme="dark"] .kbc-footer-bar { background:#0a0303 !important; border-top-color:rgba(212,169,106,0.12) !important; color:#8a6050 !important; }',
'html[data-theme="dark"] .kbc-footer a { color:#d4a96a !important; }',

'/* Pagination */',
'html[data-theme="dark"] .announcements-pagination { border-top-color:#3d2020 !important; }',

'/* Scrollbar */',
'html[data-theme="dark"] ::-webkit-scrollbar { width:8px; }',
'html[data-theme="dark"] ::-webkit-scrollbar-track { background:#1a0808; }',
'html[data-theme="dark"] ::-webkit-scrollbar-thumb { background:#4a2020; border-radius:4px; }',
'html[data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background:#6a3030; }',

'/* Bootstrap */',
'html[data-theme="dark"] .modal-content,html[data-theme="dark"] .offcanvas { background:#1e0f0f !important; color:#f0e8df !important; border-color:#3d2020 !important; }',
'html[data-theme="dark"] .table,html[data-theme="dark"] .table td,html[data-theme="dark"] .table th { color:#f0e8df !important; border-color:#3d2020 !important; }',
].join('\n');


/* ════════════════════════════════════════════════════════════
   GLOBAL FONT BOOST — always on, light + dark
   ════════════════════════════════════════════════════════════ */
var FONT_CSS = [
'/* ── Base ── */',
'body { font-size:16px !important; line-height:1.75 !important; }',

'/* ── Navigation ── */',
'.kbc-links a          { font-size:15px !important; font-weight:700 !important; }',
'.kbc-brand-name       { font-size:17px !important; }',
'.kbc-brand-sub        { font-size:12px !important; }',
'.header-title,.header-name { font-size:16px !important; }',
'.header-sub,.header-loc    { font-size:12.5px !important; }',
'.kbc-mobile-menu a    { font-size:16px !important; }',

'/* ── Hero / info box ── */',
'.info-box-heading     { font-size:clamp(20px,3vw,34px) !important; }',
'.info-box-label       { font-size:12px !important; }',

'/* ── Section labels ── */',
'.section-eyebrow      { font-size:12.5px !important; letter-spacing:0.1em !important; }',
'.vm-label             { font-size:12px !important; }',
'.vm-text              { font-size:15px !important; line-height:1.8 !important; }',

'/* ── Panel cards ── */',
'.panel-top-title      { font-size:15px !important; }',
'.panel-top-sub        { font-size:13px !important; }',
'.panel-count          { font-size:13.5px !important; }',
'.panel-cta            { font-size:12px !important; }',

'/* ── Issues card ── */',
'.issues-eyebrow       { font-size:11px !important; }',
'.issues-title         { font-size:22px !important; }',
'.issues-subtitle      { font-size:14px !important; }',
'.i-stat-num           { font-size:26px !important; }',
'.i-stat-label         { font-size:11.5px !important; }',
'.issue-text           { font-size:14px !important; }',
'.i-badge              { font-size:11px !important; }',
'.issues-footer-text   { font-size:13px !important; }',
'.issues-cta           { font-size:12px !important; }',

'/* ── Announcement cards ── */',
'.announcement-title   { font-size:18px !important; }',
'.announcement-excerpt { font-size:15px !important; line-height:1.7 !important; }',
'.announcement-date    { font-size:12.5px !important; }',
'.announcement-badge   { font-size:11.5px !important; }',
'.announcement-read-btn{ font-size:13px !important; }',
'.announcement-meta    { font-size:13px !important; }',

'/* ── Project cards ── */',
'.project-card-title   { font-size:16px !important; }',
'.project-status-badge { font-size:12px !important; }',
'.project-budget,.project-date { font-size:13.5px !important; }',

'/* ── Report / form ── */',
'.card-hero-eyebrow    { font-size:11.5px !important; }',
'.card-hero-title      { font-size:21px !important; }',
'.card-hero-sub        { font-size:13px !important; }',
'.form-pill            { font-size:12px !important; }',
'.field-label          { font-size:13px !important; font-weight:700 !important; }',
'.field-input          { font-size:15px !important; }',
'.sf-input,.comment-input,.af-input { font-size:15px !important; }',
'.picker-title         { font-size:15px !important; }',
'.picker-sub           { font-size:13.5px !important; }',
'#fileNameDisplay      { font-size:13.5px !important; }',
'.btn-submit           { font-size:16px !important; letter-spacing:0.04em !important; }',
'.sort-dropdown label  { font-size:14px !important; }',
'.sort-dropdown select { font-size:14.5px !important; }',
'.search-box input     { font-size:15px !important; }',

'/* ── How it works ── */',
'.hiw-eyebrow          { font-size:12px !important; }',
'.hiw-heading          { font-size:34px !important; }',
'.hiw-sub              { font-size:15px !important; }',
'.hiw-card-title       { font-size:17px !important; }',
'.hiw-card-desc        { font-size:14.5px !important; line-height:1.75 !important; }',

'/* ── Floating btn ── */',
'.floating-btn         { font-size:15px !important; height:50px !important; padding:0 28px !important; }',

'/* ── Auth / login ── */',
'.auth-tab             { font-size:15px !important; }',
'.af-input             { font-size:15px !important; padding:14px 16px !important; }',
'.af-btn               { font-size:16px !important; padding:15px !important; }',
'.al-heading           { font-size:38px !important; }',

'/* ── Back btn ── */',
'.back-btn             { font-size:13px !important; padding:8px 18px !important; }',

'/* ── Footer ── */',
'.kbc-footer           { font-size:14.5px !important; }',
'.kbc-footer-bar       { font-size:13px !important; }',

'/* ── Scroll hint ── */',
'.scroll-hint          { font-size:13px !important; }',
].join('\n');

function injectFontBoost() {
    if (document.getElementById('kbc-font-boost')) return;
    var style = document.createElement('style');
    style.id  = 'kbc-font-boost';
    style.textContent = FONT_CSS;
    document.head.appendChild(style);
}

function injectDarkStyles() {
    if (document.getElementById('kbc-dark-style')) return;
    var style = document.createElement('style');
    style.id  = 'kbc-dark-style';
    style.textContent = DARK_CSS;
    document.head.appendChild(style);
}

/* Apply saved theme BEFORE first paint — no flash
   Skip entirely on admin pages (they use body.dark-mode, not data-theme) */
if (!window.__isAdminPage) {
    injectFontBoost();
    injectDarkStyles();
    (function () {
        var saved = localStorage.getItem('kbc-theme');
        if (saved === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            // Broadcast to any already-open admin tabs
            try { new BroadcastChannel('kbc-theme').postMessage({ dark: true }); } catch (e) {}
        }
    }());
}

/* BroadcastChannel — notifies open admin tabs when resident toggles dark mode.
   Admin tabs mirror the change instantly (session-only, no localStorage).     */
var _kbcAdminChannel = (function () {
    try { return new BroadcastChannel('kbc-theme'); }
    catch (e) { return { postMessage: function () {} }; }
}());

function toggleDarkMode() {
    var html   = document.documentElement;
    var isDark = html.getAttribute('data-theme') === 'dark';
    var next   = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('kbc-theme', next);
    var btn = document.getElementById('kbc-dark-toggle');
    if (btn) updateToggleIcon(btn);
    // Broadcast to any open admin tabs so they mirror the change
    _kbcAdminChannel.postMessage({ dark: next === 'dark' });
}

function updateToggleIcon(btn) {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var moon = btn.querySelector('.kbc-icon-moon');
    var sun  = btn.querySelector('.kbc-icon-sun');
    if (isDark) {
        btn.style.background  = 'linear-gradient(135deg,#d4a96a,#c49a45)';
        btn.style.color       = '#2a0303';
        btn.style.borderColor = 'rgba(212,169,106,0.7)';
        btn.style.boxShadow   = '0 4px 20px rgba(212,169,106,0.35)';
        if (moon) moon.style.display = 'none';
        if (sun)  sun.style.display  = 'block';
    } else {
        btn.style.background  = 'linear-gradient(135deg,#6b0f0f,#3d0606)';
        btn.style.color       = '#d4a96a';
        btn.style.borderColor = 'rgba(212,169,106,0.5)';
        btn.style.boxShadow   = '0 4px 20px rgba(0,0,0,0.5)';
        if (moon) moon.style.display = 'block';
        if (sun)  sun.style.display  = 'none';
    }
}

function injectDarkToggle() {
    if (document.getElementById('kbc-dark-toggle')) return;

    var btn  = document.createElement('button');
    btn.id   = 'kbc-dark-toggle';
    btn.type = 'button';
    btn.title = 'Toggle Dark Mode';
    btn.setAttribute('aria-label', 'Toggle dark mode');

    btn.style.position        = 'fixed';
    btn.style.bottom          = '28px';
    btn.style.left            = '20px';
    btn.style.zIndex          = '2147483647';
    btn.style.width           = '48px';
    btn.style.height          = '48px';
    btn.style.borderRadius    = '50%';
    btn.style.border          = '2px solid rgba(212,169,106,0.5)';
    btn.style.display         = 'flex';
    btn.style.alignItems      = 'center';
    btn.style.justifyContent  = 'center';
    btn.style.cursor          = 'pointer';
    btn.style.pointerEvents   = 'all';
    btn.style.outline         = 'none';
    btn.style.padding         = '0';
    btn.style.transition      = 'transform 0.2s';
    btn.style.webkitTapHighlightColor = 'transparent';

    btn.innerHTML =
        '<svg class="kbc-icon-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>' +
        '<svg class="kbc-icon-sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        toggleDarkMode();
    });

    btn.addEventListener('mouseenter', function () { btn.style.transform = 'scale(1.12)'; });
    btn.addEventListener('mouseleave', function () { btn.style.transform = 'scale(1)'; });

    document.body.appendChild(btn);
    updateToggleIcon(btn);
}


/* ── DOMContentLoaded ── */
document.addEventListener('DOMContentLoaded', function () {

    /* File inputs */
    var fileInput   = document.getElementById('fileInput');
    var cameraInput = document.getElementById('cameraInput');
    if (fileInput)   fileInput.addEventListener('change', handleFileChange);
    if (cameraInput) cameraInput.addEventListener('change', handleFileChange);

    /* Dark mode toggle — resident pages only */
    if (!window.__isAdminPage) { injectDarkToggle(); }

    /* Hamburger — resident.php uses "hamburgerBtn" / "mobileMenu" */
    var hamburger  = document.getElementById('hamburgerBtn') || document.getElementById('kbc-hamburger');
    var mobileMenu = document.getElementById('mobileMenu')   || document.getElementById('kbc-mobile-menu');
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', function () {
            mobileMenu.classList.toggle('open');
        });
        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mobileMenu.classList.remove('open');
            });
        });
    }

});