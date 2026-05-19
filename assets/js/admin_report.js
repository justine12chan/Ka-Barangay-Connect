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

/* ── Date filter state ── */
let activeDateFilter = 'all';   // 'all' | 'today' | 'YYYY-MM-DD'

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
        ? `<div style="font-size:11px; color:rgba(255,255,255,0.7); margin-top:2px;"><i class="fa fa-map-marker" style="margin-right:3px;"></i>${r.purok}</div>` : '';

    // Build comments HTML
    const comments = r.comments || [];
    const commentsHtml = comments.length === 0
        ? `<p style="font-size:12.5px; color:var(--muted); margin:0 0 14px;">No comments yet.</p>`
        : comments.map(c => {
            const isAdmin = parseInt(c.is_admin);
            const name    = isAdmin ? (c.commenter_name || 'Barangay Admin') : (c.resident_name || 'Resident');
            const initStr = isAdmin ? 'BA' : name.split(' ').map(w=>w[0]||'').join('').toUpperCase().slice(0,2);
            const dt      = c.created_at ? new Date(c.created_at).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit'}) : '';
            return `
            <div style="display:flex; gap:9px; margin-bottom:12px;">
                <div style="width:30px; height:30px; border-radius:50%; flex-shrink:0;
                            background:${isAdmin ? 'var(--blue-main)' : '#e2e3f0'};
                            color:${isAdmin ? '#f5cc00' : 'var(--gray-600)'};
                            font-size:11px; font-weight:700;
                            display:flex; align-items:center; justify-content:center;
                            font-family:'Sora',sans-serif;">${initStr}</div>
                <div style="flex:1; background:${isAdmin ? '#f0f1ff' : '#f8f9fc'};
                            border:1px solid ${isAdmin ? 'rgba(8,0,160,.15)' : 'var(--border)'};
                            border-radius:0 10px 10px 10px; padding:8px 12px;">
                    <p style="font-size:11.5px; font-weight:700; color:var(--blue-main); margin:0 0 3px;">
                        ${name}
                        ${isAdmin ? '<span style="display:inline-block;font-size:9px;font-weight:800;background:var(--blue-main);color:#f5cc00;padding:1px 7px;border-radius:20px;margin-left:5px;text-transform:uppercase;letter-spacing:.05em;">Admin</span>' : ''}
                    </p>
                    <p style="font-size:12.5px; color:var(--text); margin:0; line-height:1.55;">${c.comment_text || ''}</p>
                    <span style="font-size:10px; color:var(--muted); margin-top:4px; display:block;">${dt}</span>
                </div>
            </div>`;
        }).join('');

    return `
    <div style="border-top:2px solid ${cat.border}; background:#fff;">
        <div style="background:linear-gradient(135deg,${cat.color}dd 0%,${cat.color} 100%);
                    padding:16px 18px;">
            <div style="font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
                         color:rgba(255,255,255,0.75); margin-bottom:${specific ? '1px' : '4px'};">${grp}</div>
            ${specific ? `<div style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.95);
                         margin-bottom:6px; letter-spacing:.01em;">${specific}</div>` : ''}
            <div style="font-family:'Sora',sans-serif; font-size:26px; font-weight:800;
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
            <div class="field-group" style="margin-bottom:14px;">
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

            <!-- Comments thread -->
            <div style="border-top:1px solid var(--border); padding-top:14px; margin-top:4px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                    <span style="font-size:11px; font-weight:800; text-transform:uppercase;
                                 letter-spacing:.08em; color:var(--gray-600);">Comments</span>
                    <span style="font-size:10px; font-weight:700; color:var(--muted);
                                 background:var(--gray-200); border-radius:20px; padding:1px 8px;">${comments.length}</span>
                </div>
                ${commentsHtml}

                <!-- Admin reply form -->
                <form method="POST" action="admin_report.php"
                      style="display:flex; gap:8px; margin-top:8px;">
                    <input type="hidden" name="admin_comment" value="1">
                    <input type="hidden" name="report_id"    value="${r.id}">
                    <input type="text" name="comment_text"
                           placeholder="Reply as Barangay Admin..."
                           required
                           style="flex:1; padding:9px 14px; border:1.5px solid var(--border);
                                  border-radius:10px; font-size:13px; color:var(--text);
                                  background:var(--white); outline:none; font-family:'DM Sans',sans-serif;
                                  transition:border-color .18s;"
                           onfocus="this.style.borderColor='var(--blue-main)'"
                           onblur="this.style.borderColor='var(--border)'">
                    <button type="submit"
                            style="padding:9px 16px; background:var(--blue-main); color:#fff;
                                   font-weight:700; font-size:12.5px; border:none; border-radius:10px;
                                   cursor:pointer; white-space:nowrap; font-family:'Sora',sans-serif;
                                   transition:background .18s;"
                            onmouseover="this.style.background='#0a00c7'"
                            onmouseout="this.style.background='var(--blue-main)'">
                        <i class="fa fa-paper-plane"></i> Reply
                    </button>
                </form>
            </div>
        </div>
    </div>`;
}

function renderReports() {
    const statusVal = document.getElementById('statusFilter').value;
    const catVal    = document.getElementById('catFilter').value;
    const filtered  = reports.filter(r => {
        const statusOk = statusVal === 'all' || r.status === statusVal;
        const catOk    = catVal    === 'all' || catGroup(r.category) === catVal;
        let dateOk = true;
        if (activeDateFilter && activeDateFilter !== 'all' && activeDateFilter !== 'pick') {
            if (!r.created_at) { dateOk = false; }
            else {
                const rd  = new Date(r.created_at);
                const now = new Date();
                if (activeDateFilter === 'today') {
                    dateOk = rd.toDateString() === now.toDateString();
                } else if (activeDateFilter === 'week') {
                    const startOfWeek = new Date(now);
                    startOfWeek.setDate(now.getDate() - now.getDay());
                    startOfWeek.setHours(0,0,0,0);
                    dateOk = rd >= startOfWeek;
                } else if (activeDateFilter === 'month') {
                    dateOk = rd.getMonth() === now.getMonth() && rd.getFullYear() === now.getFullYear();
                } else {
                    // specific date string YYYY-MM-DD
                    const rStr = rd.getFullYear() + '-' + String(rd.getMonth()+1).padStart(2,'0') + '-' + String(rd.getDate()).padStart(2,'0');
                    dateOk = rStr === activeDateFilter;
                }
            }
        }
        return statusOk && catOk && dateOk;
    });
    const list  = document.getElementById('reportsList');
    const empty = document.getElementById('emptyState');

    if (filtered.length === 0) {
        list.innerHTML = '';
        const msg = document.getElementById('emptyStateMsg');
        if (msg) {
            const emptyMsgs = {
                'today': 'No reports submitted today.',
                'week':  'No reports this week.',
                'month': 'No reports this month.',
            };
            msg.textContent = emptyMsgs[activeDateFilter]
                || (activeDateFilter && activeDateFilter !== 'all' && activeDateFilter !== 'pick'
                    ? 'No reports found for this date.'
                    : 'No reports match the selected filter.');
        }
        empty.style.display = '';
        return;
    }
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
                    <div style="font-family:'Sora',sans-serif; font-size:20px; font-weight:700; color:var(--text); margin-bottom:3px;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${r.title}</div>
                    <div style="font-size:14px; color:var(--muted); display:flex;
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


function applyDateFilter(mode) {
    const label    = document.getElementById('activeDateLabel');
    const labelTxt = document.getElementById('activeDateText');
    const title    = document.getElementById('reportsListTitle');
    const sel      = document.getElementById('dateFilter');

    if (mode === 'pick') {
        openCalDropdown();
        return;
    }

    activeDateFilter = mode;
    closeCalDropdown();
    if (label) label.style.display = 'none';

    const titleMap = {
        'all':   'All Reports',
        'today': "Today's Reports",
        'week':  "This Week's Reports",
        'month': "This Month's Reports",
    };
    if (title) title.textContent = titleMap[mode] || 'All Reports';

    renderReports();
    renderCalendar();
}

// Called from calendar day click
function setDateTab(dateStr) {
    activeDateFilter = dateStr;
    const label    = document.getElementById('activeDateLabel');
    const labelTxt = document.getElementById('activeDateText');
    const title    = document.getElementById('reportsListTitle');
    const sel      = document.getElementById('dateFilter');

    closeCalDropdown();

    const d = new Date(dateStr + 'T00:00:00');
    const formatted = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    if (label)    label.style.display = 'flex';
    if (labelTxt) labelTxt.textContent = formatted;
    if (title)    title.textContent = 'Reports — ' + formatted;
    if (sel)      sel.value = 'pick';

    renderReports();
    renderCalendar();
}

/* ── Calendar ── */
let calYear, calMonth;

/* ── Calendar dropdown ── */
let calDropdownOpen = false;

function toggleCalDropdown() {
    calDropdownOpen ? closeCalDropdown() : openCalDropdown();
}
function openCalDropdown() {
    const dd  = document.getElementById('calDropdown');
    const sel = document.getElementById('dateFilter');
    if (!dd) return;
    // Position under the select
    if (sel) {
        const rect = sel.getBoundingClientRect();
        dd.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
        dd.style.left = rect.left + 'px';
        dd.style.position = 'absolute';
    }
    dd.style.display = 'block';
    calDropdownOpen = true;
    renderCalendar();
}
function closeCalDropdown() {
    const dd = document.getElementById('calDropdown');
    const ch = document.getElementById('calChevron');
    if (dd) { dd.style.display = 'none'; calDropdownOpen = false; }
    if (ch) ch.style.transform = 'rotate(0deg)';
}
// Close on outside click
document.addEventListener('click', function(e) {
    const dd  = document.getElementById('calDropdown');
    const sel = document.getElementById('dateFilter');
    if (calDropdownOpen && dd && !dd.contains(e.target) && (!sel || !sel.contains(e.target))) {
        closeCalDropdown();
        // Reset select back to activeDateFilter option if calendar closed without picking
        if (sel) {
            const validOpts = ['all','today','week','month','pick'];
            sel.value = validOpts.includes(activeDateFilter) ? activeDateFilter : 'pick';
        }
    }
});

function calInit() {
    const now = new Date();
    calYear  = now.getFullYear();
    calMonth = now.getMonth();
    renderCalendar();
}

function calNav(dir) {
    calMonth += dir;
    if (calMonth > 11) { calMonth = 0;  calYear++; }
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    renderCalendar();
}

function getReportDatesSet() {
    const set = new Set();
    reports.forEach(r => {
        if (r.created_at) {
            const d = new Date(r.created_at);
            set.add(d.getFullYear() + '-' +
                    String(d.getMonth()+1).padStart(2,'0') + '-' +
                    String(d.getDate()).padStart(2,'0'));
        }
    });
    return set;
}

function renderCalendar() {
    const grid  = document.getElementById('calGrid');
    const label = document.getElementById('calMonthLabel');
    if (!grid || !label) return;

    const now         = new Date();
    const todayStr    = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
    const reportDates = getReportDatesSet();
    const monthNames  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const dayNames    = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    label.textContent = monthNames[calMonth] + ' ' + calYear;

    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();

    let html = dayNames.map(d =>
        `<div style="font-size:10px; font-weight:700; color:#8890b8; padding:3px 0;">${d}</div>`
    ).join('');

    // Empty cells before first day
    for (let i = 0; i < firstDay; i++) {
        html += `<div></div>`;
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
        const hasReports = reportDates.has(dateStr);
        const isToday    = dateStr === todayStr;
        const isSelected = activeDateFilter === dateStr;

        const classes = ['cal-day',
            hasReports ? 'has-reports' : '',
            isToday    ? 'is-today'    : '',
            isSelected ? 'is-selected' : ''
        ].filter(Boolean).join(' ');

        const dotColor = isSelected ? 'rgba(255,255,255,0.75)' : (isToday ? '#f59c23' : '#1a56db');
        const dot = hasReports
            ? `<div style="width:5px;height:5px;border-radius:50%;background:${dotColor};margin:1px auto 0;"></div>`
            : `<div style="height:6px;"></div>`;

        const opacity = hasReports ? '1' : '0.38';
        const color   = (!isSelected && !isToday) ? 'var(--text)' : '';

        html += `<div class="${classes}"
                      onclick="calSelectDay('${dateStr}')"
                      title="${hasReports ? 'Has reports · Click to filter' : 'No reports'}"
                      style="opacity:${opacity}; ${color ? 'color:'+color+';' : ''}">
                     ${day}${dot}
                 </div>`;
    }

    grid.innerHTML = html;
}

function calSelectDay(dateStr) {
    const reportDates = getReportDatesSet();
    if (!reportDates.has(dateStr)) return;
    if (activeDateFilter === dateStr) { setDateTab('all'); return; }
    setDateTab(dateStr);  // closeCalDropdown() is called inside setDateTab
}

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
    calInit();
    startAutoRefresh();
    // Restore dark mode icon state (body class already set by inline script)
    if (localStorage.getItem('kbc_dark_mode') === '1') {
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = 'fa fa-sun-o';
    }
});
/* ── Dark Mode ── */
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    const icon   = document.getElementById('darkModeIcon');
    if (icon) icon.className = isDark ? 'fa fa-sun-o' : 'fa fa-moon-o';
    localStorage.setItem('kbc_dark_mode', isDark ? '1' : '0');
}

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
}

/* ── Auto-refresh via AJAX (every 30 seconds) ── */
let autoRefreshTimer = null;
let isRefreshing     = false;

function startAutoRefresh() {
    autoRefreshTimer = setInterval(fetchLatestData, 30000);
}

function fetchLatestData() {
    if (isRefreshing) return;
    isRefreshing = true;
    showRefreshIndicator();

    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc    = parser.parseFromString(html, 'text/html');

            // Pull fresh data from the new page's inline script globals
            const scripts = doc.querySelectorAll('script:not([src])');
            scripts.forEach(s => {
                const t = s.textContent;
                if (t.includes('REPORTS_DATA'))  {
                    try { eval(t.replace(/window\./g, 'window.')); } catch(e) {}
                }
            });

            // Re-inject fresh reports + stats
            const newReports = extractGlobal(html, 'REPORTS_DATA');
            const newPending = extractStat(html, 'STAT_PENDING');
            const newProg    = extractStat(html, 'STAT_PROGRESS');
            const newRes     = extractStat(html, 'STAT_RESOLVED');

            if (newReports !== null) {
                reports.length = 0;
                newReports.forEach(r => reports.push(r));
                renderReports();
            }

            // Update stat boxes
            if (newPending !== null) updateStatBox('stat-pending',  newPending);
            if (newProg    !== null) updateStatBox('stat-progress', newProg);
            if (newRes     !== null) updateStatBox('stat-resolved', newRes);

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
function extractStat(html, key) {
    const m = html.match(new RegExp('window\\.' + key + '\\s*=\\s*(\\d+);'));
    return m ? parseInt(m[1]) : null;
}
function updateStatBox(cls, value) {
    const el = document.querySelector('.' + cls + ' .stat-num');
    if (el) el.textContent = value;
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
        setTimeout(() => {
            if (ind.parentNode) ind.parentNode.removeChild(ind);
            if (!success) ind.style.background = 'var(--blue-main)';
        }, 400);
    }, 1800);
}

/* ── Unified Modal (same as program page) ── */
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