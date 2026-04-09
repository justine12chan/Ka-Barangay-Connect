<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ka-Barangay Connect — Programs</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="page-official">

<?php
require_once 'connection.php';

// ── Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_program_status'])) {
    $id     = (int)$_POST['program_id'];
    $status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $allowed = ['planned', 'ongoing', 'completed'];
    if (in_array($status, $allowed) && $id > 0) {
        executeQuery("UPDATE programs SET status='$status' WHERE id=$id");
    }
    header("Location: admin_program.php");
    exit;
}

// ── Handle add new program (with image upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $title      = mysqli_real_escape_string($conn, trim($_POST['title']       ?? ''));
    $dept       = mysqli_real_escape_string($conn, trim($_POST['department']  ?? ''));
    $category   = mysqli_real_escape_string($conn, trim($_POST['category']    ?? ''));
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : 'NULL';
    $image_path = null;

    if (!empty($_FILES['prog_image']['name'])) {
        $upload_dir = 'assets/img/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['prog_image']['name'], PATHINFO_EXTENSION));
        $filename = 'prog_' . time() . '_' . rand(100,999) . '.' . $ext;
        $target   = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['prog_image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }

    if ($title && $dept && $category && $desc) {
        $img_esc = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
        executeQuery("INSERT INTO programs (title, department, category, description, status, start_date, image_path)
                      VALUES ('$title','$dept','$category','$desc','planned',$start_date,$img_esc)");
    }
    header("Location: admin_program.php");
    exit;
}

// ── Stats
$p_planned   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='planned'"))[0]    ?? 0;
$p_ongoing   = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='ongoing'"))[0]    ?? 0;
$p_completed = mysqli_fetch_row(executeQuery("SELECT COUNT(*) FROM programs WHERE status='completed'"))[0]  ?? 0;

// ── Fetch all programs
$result   = executeQuery("SELECT * FROM programs ORDER BY created_at DESC");
$programs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $programs[] = $row;
}
?>

    <div class="container-fluid p-3 p-md-4">
        <div class="row g-3">

            <!-- SIDEBAR -->
            <div class="col-12 col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-top">
                        <div class="sidebar-logo-wrap">
                            <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
                        </div>
                        <div>
                            <div class="sidebar-admin">Admin Panel</div>
                            <div class="sidebar-name" id="sidebarUsername">San Bartolome</div>
                        </div>
                    </div>
                    <div class="sidebar-footer">
                        <a href="#" class="sidebar-btn profile"><i class="fa fa-user"></i> Profile</a>
                        <a href="index.html" class="sidebar-btn logout" onclick="return confirmLogout()"><i class="fa fa-sign-out"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-12 col-lg-9">
                <div class="main-card">

                    <!-- Top Nav -->
                    <div class="top-nav">
                        <div class="top-nav-left">
                            <a href="official.php" class="back-btn light">&#8592; Back</a>
                            <span class="top-nav-title">Programs Overview</span>
                        </div>
                        <div class="top-nav-pills">
                            <a href="official.php"      class="nav-pill">Dashboard</a>
                            <a href="report_admin.php"  class="nav-pill">Report</a>
                            <a href="admin_program.php" class="nav-pill active">Programs</a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="stats-row">
                        <div class="stat-box" style="border-left-color:#7c3aed;">
                            <div class="stat-label">Planned</div>
                            <div class="stat-num" style="color:#7c3aed;"><?= $p_planned ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:var(--blue-main);">
                            <div class="stat-label">Ongoing</div>
                            <div class="stat-num"><?= $p_ongoing ?></div>
                        </div>
                        <div class="stat-box" style="border-left-color:#22cc77;">
                            <div class="stat-label">Completed</div>
                            <div class="stat-num" style="color:#22cc77;"><?= $p_completed ?></div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="content-area" style="padding:0;">
                        <div class="rpt-split">

                            <!-- LEFT: List panel -->
                            <div class="rpt-split-list" id="splitList">
                                <div style="padding:16px 16px 10px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;border-bottom:1px solid var(--border);">
                                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
                                        <span class="content-eyebrow">Programs</span>
                                        <div class="content-line"></div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                        <select class="rpt-filter-select" id="statusFilter" onchange="filterPrograms()">
                                            <option value="all">All Status</option>
                                            <option value="planned">Planned</option>
                                            <option value="ongoing">Ongoing</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                        <select class="rpt-filter-select" id="catFilter" onchange="filterPrograms()">
                                            <option value="all">All Departments</option>
                                            <option value="Health">Health</option>
                                            <option value="Education">Education</option>
                                            <option value="Public Works">Public Works</option>
                                            <option value="Social Welfare">Social Welfare</option>
                                            <option value="Peace & Order">Peace &amp; Order</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="padding:14px;">
                                    <div id="programsList"></div>
                                    <div class="content-empty" id="emptyState" style="display:none;">
                                        <div class="content-empty-icon"><i class="fa fa-briefcase"></i></div>
                                        <p>No programs match the selected filter.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT: Detail panel -->
                            <div class="rpt-split-detail" id="splitDetail">
                                <div class="rpt-detail-empty" id="detailEmpty">
                                    <div class="content-empty-icon" style="width:52px;height:52px;margin:0 auto 12px;">
                                        <i class="fa fa-hand-o-left" style="font-size:22px;"></i>
                                    </div>
                                    <p>Select a program to view details</p>
                                </div>
                                <div class="rpt-detail-content" id="detailContent" style="display:none;">
                                    <div class="rpt-detail-header">
                                        <div class="rpt-modal-eyebrow" id="detailCategory"></div>
                                        <div class="rpt-detail-htitle" id="detailTitle"></div>
                                        <div class="rpt-detail-meta" style="margin-top:8px;">
                                            <span class="rpt-detail-badge" id="detailStatus"></span>
                                            <span class="rpt-detail-date" id="detailDate"></span>
                                        </div>
                                        <div class="rpt-detail-reporter" id="detailDept" style="margin-top:4px;font-size:11.5px;color:rgba(255,255,255,0.6);display:flex;align-items:center;gap:5px;"></div>
                                    </div>
                                    <div style="padding:16px;">
                                        <!-- Image -->
                                        <div id="detailImageWrap" style="display:none;margin-bottom:14px;">
                                            <img id="detailImage" src="" alt="Program image" style="width:100%;border-radius:10px;max-height:220px;object-fit:cover;border:1.5px solid var(--border);">
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label">Description</label>
                                            <p class="rpt-detail-desc" id="detailDesc"></p>
                                        </div>
                                        <div class="field-group" style="margin-top:14px;">
                                            <label class="field-label">Update Status</label>
                                            <form method="POST" action="admin_program.php" id="statusForm">
                                                <input type="hidden" name="update_program_status" value="1">
                                                <input type="hidden" name="program_id"  id="formProgramId" value="">
                                                <input type="hidden" name="new_status"  id="formNewStatus"  value="">
                                                <div class="rpt-status-row">
                                                    <button type="button" class="rpt-status-btn" style="background:#f3e8ff;color:#7c3aed;border-color:#c4b5fd;" onclick="submitStatus('planned')">
                                                        <i class="fa fa-calendar"></i> Planned
                                                    </button>
                                                    <button type="button" class="rpt-status-btn in-progress" onclick="submitStatus('ongoing')">
                                                        <i class="fa fa-spinner"></i> Ongoing
                                                    </button>
                                                    <button type="button" class="rpt-status-btn resolved" onclick="submitStatus('completed')">
                                                        <i class="fa fa-check"></i> Completed
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- FAB: Add Program -->
    <button class="rpt-fab" title="Add Program" onclick="document.getElementById('postModal').classList.add('open')">
        <i class="fa fa-plus"></i>
    </button>

    <!-- Add Program Modal -->
    <div class="rpt-modal-overlay" id="postModal" onclick="if(event.target===this)this.classList.remove('open')">
        <div class="rpt-modal">
            <div class="rpt-modal-header">
                <div>
                    <div class="rpt-modal-eyebrow">Admin Action</div>
                    <div class="rpt-modal-title">Add New Program</div>
                </div>
                <button type="button" class="rpt-modal-close" onclick="document.getElementById('postModal').classList.remove('open')">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <form method="POST" action="admin_program.php" enctype="multipart/form-data">
                <input type="hidden" name="add_program" value="1">
                <div class="rpt-modal-body">
                    <div class="field-group">
                        <label class="field-label">Program Title</label>
                        <input type="text" class="field-input" name="title" placeholder="Enter program title..." required>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Category / Department</label>
                        <div class="rpt-cat-row" id="catBtnRow">
                            <button type="button" class="rpt-cat-btn" data-cat="Health"         onclick="selectCategory(this)"><i class="fa fa-heartbeat"></i><span>Health</span></button>
                            <button type="button" class="rpt-cat-btn" data-cat="Education"      onclick="selectCategory(this)"><i class="fa fa-graduation-cap"></i><span>Education</span></button>
                            <button type="button" class="rpt-cat-btn" data-cat="Public Works"   onclick="selectCategory(this)"><i class="fa fa-wrench"></i><span>Public Works</span></button>
                            <button type="button" class="rpt-cat-btn" data-cat="Social Welfare" onclick="selectCategory(this)"><i class="fa fa-users"></i><span>Social Welfare</span></button>
                            <button type="button" class="rpt-cat-btn" data-cat="Peace & Order"  onclick="selectCategory(this)"><i class="fa fa-shield"></i><span>Peace &amp; Order</span></button>
                        </div>
                        <input type="hidden" name="category"   id="postCategory" required>
                        <input type="hidden" name="department" id="postDept">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Description</label>
                        <textarea class="field-input" name="description" placeholder="Describe the program and its goals..." required style="resize:vertical;"></textarea>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Start Date</label>
                        <input type="date" class="field-input" name="start_date">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Image (optional)</label>
                        <div class="image-picker-admin" onclick="document.getElementById('progFileInput').click()" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px dashed var(--border);border-radius:10px;cursor:pointer;background:var(--faint);">
                            <i class="fa fa-image" style="font-size:18px;color:var(--muted);"></i>
                            <span style="font-size:13px;color:var(--muted);" id="progFileLabel">Choose image...</span>
                        </div>
                        <input type="file" id="progFileInput" name="prog_image" accept="image/*" style="display:none;"
                               onchange="document.getElementById('progFileLabel').textContent = this.files[0] ? '✓ ' + this.files[0].name : 'Choose image...'">
                    </div>
                </div>
                <div class="rpt-modal-footer">
                    <button type="button" class="rpt-modal-cancel" onclick="document.getElementById('postModal').classList.remove('open')">Cancel</button>
                    <button type="submit" class="rpt-modal-submit"><i class="fa fa-paper-plane"></i> Add Program</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:18px;padding:28px 28px 22px;max-width:340px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,0.18);text-align:center;">
            <div style="width:52px;height:52px;border-radius:50%;background:#fff0f0;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fa fa-sign-out" style="font-size:22px;color:#c0001a;"></i>
            </div>
            <div style="font-size:16px;font-weight:800;color:#1a2340;margin-bottom:6px;">Log Out?</div>
            <div style="font-size:13px;color:#8890b8;margin-bottom:20px;">Are you sure you want to log out of the admin panel?</div>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button onclick="document.getElementById('logoutModal').style.display='none'" style="flex:1;padding:10px;border-radius:10px;border:1.5px solid #e0e4f0;background:#f7f8fc;color:#4a5280;font-weight:700;cursor:pointer;font-size:13px;">Cancel</button>
                <a href="index.html" onclick="sessionStorage.clear()" style="flex:1;padding:10px;border-radius:10px;background:#c0001a;color:#fff;font-weight:700;cursor:pointer;font-size:13px;text-decoration:none;display:flex;align-items:center;justify-content:center;">Log Out</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const programs = <?= json_encode(array_values($programs)) ?>;

    let currentDetailId = null;

    const catColors = {
        "Health":         { bg: "#fff0f0", color: "#c0001a", border: "#f7a0aa" },
        "Education":      { bg: "#e8f0fe", color: "#1a56db", border: "#90aef8" },
        "Public Works":   { bg: "#fff3e0", color: "#c47200", border: "#ffd580" },
        "Social Welfare": { bg: "#e6faed", color: "#128548", border: "#7de0a4" },
        "Peace & Order":  { bg: "#fce8ff", color: "#8b00c7", border: "#d48cf7" },
    };

    const statusConfig = {
        "planned":   { label: "Planned",   bg: "#f3e8ff", color: "#7c3aed" },
        "ongoing":   { label: "Ongoing",   bg: "#e8f0fe", color: "#1a56db" },
        "completed": { label: "Completed", bg: "#e6faed", color: "#128548" },
    };

    function fmtDate(str) {
        if (!str) return 'TBD';
        const d = new Date(str + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    }

    function fmtDateTime(str) {
        if (!str) return '—';
        return new Date(str).toLocaleString('en-US', { month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit' });
    }

    function renderPrograms() {
        const statusVal = document.getElementById('statusFilter').value;
        const catVal    = document.getElementById('catFilter').value;
        const filtered  = programs.filter(r =>
            (statusVal === 'all' || r.status === statusVal) &&
            (catVal    === 'all' || r.category === catVal)
        );
        const list  = document.getElementById('programsList');
        const empty = document.getElementById('emptyState');

        if (filtered.length === 0) { list.innerHTML = ''; empty.style.display = ''; return; }
        empty.style.display = 'none';

        list.innerHTML = filtered.map(r => {
            const cat = catColors[r.category]  || catColors["Health"];
            const st  = statusConfig[r.status] || statusConfig["planned"];
            const has_img = r.image_path ? '&nbsp;<i class="fa fa-image" style="color:var(--muted);font-size:10px;" title="Has image"></i>' : '';
            return `
            <div class="rpt-row" data-id="${r.id}" onclick="openDetail(${r.id})">
                <div class="rpt-row-left">
                    <div class="rpt-row-top">
                        <span class="rpt-cat-badge" style="background:${cat.bg};color:${cat.color};border-color:${cat.border}">${r.category}</span>
                        <span class="rpt-status-badge" style="background:${st.bg};color:${st.color}">${st.label}</span>
                        ${has_img}
                    </div>
                    <div class="rpt-row-title">${r.title}</div>
                    <div class="rpt-row-meta">
                        <i class="fa fa-building"></i> ${r.department} &nbsp;·&nbsp;
                        <i class="fa fa-calendar"></i> ${fmtDate(r.start_date)}
                    </div>
                </div>
                <div class="rpt-row-arrow"><i class="fa fa-chevron-right"></i></div>
            </div>`;
        }).join('');
    }

    function filterPrograms() { renderPrograms(); }

    function openDetail(id) {
        const r = programs.find(x => parseInt(x.id) === id);
        if (!r) return;
        currentDetailId = id;

        const st = statusConfig[r.status] || statusConfig["planned"];

        document.getElementById('detailCategory').textContent = r.category;
        document.getElementById('detailTitle').textContent    = r.title;
        document.getElementById('detailDesc').textContent     = r.description;
        document.getElementById('detailDate').textContent     = fmtDate(r.start_date);
        document.getElementById('detailDept').innerHTML       = `<i class="fa fa-building"></i> ${r.department}`;
        document.getElementById('formProgramId').value        = r.id;

        // Image
        const imgWrap = document.getElementById('detailImageWrap');
        const imgEl   = document.getElementById('detailImage');
        if (r.image_path) {
            imgEl.src = r.image_path;
            imgWrap.style.display = 'block';
        } else {
            imgWrap.style.display = 'none';
        }

        const badge = document.getElementById('detailStatus');
        badge.textContent       = st.label;
        badge.style.background  = 'rgba(255,255,255,0.18)';
        badge.style.color       = '#fff';
        badge.style.border      = '1.5px solid rgba(255,255,255,0.3)';

        document.getElementById('detailEmpty').style.display   = 'none';
        document.getElementById('detailContent').style.display = '';
        document.getElementById('splitDetail').classList.add('has-content');

        document.querySelectorAll('.rpt-row').forEach(row => row.classList.remove('selected'));
        const selRow = document.querySelector(`.rpt-row[data-id="${id}"]`);
        if (selRow) selRow.classList.add('selected');
    }

    function submitStatus(newStatus) {
        document.getElementById('formNewStatus').value = newStatus;
        document.getElementById('statusForm').submit();
    }

    function selectCategory(btn) {
        document.querySelectorAll('.rpt-cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.dataset.cat;
        document.getElementById('postCategory').value = cat;
        document.getElementById('postDept').value     = cat + ' Department';
    }

    renderPrograms();

    function confirmLogout() {
        document.getElementById('logoutModal').style.display = 'flex';
        return false;
    }

    const adminUser = sessionStorage.getItem('adminUser');
    if (adminUser) {
        const el = document.getElementById('sidebarUsername');
        if (el) el.textContent = adminUser.charAt(0).toUpperCase() + adminUser.slice(1);
    }
    </script>
</body>
</html>