/**
 * Primary Team Selection Form Handler
 *
 * Handles the submission of the primary team selection form for volunteers
 * using AJAX to avoid page reload.
 */

(function() {
    'use strict';

    const setPrimaryButtons = document.querySelectorAll('.set-primary-team-button');
    if (!setPrimaryButtons.length) return; // Exit if no buttons exist on this page

    /**
     * Handle button click to set primary team
     * @param {Event} e - The click event
     */
    function handleButtonClick(e) {
        e.preventDefault();

        const button = e.currentTarget;
        const volunteerId = button.dataset.volunteerId;
        const teamId = button.dataset.teamId;

        if (!volunteerId || !teamId) {
            console.error('Volunteer ID or Team ID is missing.');
            return;
        }
        //prompt user for confirmation before proceeding
        const confirmation = confirm('Сигурни ли сте, че искате да зададете екип ' + button.textContent.trim() + ' като основен?');
        if (!confirmation) {
            return; // Exit if user cancels
        }

        // Disable the button to prevent double submissions
        button.disabled = true;
        const oldButtonText = button.textContent;
        button.textContent = 'Запазване...';

        fetch('/backbone/set-primary-team', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ volunteer: volunteerId, team: teamId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Get status from URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const status = urlParams.get('status') || 'accept';

                //reload page to reflect the change or redirect to the change status page
                window.location.reload();
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Грешка при задаване на основен екип. Моля, опитайте отново.');

            // Re-enable the button
            button.disabled = false;
            button.textContent = oldButtonText;
        });
    }

    // Add event listeners to all set primary team buttons
    setPrimaryButtons.forEach(button => {
        button.addEventListener('click', handleButtonClick);
    });
})();

