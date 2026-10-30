(function () {
    'use strict';

    const table = document.getElementById('shirts-table');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const search = document.getElementById('shirt-search');
    const hideTaken = document.getElementById('shirt-hide-taken');

    function applyFilter() {
        const q = search.value.trim().toLowerCase();
        rows.forEach(row => {
            const matches = !q || row.dataset.search.includes(q);
            const hidden = hideTaken.checked && row.dataset.taken === '1';
            row.style.display = matches && !hidden ? '' : 'none';
        });
    }

    function updateSummary(row, delta) {
        const summaryRow = document.querySelector('tr.summary-row[data-summary="' + CSS.escape(row.dataset.summary) + '"]');
        if (summaryRow) {
            const taken = summaryRow.querySelector('.summary-taken');
            const remaining = summaryRow.querySelector('.summary-remaining');
            taken.textContent = parseInt(taken.textContent, 10) + delta;
            remaining.textContent = parseInt(remaining.textContent, 10) - delta;
        }
        const total = document.getElementById('shirts-taken');
        total.textContent = parseInt(total.textContent, 10) + delta;
    }

    table.addEventListener('change', e => {
        const box = e.target;
        if (!box.classList.contains('shirt-toggle')) return;
        const row = box.closest('tr');
        const wanted = box.checked;
        box.disabled = true;

        fetch('/backbone/shirts/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ volunteer: box.dataset.volunteerId, taken: wanted })
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message);
                row.dataset.taken = wanted ? '1' : '0';
                row.querySelector('.shirt-date').textContent = data.date;
                updateSummary(row, wanted ? 1 : -1);
                applyFilter();
            })
            .catch(err => {
                box.checked = !wanted;
                alert('Error saving shirt status: ' + err.message);
            })
            .finally(() => { box.disabled = false; });
    });

    search.addEventListener('input', applyFilter);
    hideTaken.addEventListener('change', applyFilter);
})();
