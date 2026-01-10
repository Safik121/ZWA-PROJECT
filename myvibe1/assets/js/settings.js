/**
 * Settings Page Scripts
 * Handles modals and account deletion confirmation.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Modal Logic
    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('active');
    };

    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('active');
    };

    // Close on click outside
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    });

    // Delete Account Confirmation
    const deleteForm = document.querySelector('form[action="settings.php"] input[value="delete_account"]')?.closest('form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', (e) => {
            if (!confirm('Are you absolutely sure you want to delete your account? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
});
