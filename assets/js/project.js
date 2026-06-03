/* ============================================================
   Ka-Barangay Connect — project.php page scripts
   assets/js/project.js
============================================================ */

/* ── Filter tabs ── */
const filterTabs   = document.querySelectorAll('.filter-tab');
const projectCards = document.querySelectorAll('.proj-social-card');

filterTabs.forEach(tab => {
    tab.addEventListener('click', function () {
        const filter = this.getAttribute('data-filter');
        filterTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        projectCards.forEach(card => {
            if (filter === 'all') {
                card.style.display = '';
            } else {
                card.style.display = card.getAttribute('data-status') === filter ? '' : 'none';
            }
        });
    });
});

/* ── Keyboard nav for cards ── */
projectCards.forEach(card => {
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
});

/* ── Modal ── */
function openProjModal(id) {
    const m = document.getElementById('proj-modal-' + id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeProjModal(id) {
    const m = document.getElementById('proj-modal-' + id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function handleProjBackdropClick(e, id) {
    if (e.target === e.currentTarget) closeProjModal(id);
}

/* ── Lightbox ── */
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeLightbox();
        document.querySelectorAll('.issue-modal-backdrop.open').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
    }
});
