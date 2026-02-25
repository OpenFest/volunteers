/**
 * Primary Team Selection Form Handler
 *
 * Handles the submission of the primary team selection form for volunteers
 * using AJAX to avoid page reload.
 */

(function() {
    'use strict';

    const form = document.getElementById('primary-team-form');
    if (!form) return; // Exit if form doesn't exist on this page

    /**
     * Handle form submission
     * @param {Event} e - The submit event
     */
    function handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');

        // Disable submit button to prevent double submissions
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Запазване...';
        }

        fetch('/backbone/set-primary-team', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Get volunteer ID and status from form data or URL
                const volunteerId = formData.get('volunteer');
                const urlParams = new URLSearchParams(window.location.search);
                const status = urlParams.get('status') || 'accept';

                // Redirect to the change status page
                window.location.href = `/backbone/volunteer/change-status?volunteer=${volunteerId}&status=${status}`;
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Грешка при задаване на основен екип. Моля, опитайте отново.');

            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Запази';
            }
        });
    }

    // Add event listener
    form.addEventListener('submit', handleSubmit);
})();

