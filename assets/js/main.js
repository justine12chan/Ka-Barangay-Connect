/* ============================================================
   Ka-Barangay Connect — Shared JavaScript
   assets/js/main.js
   ============================================================ */

/* ── REPORT PAGE: Image picker ── */
function showImageOptions() {
    const choice = confirm("Open Camera?\n\nTap OK for Camera, Cancel to choose from Gallery.");
    const inputId = choice ? 'cameraInput' : 'fileInput';
    const el = document.getElementById(inputId);
    if (el) el.click();
}

function handleFileChange(e) {
    const file = e.target.files[0];
    const display = document.getElementById('fileNameDisplay');
    if (file && display) {
        display.style.display = 'block';
        display.textContent = '✓ ' + file.name;
    }
}

/* ── REPORT PAGE: Category badge ── */
function updateCategoryBadge(select) {
    const val = select.value;
    const badges = ['infra', 'kalikasan', 'serbisyo', 'publiko', 'kapayapaan'];
    badges.forEach(function(b) {
        const el = document.getElementById('badge-' + b);
        if (el) el.classList.remove('show');
    });
    if (!val) return;
    const prefix = val.split('-')[0];
    const el = document.getElementById('badge-' + prefix);
    if (el) el.classList.add('show');
}

/* ── INIT: wire up events after DOM ready ── */
document.addEventListener('DOMContentLoaded', function () {

    /* File inputs on report page */
    var fileInput   = document.getElementById('fileInput');
    var cameraInput = document.getElementById('cameraInput');
    if (fileInput)   fileInput.addEventListener('change', handleFileChange);
    if (cameraInput) cameraInput.addEventListener('change', handleFileChange);

});