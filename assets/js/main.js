/* ============================================================
   Ka-Barangay Connect — Shared JavaScript
   assets/js/main.js
   ============================================================ */

/* ── Report page: image picker ── */
function showImageOptions() {
    const choice  = confirm('Open Camera?\n\nTap OK for Camera, Cancel to choose from Gallery.');
    const inputId = choice ? 'cameraInput' : 'fileInput';
    const el      = document.getElementById(inputId);
    if (el) el.click();
}

function handleFileChange(e) {
    const file    = e.target.files[0];
    const display = document.getElementById('fileNameDisplay');
    if (file && display) {
        display.style.display = 'block';
        display.textContent   = '✓ ' + file.name;
    }
}

/* ── Init: wire up file input events ── */
document.addEventListener('DOMContentLoaded', function () {
    const fileInput   = document.getElementById('fileInput');
    const cameraInput = document.getElementById('cameraInput');
    if (fileInput)   fileInput.addEventListener('change', handleFileChange);
    if (cameraInput) cameraInput.addEventListener('change', handleFileChange);
});