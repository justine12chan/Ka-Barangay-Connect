function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
}

const monthlyData  = window.MONTHLY_DATA  || [];
const categoryData = window.CATEGORY_DATA || {};
const statPending  = window.STAT_PENDING  || 0;
const statProgress = window.STAT_PROGRESS || 0;
const statResolved = window.STAT_RESOLVED || 0;
const statPlanned   = window.STAT_PLANNED   || 0;
const statOngoing   = window.STAT_ONGOING   || 0;
const statCompleted = window.STAT_COMPLETED || 0;
const tatMonthly    = window.TAT_MONTHLY    || [];
const avgTatMin     = window.AVG_TAT_MIN    || 0;

const monthLabels = [], pendingArr = [], progressArr = [], resolvedArr = [];
const now = new Date();

const isMobile = window.innerWidth < 640;
const monthCount = isMobile ? 6 : 12;
for (let i = monthCount - 1; i >= 0; i--) {
    const d   = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const ym  = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    const lbl = d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
    monthLabels.push(lbl);
    const found = monthlyData.find(x => x.ym === ym);
    pendingArr.push(found  ? parseInt(found.pending)    : 0);
    progressArr.push(found ? parseInt(found.inprogress) : 0);
    resolvedArr.push(found ? parseInt(found.resolved)   : 0);
}

const CHART_COLORS = {
    pending:    '#f59c23',
    inprogress: '#1a56db',
    resolved:   '#22cc77',
};

let chartInstance = null;

function buildMonthChart() {
    return {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                { label: 'Pending',     data: pendingArr,  backgroundColor: CHART_COLORS.pending,    borderRadius: 4, stack: 'a', maxBarThickness: 28 },
                { label: 'In Progress', data: progressArr, backgroundColor: CHART_COLORS.inprogress, borderRadius: 4, stack: 'a', maxBarThickness: 28 },
                { label: 'Resolved',    data: resolvedArr, backgroundColor: CHART_COLORS.resolved,   borderRadius: 4, stack: 'a', maxBarThickness: 28 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11, family: 'DM Sans' }, boxWidth: 12, padding: 18 } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: isMobile ? 9 : 11 }, maxRotation: 45, minRotation: 0 } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f0f2f8' } }
            }
        }
    };
}

function buildCategoryChart() {
    const labels = Object.keys(categoryData);
    const data   = Object.values(categoryData);
    const colors = ['#0800a0', '#f59c23', '#22cc77', '#c0001a', '#8b00c7', '#1a56db'];
    return {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11, family: 'DM Sans' }, boxWidth: 12, padding: 16 } }
            }
        }
    };
}

function buildStatusChart() {
    return {
        type: 'bar',
        data: {
            labels: ['Pending', 'In Progress', 'Resolved'],
            datasets: [{
                label: 'Reports',
                data: [statPending, statProgress, statResolved],
                backgroundColor: [CHART_COLORS.pending, CHART_COLORS.inprogress, CHART_COLORS.resolved],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + ' reports' } }
            },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f0f2f8' } },
                y: { grid: { display: false }, ticks: { font: { size: 12, weight: '700' } } }
            }
        }
    };
}

function buildProjectsChart() {
    return {
        type: 'bar',
        data: {
            labels: ['Planned', 'Ongoing', 'Completed'],
            datasets: [{
                label: 'Projects',
                data: [statPlanned, statOngoing, statCompleted],
                backgroundColor: ['#8b00c7', '#1a56db', '#22cc77'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + ' project' + (ctx.parsed.x !== 1 ? 's' : '') } }
            },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f0f2f8' } },
                y: { grid: { display: false }, ticks: { font: { size: 12, weight: '700' } } }
            }
        }
    };
}

function buildTurnaroundChart() {
    // Build the last-12-months labels (same as monthly chart)
    const tatLabels = [], tatValues = [];
    const now = new Date();
    const isMobileTat = window.innerWidth < 640;
    const tatCount = isMobileTat ? 6 : 12;
    for (let i = tatCount - 1; i >= 0; i--) {
        const d   = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const ym  = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        const lbl = d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
        tatLabels.push(lbl);
        const found = tatMonthly.find(x => x.ym === ym);
        tatValues.push(found ? parseFloat(found.avg_hours) : null);
    }
    return {
        type: 'bar',
        data: {
            labels: tatLabels,
            datasets: [{
                label: 'Avg. Hours to Resolve',
                data: tatValues,
                backgroundColor: tatValues.map(v =>
                    v === null ? 'transparent' :
                    v <= 24   ? '#22cc77' :
                    v <= 72   ? '#f59c23' : '#c0001a'),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 28,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const h = ctx.parsed.y;
                            if (h === null) return ' No data';
                            const d = Math.floor(h / 24), r = Math.round(h % 24);
                            return d > 0 ? ` ${d}d ${r}h avg` : ` ${Math.round(h)}h avg`;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f2f8' },
                    ticks: {
                        font: { size: 11 },
                        callback: v => v + 'h'
                    },
                    title: { display: true, text: 'Avg. Hours', font: { size: 11 }, color: '#8890b8' }
                }
            }
        }
    };
}

function switchChartView(view, btn) {
    // Update tab buttons
    document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (chartInstance) chartInstance.destroy();
    const ctx = document.getElementById('reportsChart').getContext('2d');
    const cfg = view === 'month'       ? buildMonthChart()
              : view === 'category'    ? buildCategoryChart()
              : view === 'projects'    ? buildProjectsChart()
              : view === 'turnaround'  ? buildTurnaroundChart()
              : buildStatusChart();
    chartInstance = new Chart(ctx, cfg);
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

    fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        redirect: 'follow',
        credentials: 'same-origin'
    })
        .then(res => {
            // If we got redirected to login (or any non-200), bail gracefully
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(html => {
            // Guard: if the response doesn't contain our data variables
            // (e.g. we got the login page after a session timeout), skip silently
            if (!html.includes('window.STAT_PENDING')) {
                hideRefreshIndicator(true); // stay quiet — session expired, not a real error
                return;
            }

            // Extract updated stats
            const pending   = extractStat(html, 'STAT_PENDING');
            const progress  = extractStat(html, 'STAT_PROGRESS');
            const resolved  = extractStat(html, 'STAT_RESOLVED');
            const planned   = extractStat(html, 'STAT_PLANNED');
            const ongoing   = extractStat(html, 'STAT_ONGOING');
            const completed = extractStat(html, 'STAT_COMPLETED');

            // Update hero stat values in the DOM
            if (pending  !== null) updateHeroStat('.stat-hero-card.pending    .stat-hero-value', pending);
            if (progress !== null) updateHeroStat('.stat-hero-card.inprogress .stat-hero-value', progress);
            if (resolved !== null) updateHeroStat('.stat-hero-card.resolved   .stat-hero-value', resolved);

            // Re-render chart with fresh data if on a stat-driven view
            if (pending !== null && progress !== null && resolved !== null) {
                const activeTab = document.querySelector('.chart-tab.active');
                if (activeTab && (activeTab.id === 'tab-status' || activeTab.id === 'tab-projects')) {
                    switchChartView(activeTab.id.replace('tab-', ''), activeTab);
                }
            }

            hideRefreshIndicator(true);
        })
        .catch(err => {
            console.warn('[AutoRefresh] fetch failed:', err);
            hideRefreshIndicator(false);
        })
        .finally(() => { isRefreshing = false; });
}

function extractStat(html, key) {
    const m = html.match(new RegExp('window\\.' + key + '\\s*=\\s*(\\d+);'));
    return m ? parseInt(m[1]) : null;
}
function updateHeroStat(selector, value) {
    const el = document.querySelector(selector);
    if (el) el.textContent = value;
}

function showRefreshIndicator() {
    let ind = document.getElementById('autoRefreshIndicator');
    if (!ind) {
        ind = document.createElement('div');
        ind.id = 'autoRefreshIndicator';
        ind.style.cssText = 'position:fixed; bottom:90px; right:28px; background:#0800a0; color:#fff;' +
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
   Handled by the inline <script> in admin_dashboard.php (toggleAdminDarkMode).
   BroadcastChannel listener is set up there too.
   No localStorage persistence — resets to light on every page load.
   ─────────────────────────────────────────────────────────────── */

window.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('reportsChart').getContext('2d');
    chartInstance = new Chart(ctx, buildMonthChart());
    startAutoRefresh();
});