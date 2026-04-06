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
    <style>
        .name-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .name-label-row .field-label { margin-bottom: 0; }

        .anon-toggle {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--blue-faint, #ededff);
            border: 1.5px solid var(--border, #e2e3f0);
            color: var(--blue-main, #0800a0);
            font-family: 'Sora', sans-serif;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 4px 10px 4px 8px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, color 0.2s, box-shadow 0.2s;
            -webkit-tap-highlight-color: transparent;
            white-space: nowrap;
        }
        .anon-toggle svg {
            width: 13px; height: 13px;
            flex-shrink: 0;
            transition: stroke 0.2s;
        }
        .anon-toggle:hover {
            background: var(--blue-light, #ededff);
            border-color: var(--blue-main, #0800a0);
            box-shadow: 0 2px 8px rgba(8,0,160,0.1);
        }
        .anon-toggle.active {
            background: var(--blue-main, #0800a0);
            border-color: var(--blue-main, #0800a0);
            color: var(--yellow, #f5cc00);
            box-shadow: 0 4px 14px rgba(8,0,160,0.25);
        }
        .anon-toggle.active svg { stroke: var(--yellow, #f5cc00); }

        #nameInputWrap {
            transition: opacity 0.25s, transform 0.25s;
        }
        #nameInputWrap.hidden {
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            height: 0;
            overflow: hidden;
            margin: 0;
        }

        .anon-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(8,0,160,0.06);
            border: 1.5px dashed rgba(8,0,160,0.2);
            color: var(--blue-main, #0800a0);
            font-size: 12.5px;
            font-weight: 500;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 2px;
            animation: fadein 0.25s ease;
        }
        .anon-badge svg { width: 15px; height: 15px; flex-shrink: 0; opacity: 0.7; }

        @keyframes fadein {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
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
                            <div class="name-label-row">
                                <label class="field-label" id="nameLabel">Full Name</label>
                                <button type="button" class="anon-toggle" id="anonToggle" onclick="toggleAnonymous()">
                                    <svg id="anonIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <span id="anonLabel">Submit Anonymously</span>
                                </button>
                            </div>
                            <div id="nameInputWrap">
                                <input type="text" class="field-input" id="nameInput" placeholder="Enter your name">
                            </div>
                            <div id="anonBadge" class="anon-badge" style="display:none;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                                Your identity will be kept private
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="field-group">
                            <label class="field-label">Category</label>
                            <select class="field-input" id="categorySelect" onchange="updateCategoryBadge(this)">
                                <option value="">— Pumili ng Kategorya —</option>
                                <optgroup label=" Isyu sa Imprastraktura">
                                    <option value="infra-kalsada">Sirang kalsada</option>
                                    <option value="infra-lubak">Lubak sa daan</option>
                                    <option value="infra-tulay">Sirang tulay</option>
                                    <option value="infra-sidewalk">Wasak na sidewalk</option>
                                </optgroup>
                                <optgroup label=" Isyu sa Kapaligiran">
                                    <option value="kalikasan-kanal">Baradong kanal</option>
                                    <option value="kalikasan-basura">Tambak na basura</option>
                                    <option value="kalikasan-paligid">Maruming paligid</option>
                                    <option value="kalikasan-tubig">Mabahong tubig</option>
                                </optgroup>
                                <optgroup label=" Serbisyong Pangunahing Pangangailangan">
                                    <option value="serbisyo-streetlight">Sirang streetlight</option>
                                    <option value="serbisyo-ilaw">Walang ilaw sa daan</option>
                                    <option value="serbisyo-tubig">Problema sa tubig</option>
                                    <option value="serbisyo-poste">Nawawalang poste ng ilaw</option>
                                </optgroup>
                                <optgroup label=" Serbisyong Pampubliko">
                                    <option value="publiko-aksyon">Mabagal na aksyon ng barangay</option>
                                    <option value="publiko-reklamo">Hindi naasikaso ang reklamo</option>
                                    <option value="publiko-serbisyo">Kulang ang serbisyo</option>
                                    <option value="publiko-followup">Walang follow-up</option>
                                </optgroup>
                                <optgroup label=" Kapayapaan at Kaayusan">
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
    <script>
        let isAnonymous = false;

        function toggleAnonymous() {
            isAnonymous = !isAnonymous;
            const toggle   = document.getElementById('anonToggle');
            const label    = document.getElementById('anonLabel');
            const icon     = document.getElementById('anonIcon');
            const nameWrap = document.getElementById('nameInputWrap');
            const badge    = document.getElementById('anonBadge');
            const nameInput= document.getElementById('nameInput');

            if (isAnonymous) {
                toggle.classList.add('active');
                label.textContent = 'Submitting Anonymously';
                icon.innerHTML = `<circle cx="12" cy="8" r="4"/>
                    <path d="M6 20v-2a6 6 0 0 1 12 0v2"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>`;
                nameWrap.classList.add('hidden');
                badge.style.display = 'flex';
                nameInput.value = '';
                nameInput.disabled = true;
            } else {
                toggle.classList.remove('active');
                label.textContent = 'Submit Anonymously';
                icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>`;
                nameWrap.classList.remove('hidden');
                badge.style.display = 'none';
                nameInput.disabled = false;
            }
        }
    </script>
</body>
</html>