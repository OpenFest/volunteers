// Keep "min primary" from exceeding "min total" in team limit forms.
(function () {
    'use strict';

    const primary = document.getElementById('min_primary');
    const total = document.getElementById('min_total');
    if (!primary || !total) return;

    function sync() {
        primary.max = total.value || '';
        if (total.value && Number(primary.value) > Number(total.value)) {
            primary.value = total.value;
        }
    }

    total.addEventListener('input', sync);
    sync();
})();
