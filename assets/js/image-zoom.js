/**
 * Opens images marked with the "zoomable" class in a modal dialog.
 */
(function () {
    'use strict';

    let dialog = null;

    function getDialog() {
        if (dialog) return dialog;
        dialog = document.createElement('dialog');
        dialog.className = 'image-zoom';
        dialog.innerHTML = '<img alt="">';
        dialog.addEventListener('click', function () { dialog.close(); });
        document.body.appendChild(dialog);
        return dialog;
    }

    document.addEventListener('click', function (event) {
        const img = event.target.closest('img.zoomable');
        if (!img) return;
        const modal = getDialog();
        const big = modal.querySelector('img');
        big.src = img.src;
        big.alt = img.alt;
        modal.showModal();
    });
})();
