/* ============================================================
   Ka-Barangay Connect — announcement.php page scripts
   assets/js/announcement.js
============================================================ */

 
const searchBox    = document.getElementById('searchAnnouncements');
const sortDropdown = document.getElementById('sortBy');
const feed         = document.getElementById('announcementsFeed');

searchBox.addEventListener('keyup', function () {
    const term = this.value.toLowerCase();
    feed.querySelectorAll('.ann-social-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});


sortDropdown.addEventListener('change', function () {
    const val   = this.value;
    const cards = Array.from(feed.querySelectorAll('.ann-social-card'));
    cards.sort((a, b) => {
        if (val === 'urgent') {
            return parseInt(b.dataset.urgent) - parseInt(a.dataset.urgent);
        }
        const da = new Date(a.dataset.date), db = new Date(b.dataset.date);
        return val === 'oldest' ? da - db : db - da;
    });
    cards.forEach(c => feed.appendChild(c));
});


document.querySelectorAll('.ann-social-card').forEach(card => {
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
});

function openAnnModal(id) {
    const m = document.getElementById('ann-modal-' + id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeAnnModal(id) {
    const m = document.getElementById('ann-modal-' + id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function handleAnnBackdropClick(e, id) {
    if (e.target === e.currentTarget) closeAnnModal(id);
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
