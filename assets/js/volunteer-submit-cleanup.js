/**
 * Volunteer Form Submission Cleanup
 *
 * Clears localStorage after successful volunteer registration
 */

(function() {
    'use strict';

    const storageKey = 'newVolunteerFormData';

    /**
     * Clear the volunteer form data from localStorage
     * This should be called after successful form submission
     */
    function clearFormData() {
        try {
            localStorage.removeItem(storageKey);
            console.log('Volunteer form data cleared from localStorage');
        } catch (error) {
            console.error('Error clearing form data:', error);
        }
    }

    // Execute on page load (this will run on the success page)
    clearFormData();
})();

