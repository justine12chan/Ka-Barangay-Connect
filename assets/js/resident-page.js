/* ============================================================
   Ka-Barangay Connect — resident.php page scripts
   assets/js/resident-page.js
============================================================ */

/* Smooth scroll for anchor links + close mobile menu on click */
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
            var menu = document.getElementById('mobileMenu');
            if (menu) menu.classList.remove('open');
        }
    });
});

/* Active nav link on scroll */
var navAnchors = document.querySelectorAll('.kbc-links a[href^="#"]');

function updateActiveLink() {
    var scrollMid = window.scrollY + (window.innerHeight / 2);
    var active = '';

    /* Build ordered list of [id, top, bottom] for nav-linked sections only */
    var sectionBounds = [];
    navAnchors.forEach(function(a) {
        var id = a.getAttribute('href').slice(1);
        var el = document.getElementById(id);
        if (el) sectionBounds.push({ id: id, top: el.offsetTop, bottom: el.offsetTop + el.offsetHeight });
    });

    /* Sort top-to-bottom */
    sectionBounds.sort(function(a, b) { return a.top - b.top; });

    /* Pick the section whose range contains the viewport midpoint;
       fall back to the last section that started above midpoint */
    for (var i = 0; i < sectionBounds.length; i++) {
        if (scrollMid >= sectionBounds[i].top) {
            active = sectionBounds[i].id;
        }
    }

    navAnchors.forEach(function(a) {
        a.classList.toggle('active', a.getAttribute('href') === '#' + active);
    });
}

window.addEventListener('scroll', updateActiveLink, { passive: true });
updateActiveLink();
