/* ============================================================
   Ka-Barangay Connect — main.js
   Shared across all resident-facing pages.

   Handles:
     1. Dark mode  — persists via localStorage key "kbc-theme"
                     toggle button: #kbc-dark-toggle
     2. Hamburger  — #hamburgerBtn / #mobileMenu (resident.php)
     3. Smooth scroll — anchor links (href="#...")
     4. Navbar shrink on scroll
   ============================================================ */

(function () {
    'use strict';

    /* ── 1. DARK MODE ─────────────────────────────────────────
       Apply theme immediately (before paint) to prevent flash.
       The <script src="main.js"> tag sits in <head> on every
       resident page, so this runs as early as possible.
    ──────────────────────────────────────────────────────────── */
    var html = document.documentElement;

    function applyTheme() {
        var saved = localStorage.getItem('kbc-theme');
        if (saved === 'dark') {
            html.setAttribute('data-theme', 'dark');
        } else {
            // Remove attribute so CSS light-mode defaults take over
            html.removeAttribute('data-theme');
        }
    }

    // Apply immediately (blocks render — intentional, prevents flash)
    applyTheme();

    // Wire up the toggle button once DOM is ready
    document.addEventListener('DOMContentLoaded', function () {

        // Re-apply in case any other script ran between head and DOMContentLoaded
        applyTheme();

        var btn = document.getElementById('kbc-dark-toggle');
        if (btn) {
            btn.addEventListener('click', function () {
                var isDark = html.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    html.removeAttribute('data-theme');
                    localStorage.setItem('kbc-theme', 'light');
                } else {
                    html.setAttribute('data-theme', 'dark');
                    localStorage.setItem('kbc-theme', 'dark');
                }
            });
        }

        /* ── 2. HAMBURGER MENU ─────────────────────────────── */
        var hamburger  = document.getElementById('hamburgerBtn');
        var mobileMenu = document.getElementById('mobileMenu');

        if (hamburger && mobileMenu) {
            hamburger.addEventListener('click', function () {
                var open = mobileMenu.classList.toggle('open');
                hamburger.classList.toggle('active', open);
                hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            // Close menu when a link inside it is clicked
            mobileMenu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    mobileMenu.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                });
            });

            // Close menu on outside click
            document.addEventListener('click', function (e) {
                if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
            });
        }

        /* ── 3. SMOOTH SCROLL for anchor links ─────────────── */
        document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        /* ── 4. NAVBAR SHRINK ON SCROLL ─────────────────────── */
        var nav = document.querySelector('.kbc-nav');
        if (nav) {
            var onScroll = function () {
                if (window.scrollY > 40) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll(); // run once on load
        }

    }); // end DOMContentLoaded

})();