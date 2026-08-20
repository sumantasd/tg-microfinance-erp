import * as bootstrap from 'bootstrap';
import './public.js';
import './admin.js';

window.bootstrap = bootstrap;

// Global Delegated Click Listener for Bootstrap Modals
document.addEventListener('click', (e) => {
    const modalTrigger = e.target.closest('[data-bs-toggle="modal"]');
    if (modalTrigger) {
        const targetSelector = modalTrigger.getAttribute('data-bs-target') || modalTrigger.getAttribute('href');
        if (targetSelector && targetSelector.startsWith('#')) {
            const modalEl = document.querySelector(targetSelector);
            if (modalEl) {
                e.preventDefault();
                // Dynamically relocate modal to document.body to break out of parent stacking contexts & overflow hidden clipping
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        }
    }
});

// Initialize Bootstrap Tooltips & Popovers
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});
