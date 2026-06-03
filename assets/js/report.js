/* ============================================================
   Ka-Barangay Connect — report.php page scripts
   assets/js/report.js
============================================================ */

/* ── Tab switching, category badge, file select, lightbox ── */
/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.compact-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

/* ── Category badge ── */
function updateCategoryBadge(sel) {
    const group = sel.value.split('|')[0];
    document.querySelectorAll('.cat-badge').forEach(b => b.style.display = 'none');
    const map = {
        'Infrastructure':   'badge-infra',
        'Kalikasan':        'badge-kalikasan',
        'Serbisyo Publiko': 'badge-serbisyo',
        'Publiko':          'badge-publiko',
        'Kapayapaan':       'badge-kapayapaan',
    };
    if (map[group]) document.getElementById(map[group]).style.display = 'inline-block';
}

/* ── File select ── */
function handleFileSelect(input) {
    const label = document.getElementById('fileSelectedLabel');
    if (input.files[0]) {
        label.style.display = 'block';
        label.textContent = 'Selected: ' + input.files[0].name;
    }
}

/* ── Lightbox ── */
function openLightbox(src) {
    const lb = document.getElementById('lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });


/* ── Notification System ── */
(function() {
    let _notifs = [];
    let _open   = false;

    function timeAgo(dateStr) {
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60)   return 'Just now';
        if (diff < 3600) return Math.floor(diff/60)  + 'm ago';
        if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function renderList() {
        const list = document.getElementById('notifList');
        if (!list) return;
        if (_notifs.length === 0) {
            list.innerHTML = '<div class="rnd-empty">No notifications yet</div>';
            return;
        }
        list.innerHTML = _notifs.map(n => {
            const isUnread = n.is_read === '0' || n.is_read === 0;
            const iconType = n.type === 'admin_comment' ? 'type-comment' : 'type-status';
            const iconSvg  = n.type === 'admin_comment'
                ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`
                : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>`;
            return `
            <div class="rnd-item ${isUnread ? 'unread' : ''}" onclick="goToReport(${n.report_id})">
                <div class="rnd-item-icon ${iconType}">${iconSvg}</div>
                <div class="rnd-item-body">
                    <div class="rnd-item-msg">${n.message}</div>
                    <div class="rnd-item-time">${timeAgo(n.created_at)}</div>
                </div>
                ${isUnread ? '<div class="rnd-unread-dot"></div>' : ''}
            </div>`;
        }).join('');
    }

    function updateBadge(count) {
        const badge = document.getElementById('notifBadge');
        const btn   = document.getElementById('notifBtn');
        if (!badge || !btn) return;
        if (count > 0) {
            badge.style.display = 'flex';
            badge.textContent   = count > 99 ? '99+' : count;
            btn.classList.add('has-unread');
        } else {
            badge.style.display = 'none';
            btn.classList.remove('has-unread');
        }
    }

    const STATUS_BADGE = {
        'pending':     { label: 'Pending',     bg: '#fff8e1', color: '#92650a', border: '#f0c040', dot: '#f0c040' },
        'in-progress': { label: 'In Progress', bg: '#fdf0e8', color: '#8b1a1a', border: '#e8cfa0', dot: '#d4a96a' },
        'resolved':    { label: 'Resolved',    bg: '#e6f9ee', color: '#126e40', border: '#72d49a', dot: '#34a853' },
    };

    function updateReportCards(statuses) {
        if (!statuses || typeof statuses !== 'object') return;
        Object.entries(statuses).forEach(([id, status]) => {
            const card = document.getElementById('report-' + id);
            if (!card) return;
            const bi = STATUS_BADGE[status] || STATUS_BADGE['pending'];
            const badge = card.querySelector('.rp-status-badge');
            const dot   = card.querySelector('.rp-status-dot');
            if (badge) {
                badge.style.background   = bi.bg;
                badge.style.color        = bi.color;
                badge.style.borderColor  = bi.border;
                // Update the text node (keep the dot span, replace only text)
                const textNode = [...badge.childNodes].find(n => n.nodeType === 3 && n.textContent.trim());
                if (textNode) textNode.textContent = ' ' + bi.label;
                else badge.lastChild.textContent = ' ' + bi.label;
            }
            if (dot) dot.style.background = bi.dot;
        });
    }

    function fetchNotifs(animate) {
        fetch('report.php?action=get_notifications')
            .then(r => r.json())
            .then(data => {
                const prevUnread = _notifs.filter(n => n.is_read === '0' || n.is_read === 0).length;
                _notifs = data.notifications || [];
                updateBadge(data.unread || 0);
                if (animate && data.unread > prevUnread) {
                    const btn = document.getElementById('notifBtn');
                    if (btn) { btn.classList.remove('has-unread'); void btn.offsetWidth; btn.classList.add('has-unread'); }
                }
                // Live-update status badges on report cards
                if (data.report_statuses) updateReportCards(data.report_statuses);
                if (_open) renderList();
            })
            .catch(() => {});
    }

    window.toggleNotifDropdown = function() {
        const dd = document.getElementById('notifDropdown');
        if (!dd) return;
        _open = !dd.classList.contains('open');
        dd.classList.toggle('open', _open);
        if (_open) renderList();
    };

    window.markAllRead = function(e) {
        if (e) e.stopPropagation();
        fetch('report.php?action=mark_read')
            .then(r => r.json())
            .then(() => {
                _notifs.forEach(n => n.is_read = 1);
                updateBadge(0);
                renderList();
            });
    };

    window.goToReport = function(reportId) {
        switchTab('reports');
        const dd = document.getElementById('notifDropdown');
        if (dd) dd.classList.remove('open');
        _open = false;
        markAllRead();
        setTimeout(() => {
            const el = document.getElementById('report-' + reportId);
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Highlight pulse
            el.style.transition = 'box-shadow 0.3s, border-color 0.3s';
            el.style.boxShadow  = '0 0 0 3px rgba(124,29,29,0.5), 0 12px 36px rgba(0,0,0,0.15)';
            el.style.borderColor = 'var(--primary)';
            setTimeout(() => {
                el.style.boxShadow   = '';
                el.style.borderColor = '';
            }, 2000);
        }, 150);
    };

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('notifWrap');
        if (wrap && !wrap.contains(e.target)) {
            const dd = document.getElementById('notifDropdown');
            if (dd) dd.classList.remove('open');
            _open = false;
        }
    });

    // Initial fetch + poll every 5 seconds
    fetchNotifs(false);
    setInterval(() => fetchNotifs(true), 5000);
})();