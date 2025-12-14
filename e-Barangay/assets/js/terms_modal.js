// Terms and Conditions Modal JavaScript
(function() {
    'use strict';

    // Get modal elements
    const termsModal = document.getElementById('termsModal');
    const termsLink = document.getElementById('termsLink');
    const modalClose = document.querySelector('.modal-close');
    const closeModalBtn = document.getElementById('closeModalBtn');

    // Check if elements exist
    if (!termsModal || !termsLink) {
        console.error('Terms modal elements not found');
        return;
    }

    // Open modal
    termsLink.addEventListener('click', function(e) {
        e.preventDefault();
        termsModal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    });

    // Close modal function
    function closeModal() {
        termsModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    }

    // Close modal - X button
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    // Close modal - Close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === termsModal) {
            closeModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && termsModal.style.display === 'flex') {
            closeModal();
        }
    });

})();