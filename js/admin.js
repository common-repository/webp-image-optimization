// js/admin.js

document.addEventListener('DOMContentLoaded', function() {

    // Function to synchronize range and number inputs
    function syncInputs(rangeId, numberId, valueId) {
        var rangeInput = document.getElementById(rangeId);
        var numberInput = document.getElementById(numberId);
        var valueDisplay = document.getElementById(valueId);

        if (!rangeInput || !numberInput || !valueDisplay) return;

        // Set initial value display
        valueDisplay.textContent = rangeInput.value;

        // Synchronize the values
        rangeInput.addEventListener('input', function() {
            numberInput.value = rangeInput.value;
            valueDisplay.textContent = rangeInput.value;
        });

        numberInput.addEventListener('input', function() {
            rangeInput.value = numberInput.value;
            valueDisplay.textContent = numberInput.value;
        });
    }

    // Synchronize JPEG Quality
    syncInputs('jpeg_quality_range', 'jpeg_quality_number', 'jpeg_quality_value');

    // Synchronize PNG Compression
    syncInputs('png_compression_range', 'png_compression_number', 'png_compression_value');

    // Synchronize WebP Quality
    syncInputs('webp_quality_range', 'webp_quality_number', 'webp_quality_value');
});
