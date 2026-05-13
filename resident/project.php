<?php
require_once __DIR__ . '/../connection.php';

$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter    = trim($_POST['reporter']    ?? '');
    $purok       = trim($_POST['purok']       ?? '');
    $raw_cat     = trim($_POST['category']    ?? '');
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_path  = null;
    $cat_parts      = explode('|', $raw_cat, 2);
    $category       = $cat_parts[0];
    $specific_issue = isset($cat_parts[1]) ? $cat_parts[1] : '';

    if (empty($reporter) || empty($title) || empty($category) || empty($description)) {
        $error_msg = 'Please fill in all required fields.';
    } else {
        if (!empty($_FILES['report_image']['name'])) {
            $upload_dir     = __DIR__ . '/../assets/img/uploads/';
            $upload_dir_web = 'assets/img/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext      = pathinfo($_FILES['report_image']['name'], PATHINFO_EXTENSION);
            $filename = 'report_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $target   = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['report_image']['tmp_name'], $target)) {
                $image_path = $upload_dir_web . $filename;
            }
        }

        $r_esc    = mysqli_real_escape_string($conn, $reporter);
        $p_esc    = mysqli_real_escape_string($conn, $purok);
        $cat_esc  = mysqli_real_escape_string($conn, $category);
        $spec_esc = mysqli_real_escape_string($conn, $specific_issue);
        $t_esc    = mysqli_real_escape_string($conn, $title);
        $d_esc    = mysqli_real_escape_string($conn, $description);
        $img_esc  = $image_path ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';

        $full_cat_esc = $spec_esc ? "$cat_esc|$spec_esc" : $cat_esc;

        $query = "INSERT INTO reports (reporter, purok, category, title, description, image_path, status)
                  VALUES ('$r_esc', '$p_esc', '$full_cat_esc', '$t_esc', '$d_esc', $img_esc, 'pending')";

        if (executeQuery($query)) {
            $success_msg = 'Your report has been submitted successfully!';
        } else {
            $error_msg = 'Failed to submit report. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report — Ka-Barangay Connect</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/resident.css">
</head>
<body class="page-report">

    <nav class="header">
        <div class="logo">
            <img src="../assets/img/logo.png" alt="Logo"
                 onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="header-name">Ka-Barangay Connect</div>
            <div class="header-loc">San Bartolome</div>
        </div>
        <a href="resident.php" class="back-btn" style="margin-left:auto;">&#8592; Back</a>
    </nav>

    <div class="page-body">
        <div class="report-card">

            <div class="card-hero">
                <div class="card-hero-eyebrow">Resident Submission</div>
                <div class="card-hero-title">Submit a Report</div>
                <div class="card-hero-sub">Fill in the details below to file a report to the barangay</div>
            </div>

            <?php if ($success_msg): ?>
            <div style="margin:0 24px; padding:14px 18px; background:#e6faed; border:1.5px solid #7de0a4;
                        border-radius:12px; color:#128548; font-size:13.5px; font-weight:600;">
                ✓ <?= htmlspecialchars($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
            <div style="margin:0 24px; padding:14px 18px; background:#fff0f0; border:1.5px solid #f7a0aa;
                        border-radius:12px; color:#c0001a; font-size:13.5px; font-weight:600;">
                ✗ <?= htmlspecialchars($error_msg) ?>
            </div>
            <?php endif; ?>

            <form class="form-body" method="POST" enctype="multipart/form-data">

                <div class="form-section-label">
                    <span class="form-pill">Your Info</span>
                    <div class="form-line"></div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-12 col-sm-6">
                        <div class="field-group">
                            <label class="field-label" for="nameInput">Full Name <span style="color:#c0001a;">*</span></label>
                            <input type="text" class="field-input" id="nameInput"
                                   name="reporter" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <div class="field-group">
                            <label class="field-label">Purok / Area</label>
                            <select class="field-input" name="purok" required>
                                <option value="" disabled selected>Select your purok...</option>
                                <option value="Purok 1">Purok 1</option>
                                <option value="Purok 2">Purok 2</option>
                                <option value="Purok 3">Purok 3</option>
                                <option value="Purok 4">Purok 4</option>
                                <option value="Purok 5">Purok 5</option>
                                <option value="Purok 6">Purok 6</option>
                                <option value="Purok 7">Purok 7</option>
                                <option value="PMK">PMK</option>
                                <option value="Morgan">Morgan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section-label">
                    <span class="form-pill">Issue Category</span>
                    <div class="form-line"></div>
                </div>

                <div class="field-group">
                    <label class="field-label">Category</label>
                    <select class="field-input" name="category" onchange="updateCategoryBadge(this)" required>
                        <option value="" disabled selected>Select a category...</option>
                        <optgroup label="Isyu sa Imprastraktura">
                            <option value="Infrastructure|Sirang kalsada">Sirang kalsada</option>
                            <option value="Infrastructure|Lubak sa daan">Lubak sa daan</option>
                            <option value="Infrastructure|Sirang tulay">Sirang tulay</option>
                            <option value="Infrastructure|Wasak na sidewalk">Wasak na sidewalk</option>
                        </optgroup>
                        <optgroup label="Isyu sa Kapaligiran">
                            <option value="Kalikasan|Baradong kanal">Baradong kanal</option>
                            <option value="Kalikasan|Tambak na basura">Tambak na basura</option>
                            <option value="Kalikasan|Maruming paligid">Maruming paligid</option>
                            <option value="Kalikasan|Mabahong tubig">Mabahong tubig</option>
                        </optgroup>
                        <optgroup label="Serbisyong Pangunahing Pangangailangan">
                            <option value="Serbisyo Publiko|Sirang streetlight">Sirang streetlight</option>
                            <option value="Serbisyo Publiko|Walang ilaw sa daan">Walang ilaw sa daan</option>
                            <option value="Serbisyo Publiko|Problema sa tubig">Problema sa tubig</option>
                            <option value="Serbisyo Publiko|Nawawalang poste ng ilaw">Nawawalang poste ng ilaw</option>
                        </optgroup>
                        <optgroup label="Serbisyong Pampubliko">
                            <option value="Publiko|Mabagal na aksyon ng barangay">Mabagal na aksyon ng barangay</option>
                            <option value="Publiko|Hindi naasikaso ang reklamo">Hindi naasikaso ang reklamo</option>
                            <option value="Publiko|Kulang ang serbisyo">Kulang ang serbisyo</option>
                            <option value="Publiko|Walang follow-up">Walang follow-up</option>
                        </optgroup>
                        <optgroup label="Kapayapaan at Kaayusan">
                            <option value="Kapayapaan|Madilim na lugar sa gabi">Madilim na lugar sa gabi</option>
                            <option value="Kapayapaan|Maingay na kapitbahay">Maingay na kapitbahay</option>
                            <option value="Kapayapaan|Gulo o away">Gulo o away</option>
                            <option value="Kapayapaan|Kahina-hinalang tambay">Kahina-hinalang tambay</option>
                        </optgroup>
                    </select>
                    <div class="category-badge-row">
                        <span class="cat-badge infra"      id="badge-infra">🔧 Imprastraktura</span>
                        <span class="cat-badge kalikasan"  id="badge-kalikasan">🌿 Kapaligiran</span>
                        <span class="cat-badge serbisyo"   id="badge-serbisyo">💡 Pangunahing Serbisyo</span>
                        <span class="cat-badge publiko"    id="badge-publiko">🏛️ Serbisyong Pampubliko</span>
                        <span class="cat-badge kapayapaan" id="badge-kapayapaan">🕊️ Kapayapaan at Kaayusan</span>
                    </div>
                </div>

                <div class="form-section-label mt-2">
                    <span class="form-pill">Report Details</span>
                    <div class="form-line"></div>
                </div>

                <div class="field-group">
                    <label class="field-label">Report Title</label>
                    <input type="text" class="field-input" name="title"
                           placeholder="Brief title of your report" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Description</label>
                    <textarea class="field-input" name="description"
                              placeholder="Provide full details of the issue or concern..." required></textarea>
                </div>

                <div class="form-section-label mt-2">
                    <span class="form-pill">Attachment</span>
                    <div class="form-line"></div>
                </div>

                <div class="image-picker mb-2" onclick="document.getElementById('fileInput').click()">
                    <div class="picker-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <div>
                        <div class="picker-title">Choose Image</div>
                        <div class="picker-sub">Tap to open gallery</div>
                    </div>
                </div>

                <div id="fileNameDisplay" style="display:none; font-size:12.5px; color:#128548; margin-bottom:8px;">
                    &#10003; File selected
                </div>
                <input type="file" id="fileInput" name="report_image" accept="image/*" style="display:none;">

                <div class="form-footer">
                    <button type="submit" class="btn-submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        Submit Report
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/report.js"></script>
</body>
</html>