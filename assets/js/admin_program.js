const programs = window.PROGRAMS_DATA || [];

const catColors = {
    'Infrastructure':   { bg: '#fff3e0', color: '#c47200', border: '#ffd580' },
    'Kalikasan':        { bg: '#e6faed', color: '#128548', border: '#7de0a4' },
    'Serbisyo Publiko': { bg: '#e8f0fe', color: '#1a56db', border: '#90aef8' },
    'Kapayapaan':       { bg: '#fce8ff', color: '#8b00c7', border: '#d48cf7' },
    'Publiko':          { bg: '#fff0f0', color: '#c0001a', border: '#f7a0aa' },
};

const typeConfig = {
    'announcement': { bg: '#fff8e1', color: '#b45309', border: '#fcd34d', icon: 'fa-bullhorn',  label: 'Announcement' },
    'project':      { bg: '#eff6ff', color: '#1d4ed8', border: '#93c5fd', icon: 'fa-briefcase', label: 'Project' },
};

const statusPill  = { planned: 'pill-planned', ongoing: 'pill-ongoing', completed: 'pill-completed' };
const statusLabel = { planned: 'Planned',       ongoing: 'Ongoing',      completed: 'Completed' };

function fmtDate(str) {
    if (!str) return 'TBD';
    return new Date(str + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function fmtDateTime(str) {
    if (!str) return '';
    return new Date(str).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}
function getCatInitials(cat) {
    if (!cat) return '??';
    const w = cat.split(' ');
    return w.length === 1 ? cat.substring(0, 2).toUpperCase() : (w[0][0] + w[1][0]).toUpperCase();
}

function buildImageGrid(imagePath, progId) {
    if (!imagePath) return '';
    let images = [];
    try {
        const parsed = JSON.parse(imagePath);
        images = Array.isArray(parsed) ? parsed : [imagePath];
    } catch (e) {
        images = imagePath.split(',').map(s => s.trim()).filter(Boolean);
    }
    if (!images.length) return '';

    const MAX_VISIBLE = 4;
    const visible    = images.slice(0, MAX_VISIBLE);
    const extra      = images.length - MAX_VISIBLE;
    const countClass = images.length === 1 ? 'img-count-1'
                     : images.length === 2 ? 'img-count-2'
                     : images.length === 3 ? 'img-count-3'
                     : 'img-count-many';

    const imagesAttr = JSON.stringify(images).replace(/'/g, '&#39;');
    const cells = visible.map((src, idx) => {
        const isLast  = idx === visible.length - 1 && extra > 0;
        const overlay = isLast ? `<div class="prog-img-overlay">+${extra + 1}</div>` : '';
        const safeSrc = src.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        return `<div class="prog-img-cell" data-images='${imagesAttr}' data-index="${idx}" onclick="handleImgClick(event,this)">
                    <img src="${safeSrc}" alt="Program image" loading="lazy"
                         onerror="this.closest('.prog-img-cell').style.display='none'">
                    ${overlay}
                </div>`;
    }).join('');
    return `<div class="prog-social-images ${countClass}">${cells}</div>`;
}

function buildCard(r, isLatest) {
    const postType    = (r.post_type || 'project').toLowerCase();
    const tc          = typeConfig[postType] || typeConfig['project'];
    const cat         = r.department || '';
    const catC        = catColors[cat] || { bg: '#ededff', color: '#0800a0', border: '#c7c8f0' };
    const avatarStyle = `background:${catC.bg}; color:${catC.color}; border-color:${catC.border};`;
    const initials    = getCatInitials(cat);
    const dateStr     = fmtDateTime(r.created_at);
    const imgHtml     = buildImageGrid(r.image_path, r.id);

    const typeBadge = `<span class="prog-status-pill" style="background:${tc.bg}; color:${tc.color};
                        border:1.5px solid ${tc.border}; display:inline-flex; align-items:center; gap:5px;">
        <i class="fa ${tc.icon}" style="font-size:9px;"></i>${tc.label}
    </span>`;

    const statusBadge = (postType === 'project' && r.status && statusPill[r.status])
        ? `<span class="prog-status-pill ${statusPill[r.status]}">${statusLabel[r.status]}</span>`
        : '';

    const latestTag = isLatest
        ? `<div style="padding:2px 16px 8px;">
               <span style="display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:700;
                            color:#1a56db; background:#e8f0fe; border:1px solid #93b4f7; border-radius:20px;
                            padding:2px 10px; letter-spacing:0.03em;">
                   <i class="fa fa-bolt" style="font-size:9px;"></i> Latest Update
               </span>
           </div>` : '';

    const dropdownItems = `<button class="prog-dropdown-item" onclick="openEditModal(event,${r.id})">
               <i class="fa fa-pencil"></i> Edit
           </button>
           <button class="prog-dropdown-item delete-item"
                   data-id="${r.id}" data-type="${postType}" data-title="${(r.title||'').replace(/"/g,'&quot;')}"
                   onclick="openDeleteModal(event,this)">
               <i class="fa fa-trash"></i> Delete
           </button>`;

    return `
    <div class="prog-social-card" data-status="${r.status || ''}" data-type="${postType}"
         data-id="${r.id}" data-date="${r.created_at || ''}">
        <div class="prog-social-header">
            <div class="prog-reporter-avatar" style="${avatarStyle}">${initials}</div>
            <div class="prog-reporter-meta">
                <p class="prog-reporter-name">${r.title}</p>
                <p class="prog-reporter-time">${cat}${dateStr ? ' · ' + dateStr : ''}</p>
            </div>
            ${typeBadge} ${statusBadge}
            <button class="prog-dots-btn" title="More options"
                    onclick="toggleDropdown(event,${r.id})">&#8942;</button>
            <div class="prog-dropdown" id="dropdown-${r.id}">
                ${dropdownItems}
            </div>
        </div>
        ${latestTag}
        <div class="prog-social-body">
            <p class="prog-social-desc">${r.description || ''}</p>
            ${imgHtml}
            <div class="prog-social-meta">
                <span><i class="fa fa-calendar" style="margin-right:4px;"></i>${fmtDate(r.start_date)}</span>
            </div>
        </div>
    </div>`;
}

function renderPrograms() {
    const statusVal = document.getElementById('statusFilter').value;
    const sortVal   = document.getElementById('sortFilter').value;
    const typeVal   = document.getElementById('typeFilter').value;
    const list      = document.getElementById('programsList');
    const empty     = document.getElementById('emptyState');

    let filtered = programs.filter(r =>
        (typeVal   === 'all' || r.post_type === typeVal) &&
        (statusVal === 'all' || r.status    === statusVal)
    );

    filtered.sort((a, b) => {
        const da = new Date(a.created_at || 0), db = new Date(b.created_at || 0);
        return sortVal === 'oldest' ? da - db : db - da;
    });

    if (!filtered.length) { list.innerHTML = ''; empty.style.display = ''; return; }
    empty.style.display = 'none';

    if (statusVal !== 'all') {
        const iconMap = { planned: 'fa-clock-o', ongoing: 'fa-spinner', completed: 'fa-check-circle' };
        const divider = `
        <div style="display:flex; align-items:center; gap:10px; padding:14px 14px 6px;">
            <span class="content-eyebrow" style="display:inline-flex; align-items:center; gap:6px; white-space:nowrap;">
                <i class="fa ${iconMap[statusVal] || 'fa-list'}"></i> ${statusLabel[statusVal] || statusVal}
            </span>
            <div class="content-line"></div>
        </div>`;
        list.innerHTML = divider + filtered.map((r, i) => buildCard(r, i === 0)).join('');
    } else {
        list.innerHTML = filtered.map(r => buildCard(r, false)).join('');
    }
}

function toggleDropdown(e, id) {
    e.stopPropagation();
    const dd     = document.getElementById('dropdown-' + id);
    const isOpen = dd.classList.contains('open');
    document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
    if (!isOpen) dd.classList.add('open');
}
document.addEventListener('click', () => {
    document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
});

function openEditModal(e, id) {
    e.stopPropagation();
    document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
    const r = programs.find(x => parseInt(x.id) === id);
    if (!r) return;
    const isAnn = (r.post_type || 'project') === 'announcement';

    document.getElementById('editFlagProgram').disabled = isAnn;
    document.getElementById('editFlagAnn').disabled     = !isAnn;
    document.getElementById('editId').disabled          = isAnn;
    document.getElementById('editAnnId').disabled       = !isAnn;
    document.getElementById('editId').value             = isAnn ? '' : r.id;
    document.getElementById('editAnnId').value          = isAnn ? r.id : '';

    document.getElementById('editTitle').value = r.title || '';
    document.getElementById('editDept').value  = r.department || '';
    document.getElementById('editDesc').value  = r.description || '';
    document.getElementById('editFileLabel').textContent = 'Keep current image...';
    document.getElementById('editFileInput').value = '';

    document.getElementById('editProgFields').style.display = isAnn ? 'none' : '';
    if (!isAnn) {
        document.getElementById('editStatus').value    = r.status || 'planned';
        document.getElementById('editStartDate').value = r.start_date ? r.start_date.substring(0, 10) : '';
    }

    document.getElementById('editModalTitle').innerHTML =
        `<i class="fa fa-pencil" style="color:var(--blue-main); margin-right:8px;"></i>Edit ${isAnn ? 'Announcement' : 'Program'}`;
    document.getElementById('editDeptLabel').textContent = isAnn ? 'Posted By' : 'Department';
    document.getElementById('editDescLabel').textContent = isAnn ? 'Message' : 'Description';

    document.getElementById('editConfirmModal').classList.add('open');
}
function closeEditModal()  { document.getElementById('editConfirmModal').classList.remove('open'); }
function showSaveConfirm() {
    if (!document.getElementById('editTitle').value.trim()) { alert('Title cannot be empty.'); return; }
    document.getElementById('editSaveConfirm').classList.add('open');
}
function closeSaveConfirm() { document.getElementById('editSaveConfirm').classList.remove('open'); }

let pendingDeleteId   = null;
let pendingDeleteType = 'project';

function openDeleteModal(e, btn) {
    e.stopPropagation();
    document.querySelectorAll('.prog-dropdown.open').forEach(d => d.classList.remove('open'));
    pendingDeleteId   = btn.getAttribute('data-id');
    pendingDeleteType = btn.getAttribute('data-type') || 'project';
    const title       = btn.getAttribute('data-title') || 'this item';
    document.getElementById('deleteProgramTitle').textContent = '"' + title + '"';
    document.getElementById('deleteConfirmModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.remove('open');
}
function confirmDelete() {
    if (pendingDeleteType === 'announcement') {
        document.getElementById('deleteAnnId').value = pendingDeleteId;
        document.getElementById('deleteAnnFormHidden').submit();
    } else {
        document.getElementById('deleteProgramId').value = pendingDeleteId;
        document.getElementById('deleteFormHidden').submit();
    }
}

let lbImages = [], lbIndex = 0;

function handleImgClick(e, el) {
    e.stopPropagation();
    try {
        const images = JSON.parse(el.getAttribute('data-images'));
        const index  = parseInt(el.getAttribute('data-index')) || 0;
        openLightbox(e, images, index);
    } catch(err) { console.error('Lightbox error:', err); }
}

function openLightbox(e, images, startIndex) {
    e.stopPropagation();
    lbImages = Array.isArray(images) ? images : [images];
    lbIndex  = startIndex || 0;
    showLightboxImage();
    document.getElementById('imgLightbox').classList.add('open');
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
    document.removeEventListener('keydown', lightboxKeyHandler);
    lbImages = []; lbIndex = 0;
}

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
}

function openUnifiedModal() {
    document.getElementById('uStep1').style.display     = '';
    document.getElementById('uStep2Ann').style.display  = 'none';
    document.getElementById('uStep2Prog').style.display = 'none';
    document.getElementById('unifiedModal').classList.add('open');
}
function closeUnifiedModal() { document.getElementById('unifiedModal').classList.remove('open'); }
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

/* ── Auto-refresh via AJAX (every 30 seconds) ── */
let isRefreshing = false;

function startAutoRefresh() {
    setInterval(fetchLatestData, 30000);
}

function fetchLatestData() {
    if (isRefreshing) return;
    isRefreshing = true;
    showRefreshIndicator();

    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.text())
        .then(html => {
            const newPrograms = extractGlobal(html, 'PROGRAMS_DATA');
            if (newPrograms !== null) {
                programs.length = 0;
                newPrograms.forEach(p => programs.push(p));
                renderPrograms();
            }
            hideRefreshIndicator(true);
        })
        .catch(() => hideRefreshIndicator(false))
        .finally(() => { isRefreshing = false; });
}

function extractGlobal(html, key) {
    const m = html.match(new RegExp('window\\.' + key + '\\s*=\\s*(\\[.*?\\]);', 's'));
    if (!m) return null;
    try { return JSON.parse(m[1]); } catch(e) { return null; }
}

function showRefreshIndicator() {
    let ind = document.getElementById('autoRefreshIndicator');
    if (!ind) {
        ind = document.createElement('div');
        ind.id = 'autoRefreshIndicator';
        ind.style.cssText = 'position:fixed; bottom:90px; right:28px; background:var(--blue-main); color:#fff;' +
            'font-family:"Sora",sans-serif; font-size:12px; font-weight:700; padding:7px 14px;' +
            'border-radius:20px; z-index:8999; display:flex; align-items:center; gap:7px;' +
            'box-shadow:0 4px 14px rgba(8,0,160,.3); opacity:0; transition:opacity .3s;';
        ind.innerHTML = '<i class="fa fa-refresh fa-spin"></i> Updating…';
        document.body.appendChild(ind);
    }
    setTimeout(() => { ind.style.opacity = '1'; }, 10);
}
function hideRefreshIndicator(success) {
    const ind = document.getElementById('autoRefreshIndicator');
    if (!ind) return;
    ind.innerHTML = success
        ? '<i class="fa fa-check"></i> Up to date'
        : '<i class="fa fa-exclamation-triangle"></i> Refresh failed';
    if (!success) ind.style.background = '#c0001a';
    setTimeout(() => {
        ind.style.opacity = '0';
        setTimeout(() => { if (ind.parentNode) ind.parentNode.removeChild(ind); }, 400);
    }, 1800);
}

/* ── Dark Mode ──
   Handled by the inline <script> in admin_program.php (toggleAdminDarkMode).
   BroadcastChannel listener is set up there too.
   No localStorage persistence — resets to light on every page load.
   ─────────────────────────────────────────────────────────────── */

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
}

document.addEventListener('DOMContentLoaded', function () {
    renderPrograms();
    startAutoRefresh();
});