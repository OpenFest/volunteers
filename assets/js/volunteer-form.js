/**
 * Volunteer Registration Form Handler
 *
 * Handles form data persistence using localStorage and image preview functionality
 * for the new volunteer registration form.
 */

(function() {
    'use strict';

    const form = document.querySelector('#new_volunteer');
    if (!form) return; // Exit if form doesn't exist on this page

    const storageKey = 'newVolunteerFormData';
    const imageInput = form.querySelector('#volunteer_picture');
    const preview = form.querySelector('#preview');

    /**
     * Restore form data from localStorage on page load
     */
    function restoreFormData() {
        const savedData = localStorage.getItem(storageKey);
        if (!savedData) return;

        try {
            const data = JSON.parse(savedData);
            for (const [name, value] of Object.entries(data)) {
                const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
                if (!field) continue;

                if (field.type === 'checkbox') {
                    field.checked = value === true;
                } else {
                    field.value = value;
                }
            }
        } catch (error) {
            console.error('Error restoring form data:', error);
        }
    }

    /**
     * Save form data to localStorage
     */
    function saveFormData() {
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            if (key === 'csrf_token') return; // Skip CSRF token

            const field = form.querySelector(`[name="${CSS.escape(key)}"]`);
            if (field && (field.type === 'checkbox' || field.type === 'radio')) {
                data[key] = field.checked;
            } else if (field && field.type === 'file') {
                // Skip file inputs, as they cannot be stored in localStorage
            } else {
                data[key] = value;
            }
        });

        try {
            localStorage.setItem(storageKey, JSON.stringify(data));
        } catch (error) {
            console.error('Error saving form data:', error);
        }
    }

    /**
     * Preview uploaded image
     */
    function previewImage() {
        const file = imageInput.files[0];

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            preview.src = '#';
        }
    }

    // Initialize
    window.addEventListener('DOMContentLoaded', restoreFormData);
    form.addEventListener('input', saveFormData);
    form.addEventListener('change', saveFormData);

    if (imageInput) {
        imageInput.addEventListener('change', previewImage);
    }
})();

