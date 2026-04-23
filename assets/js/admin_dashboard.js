/* ============================================================
   Ka-Barangay Connect — Admin Dashboard
   assets/js/admin_dashboard.js
   ============================================================ */

/* ── Data (injected by PHP via window globals before this script loads) ──
   Expected globals set inline in admin_dashboard.php:
     window.MONTHLY_DATA   = <?= json_encode($monthly_data) ?>;
     window.CATEGORY_DATA  = <?= json_encode($category_data) ?>;
     window.STAT_PENDING   = <?= $r_pending ?>;
     window.STAT_PROGRESS  = <?= $r_progress ?>;
     window.STAT_RESOLVED  = <?= $r_resolved ?>;
   ──────────────────────────────────────────────────────────── */

/* ── Logout ── */
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
    return false;
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
    document.getElementById('uStep1').style.display     = 'none';
    document.getElementById('uStep2Ann').style.display  = (type === 'announcement') ? '' : 'none';
    document.getElementById('uStep2Prog').style.display = (type === 'program')       ? '' : 'none';
}
function backToTypePicker() {
    document.getElementById('uStep1').style.display     = '';
    document.getElementById('uStep2Ann').style.display  = 'none';
    document.getElementById('uStep2Prog').style.display = 'none';
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

/* ── Chart setup ── */
const monthlyData  = window.MONTHLY_DATA  || [];
const categoryData = window.CATEGORY_DATA || {};
const statPending  = window.STAT_PENDING  || 0;
const statProgress = window.STAT_PROGRESS || 0;
const statResolved = window.STAT_RESOLVED || 0;

const monthLabels = [], pendingArr = [], progressArr = [], resolvedArr = [];
const now = new Date();

for (let i = 11; i >= 0; i--) {
    const d   = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const ym  = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    const lbl = d.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
    monthLabels.push(lbl);
    const found = monthlyData.find(x => x.ym === ym);
    pendingArr.push(found  ? parseInt(found.pending)     : 0);
    progressArr.push(found ? parseInt(found.inprogress)  : 0);
    resolvedArr.push(found ? parseInt(found.resolved)    : 0);
}

let chartInstance = null;

function buildMonthChart() {
    return {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                { label: 'Pending',     data: pendingArr,  backgroundColor: '#f59c23', borderRadius: 4, stack: 'a' },
                { label: 'In Progress', data: progressArr, backgroundColor: '#3b7ef8', borderRadius: 4, stack: 'a' },
                { label: 'Resolved',    data: resolvedArr, backgroundColor: '#22cc77', borderRadius: 4, stack: 'a' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    };
}

function buildCategoryChart() {
    const labels = Object.keys(categoryData);
    const data   = Object.values(categoryData);
    const colors = ['#3b7ef8', '#f59c23', '#22cc77', '#c0001a', '#8b00c7'];
    return {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors.slice(0, labels.length), borderWidth: 2 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } }
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
                backgroundColor: ['#f59c23', '#3b7ef8', '#22cc77'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } }, y: { grid: { display: false } } }
        }
    };
}

function switchChartView(view, btn) {
    document.querySelectorAll('.chart-view-btn').forEach(b => {
        b.style.background = 'var(--faint)';
        b.style.color      = 'var(--muted)';
    });
    btn.style.background = 'var(--blue-main)';
    btn.style.color      = '#fff';
    if (chartInstance) chartInstance.destroy();
    const ctx = document.getElementById('reportsChart').getContext('2d');
    const cfg = view === 'month'    ? buildMonthChart()
              : view === 'category' ? buildCategoryChart()
              : buildStatusChart();
    chartInstance = new Chart(ctx, cfg);
}

/* ── Init ── */
window.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('reportsChart').getContext('2d');
    chartInstance = new Chart(ctx, buildMonthChart());
});