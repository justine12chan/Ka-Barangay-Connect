<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report — Ka-Barangay Connect</title>
    <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
     <link rel="icon" href="assets/img/logo.png" type="image/x-icon">
</head>
<body class="page-report">

    <!-- HEADER -->
    <nav class="header">
        <div class="logo">
            <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='SB'">
        </div>
        <div>
            <div class="header-name">Ka-Barangay Connect</div>
            <div class="header-loc">San Bartolome</div>
        </div>
        <a href="resident.php" class="back-btn">&#8592; Back</a>
    </nav>

    <div class="page-body">
        <div class="report-card">

            <!-- Hero -->
            <div class="card-hero">
                <div class="card-hero-eyebrow">Resident Submission</div>
                <div class="card-hero-title">Submit a Report</div>
                <div class="card-hero-sub">Fill in the details below to file a report to the barangay</div>
            </div>

            <!-- Form -->
            <div class="form-body">

                <div class="form-section-label">
                    <span class="form-pill">Your Info</span>
                    <div class="form-line"></div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-12 col-sm-6">
                        <div class="field-group">
                            <label class="field-label">Full Name</label>
                            <input type="text" class="field-input" placeholder="Enter your name">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="field-group">
                            <label class="field-label">Category</label>
                            <select class="field-input" id="categorySelect" onchange="updateCategoryBadge(this)">
                                <option value="">— Pumili ng Kategorya —</option>
                                <optgroup label="🔧 Isyu sa Imprastraktura">
                                    <option value="infra-kalsada">Sirang kalsada</option>
                                    <option value="infra-lubak">Lubak sa daan</option>
                                    <option value="infra-tulay">Sirang tulay</option>
                                    <option value="infra-sidewalk">Wasak na sidewalk</option>
                                </optgroup>
                                <optgroup label="🌿 Isyu sa Kapaligiran">
                                    <option value="kalikasan-kanal">Baradong kanal</option>
                                    <option value="kalikasan-basura">Tambak na basura</option>
                                    <option value="kalikasan-paligid">Maruming paligid</option>
                                    <option value="kalikasan-tubig">Mabahong tubig</option>
                                </optgroup>
                                <optgroup label="💡 Serbisyong Pangunahing Pangangailangan">
                                    <option value="serbisyo-streetlight">Sirang streetlight</option>
                                    <option value="serbisyo-ilaw">Walang ilaw sa daan</option>
                                    <option value="serbisyo-tubig">Problema sa tubig</option>
                                    <option value="serbisyo-poste">Nawawalang poste ng ilaw</option>
                                </optgroup>
                                <optgroup label="🏛️ Serbisyong Pampubliko">
                                    <option value="publiko-aksyon">Mabagal na aksyon ng barangay</option>
                                    <option value="publiko-reklamo">Hindi naasikaso ang reklamo</option>
                                    <option value="publiko-serbisyo">Kulang ang serbisyo</option>
                                    <option value="publiko-followup">Walang follow-up</option>
                                </optgroup>
                                <optgroup label="🕊️ Kapayapaan at Kaayusan">
                                    <option value="kapayapaan-madilim">Madilim na lugar sa gabi</option>
                                    <option value="kapayapaan-ingay">Maingay na kapitbahay</option>
                                    <option value="kapayapaan-gulo">Gulo o away</option>
                                    <option value="kapayapaan-tambay">Kahina-hinalang tambay</option>
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
                    </div>
                </div>

                <div class="form-section-label mt-2">
                    <span class="form-pill">Report Details</span>
                    <div class="form-line"></div>
                </div>

                <div class="field-group">
                    <label class="field-label">Report Title</label>
                    <input type="text" class="field-input" placeholder="Brief title of your report">
                </div>

                <div class="field-group">
                    <label class="field-label">Description</label>
                    <textarea class="field-input" placeholder="Provide full details of the issue or concern..."></textarea>
                </div>

                <div class="form-section-label mt-2">
                    <span class="form-pill">Attachment</span>
                    <div class="form-line"></div>
                </div>

                <div class="image-picker mb-2" onclick="showImageOptions()">
                    <div class="picker-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f5cc00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <div>
                        <div class="picker-title">Choose Image</div>
                        <div class="picker-sub">Tap to open camera or gallery</div>
                    </div>
                </div>

                <div id="fileNameDisplay">&#10003; File selected</div>

                <input type="file" id="fileInput" accept="image/*">
                <input type="file" id="cameraInput" accept="image/*" capture="environment">

            </div>

            <!-- Submit -->
            <div class="form-footer">
                <button type="button" class="btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Submit Report
                </button>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>