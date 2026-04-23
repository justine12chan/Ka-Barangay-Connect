/* ============================================================
   Ka-Barangay Connect — Admin Report Page
   assets/js/admin_report.js
   ============================================================ */

/* ── Data (injected by PHP via window globals before this script loads) ──
   Expected globals set inline in admin_report.php:
     window.REPORTS_DATA   = <?= json_encode(array_values($reports)) ?>;
     window.STAT_PENDING   = <?= $r_pending ?>;
     window.STAT_PROGRESS  = <?= $r_progress ?>;
     window.STAT_RESOLVED  = <?= $r_resolved ?>;
   ──────────────────────────────────────────────────────────── */

const reports = window.REPORTS_DATA || [];

/* ── Config maps ── */
const catColors = {
    'Infrastructure':   { bg: '#fff3e0', color: '#c47200', border: '#ffd580' },
    'Kalikasan':        { bg: '#e6faed', color: '#128548', border: '#7de0a4' },
    'Serbisyo Publiko': { bg: '#e8f0fe', color: '#1a56db', border: '#90aef8' },
    'Kapayapaan':       { bg: '#fce8ff', color: '#8b00c7', border: '#d48cf7' },
    'Publiko':          { bg: '#fff0f0', color: '#c0001a', border: '#f7a0aa' },
};

const statusConfig = {
    'pending':     { label: 'Pending',     bg: '#fff3e0', color: '#c47200' },
    'in-progress': { label: 'In Progress', bg: '#e8f0fe', color: '#1a56db' },
    'resolved':    { label: 'Resolved',    bg: '#e6faed', color: '#128548' },
};

/* ── Category helpers ── */
// Stored as "Group|Specific Issue" (new) or just "Group" (old records)
function catGroup(raw)   { return (raw || '').split('|')[0].trim(); }
function catSpecific(raw){ const p=(raw||'').split('|'); return p.length>1?p[1].trim():null; }
function catDisplayLabel(raw) {
    const g=catGroup(raw), s=catSpecific(raw);
    return s ? g + ' · ' + s : g;  // "Kalikasan · Baradong kanal"
}
function catStyle(raw)   { return catColors[catGroup(raw)] || {bg:'#f1f5f9',color:'#475569',border:'#e2e8f0'}; }

/* ── Helpers ── */
function fmtDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit'
    });
}

function escapeJs(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

/* ── Report rendering ── */
let openDetailId = null;

function buildDetailHtml(r) {
    const st            = statusConfig[r.status] || statusConfig['pending'];
    const cat           = catStyle(r.category);
    const grp           = catGroup(r.category);
    const specific      = catSpecific(r.category);
    const reporter_disp = parseInt(r.is_anonymous) ? 'Anonymous' : (r.reporter || '');
    const imgHtml       = r.image_path
        ? `<div class="rpt-img-wrap" onclick="openLightbox(event,['${r.image_path.replace(/'/g,"\\'")}'],0)" title="Click to enlarge">
               <img src="${r.image_path}" alt="Report photo" loading="lazy"
                    onerror="this.closest('.rpt-img-wrap').style.display='none'">
               <div class="rpt-img-zoom-hint"><i class="fa fa-search-plus"></i> View full</div>
           </div>` : '';
    const purokHtml = r.purok
        ? `<div style="font-size:11px; color:rgba(255,255,255,0.7); margin-top:2px;">📍 ${r.purok}</div>` : '';

    return `
    <div style="border-top:2px solid ${cat.border}; background:#fff;">
        <div style="background:linear-gradient(135deg,${cat.color}dd 0%,${cat.color} 100%);
                    padding:16px 18px;">
            <div style="font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
                         color:rgba(255,255,255,0.75); margin-bottom:${specific ? '1px' : '4px'};">${grp}</div>
            ${specific ? `<div style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.95);
                         margin-bottom:6px; letter-spacing:.01em;">${specific}</div>` : ''}
            <div style="font-family:'Sora',sans-serif; font-size:16px; font-weight:800;
                         color:#fff; margin-bottom:8px;">${r.title}</div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="font-size:10px; font-weight:700; padding:3px 12px; border-radius:20px;
                             background:rgba(255,255,255,0.2); color:#fff;
                             border:1px solid rgba(255,255,255,0.35);">${st.label}</span>
                <span style="font-size:11px; color:rgba(255,255,255,0.75);">${fmtDate(r.created_at)}</span>
            </div>
            <div style="font-size:11.5px; color:rgba(255,255,255,0.75); margin-top:5px;
                         display:flex; align-items:center; gap:5px;">
                <i class="fa fa-${parseInt(r.is_anonymous) ? 'user-secret' : 'user'}"></i>
                ${reporter_disp}
            </div>
            ${purokHtml}
        </div>
        <div style="padding:16px 18px; background:${cat.bg}08;">
            ${imgHtml}
            <div class="field-group" style="margin-bottom:14px;">
                <label class="field-label">Description</label>
                <p style="font-size:13px; color:var(--text); line-height:1.6; margin:0;">
                    ${r.description || '—'}
                </p>
            </div>
            <div class="field-group">
                <label class="field-label">Update Status</label>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
                    <button type="button" class="rpt-status-btn pending"
                            onclick="askStatusChange(${r.id},'pending','${escapeJs(r.title)}')">
                        <i class="fa fa-clock-o"></i> Pending
                    </button>
                    <button type="button" class="rpt-status-btn in-progress"
                            onclick="askStatusChange(${r.id},'in-progress','${escapeJs(r.title)}')">
                        <i class="fa fa-spinner"></i> In Progress
                    </button>
                    <button type="button" class="rpt-status-btn resolved"
                            onclick="askStatusChange(${r.id},'resolved','${escapeJs(r.title)}')">
                        <i class="fa fa-check"></i> Resolved
                    </button>
                </div>
            </div>
        </div>
    </div>`;
}

function renderReports() {
    const statusVal = document.getElementById('statusFilter').value;
    const catVal    = document.getElementById('catFilter').value;
    const filtered  = reports.filter(r =>
        (statusVal === 'all' || r.status === statusVal) &&
        (catVal    === 'all' || catGroup(r.category) === catVal)
    );
    const list  = document.getElementById('reportsList');
    const empty = document.getElementById('emptyState');

    if (filtered.length === 0) { list.innerHTML = ''; empty.style.display = ''; return; }
    empty.style.display = 'none';

    list.innerHTML = filtered.map(r => {
        const cat           = catStyle(r.category);
        const st            = statusConfig[r.status] || statusConfig['pending'];
        const reporter_disp = parseInt(r.is_anonymous) ? 'Anonymous' : (r.reporter || '');
        const purok_disp    = r.purok ? ` · ${r.purok}` : '';
        const has_img       = r.image_path
            ? '&nbsp;<i class="fa fa-image" style="color:var(--muted);font-size:10px;" title="Has image"></i>' : '';
        const isOpen        = openDetailId === parseInt(r.id);
        const badgeLabel    = catDisplayLabel(r.category);

        return `
        <div class="rpt-inline-card ${isOpen ? 'is-open' : ''}" data-id="${r.id}"
             style="margin-bottom:8px; border-radius:12px;
                    border:1.5px solid ${isOpen ? 'var(--blue-main)' : 'var(--border)'};
                    background:#fff; overflow:hidden; transition:border-color 0.2s;">
            <div style="display:flex; align-items:center; gap:8px; padding:12px 14px; cursor:pointer;"
                 onclick="toggleDetail(${r.id})">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                        <span class="rpt-cat-badge"
                              style="background:${cat.bg}; color:${cat.color}; border-color:${cat.border}">
                            ${badgeLabel}
                        </span>
                        <span class="rpt-status-badge"
                              style="background:${st.bg}; color:${st.color}">${st.label}</span>
                        ${has_img}
                    </div>
                    <div style="font-size:13px; font-weight:700; color:var(--text); margin-bottom:3px;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${r.title}</div>
                    <div style="font-size:11px; color:var(--muted); display:flex;
                                align-items:center; gap:4px; flex-wrap:wrap;">
                        <i class="fa fa-${parseInt(r.is_anonymous) ? 'user-secret' : 'user'}"></i>
                        ${reporter_disp}${purok_disp}
                        &nbsp;·&nbsp;
                        <i class="fa fa-clock-o"></i> ${fmtDate(r.created_at)}
                    </div>
                </div>
                <div style="color:var(--muted); font-size:12px; flex-shrink:0;
                            transition:transform 0.25s;
                            transform:rotate(${isOpen ? '90deg' : '0deg'});">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </div>
            <div id="detail-${r.id}" style="display:${isOpen ? 'block' : 'none'};">
                ${isOpen ? buildDetailHtml(r) : ''}
            </div>
        </div>`;
    }).join('');
}

function toggleDetail(id) {
    id = parseInt(id);
    openDetailId = (openDetailId === id) ? null : id;
    renderReports();
    if (openDetailId) {
        setTimeout(() => {
            const el = document.querySelector(`.rpt-inline-card[data-id="${id}"]`);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);
    }
}

function filterReports() { renderReports(); }

/* ── Status change ── */
let pendingStatusId = null, pendingStatusVal = null;

function askStatusChange(reportId, newStatus, title) {
    pendingStatusId  = reportId;
    pendingStatusVal = newStatus;
    const labels = { pending: 'Pending', 'in-progress': 'In Progress', resolved: 'Resolved' };
    document.getElementById('confirmStatusLabel').textContent = labels[newStatus];
    document.getElementById('confirmReportTitle').textContent = '"' + title + '"';
    document.getElementById('statusConfirmModal').style.display = 'flex';
}

function confirmStatusChange() {
    document.getElementById('formReportIdH').value  = pendingStatusId;
    document.getElementById('formNewStatusH').value = pendingStatusVal;
    document.getElementById('statusFormHidden').submit();
}

/* ── Logout ── */
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
}

/* ── Urgent toggle ── */
let urgentOn = false;
function toggleUrgent() {
    urgentOn = !urgentOn;
    const btn   = document.getElementById('urgentBtn');
    const label = document.getElementById('urgentBtnLabel');
    document.getElementById('urgentHidden').value = urgentOn ? '1' : '0';
    btn.style.background  = urgentOn ? '#c0001a' : '#fff3e0';
    btn.style.color       = urgentOn ? '#fff'    : '#c47200';
    btn.style.borderColor = urgentOn ? '#c0001a' : '#ffd580';
    label.textContent     = urgentOn ? '⚠ Urgent ON' : 'Mark as Urgent';
}

/* ── Unified create modal ── */
function openUnifiedModal() {
    document.getElementById('uStep1').style.display     = '';
    document.getElementById('uStep2Ann').style.display  = 'none';
    document.getElementById('uStep2Prog').style.display = 'none';
    document.getElementById('unifiedModal').classList.add('open');
}
function closeUnifiedModal() {
    document.getElementById('unifiedModal').classList.remove('open');
}
function chooseType(type) {
    document.getElementById('uStep1').style.display = 'none';
    document.getElementById('uStep2Ann').style.display  = (type === 'announcement') ? '' : 'none';
    document.getElementById('uStep2Prog').style.display = (type === 'program')       ? '' : 'none';
}
function backToTypePicker() {
    document.getElementById('uStep1').style.display     = '';
    document.getElementById('uStep2Ann').style.display  = 'none';
    document.getElementById('uStep2Prog').style.display = 'none';
}

/* ── Lightbox ── */
let lbImages = [], lbIndex = 0;

function openLightbox(e, images, startIndex) {
    e.stopPropagation();
    lbImages = Array.isArray(images) ? images : [images];
    lbIndex  = startIndex || 0;
    showLightboxImage();
    document.getElementById('imgLightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', lightboxKeyHandler);
}
function showLightboxImage() {
    const img     = document.getElementById('imgLightboxImg');
    const counter = document.getElementById('imgLightboxCounter');
    img.style.opacity = '0';
    img.onload = () => { img.style.opacity = '1'; };
    img.src = lbImages[lbIndex];
    counter.textContent = lbImages.length > 1 ? `${lbIndex + 1} / ${lbImages.length}` : '';
    document.getElementById('imgLightboxPrev').classList.toggle('hidden', lbIndex === 0);
    document.getElementById('imgLightboxNext').classList.toggle('hidden', lbIndex === lbImages.length - 1);
}
function lightboxNav(dir) {
    lbIndex = Math.max(0, Math.min(lbImages.length - 1, lbIndex + dir));
    showLightboxImage();
}
function lightboxKeyHandler(e) {
    if (e.key === 'ArrowLeft')  lightboxNav(-1);
    if (e.key === 'ArrowRight') lightboxNav(1);
    if (e.key === 'Escape')     closeLightbox();
}
function closeLightbox() {
    document.getElementById('imgLightbox').classList.remove('open');
    document.body.style.overflow = '';
    document.removeEventListener('keydown', lightboxKeyHandler);
    lbImages = []; lbIndex = 0;
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', function () {
    renderReports();
});