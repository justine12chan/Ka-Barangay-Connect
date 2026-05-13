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

const monthLabels = [], pendingArr = [], progressArr = [], resolvedArr = [];
const now = new Date();

for (let i = 11; i >= 0; i--) {
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
                { label: 'Pending',     data: pendingArr,  backgroundColor: CHART_COLORS.pending,    borderRadius: 4, stack: 'a' },
                { label: 'In Progress', data: progressArr, backgroundColor: CHART_COLORS.inprogress, borderRadius: 4, stack: 'a' },
                { label: 'Resolved',    data: resolvedArr, backgroundColor: CHART_COLORS.resolved,   borderRadius: 4, stack: 'a' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11, family: 'DM Sans' }, boxWidth: 12, padding: 18 } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
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

function switchChartView(view, btn) {
    // Update tab buttons
    document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (chartInstance) chartInstance.destroy();
    const ctx = document.getElementById('reportsChart').getContext('2d');
    const cfg = view === 'month'    ? buildMonthChart()
              : view === 'category' ? buildCategoryChart()
              : view === 'projects' ? buildProjectsChart()
              : buildStatusChart();
    chartInstance = new Chart(ctx, cfg);
}

function refreshPage() {
    const btn = document.getElementById('refreshBtn');
    btn.classList.add('spinning');
    window.location.reload();
}

/* ── Dark Mode ── */
function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    const icon   = document.getElementById('darkModeIcon');
    if (icon) {
        icon.className = isDark ? 'fa fa-sun-o' : 'fa fa-moon-o';
    }
    localStorage.setItem('kbc_dark_mode', isDark ? '1' : '0');
}

window.addEventListener('DOMContentLoaded', () => {
    // Restore dark mode preference
    if (localStorage.getItem('kbc_dark_mode') === '1') {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = 'fa fa-sun-o';
    }

    const ctx = document.getElementById('reportsChart').getContext('2d');
    chartInstance = new Chart(ctx, buildMonthChart());
});