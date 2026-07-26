// TG Microfinance ERP - Admin JS Interactions

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Mobile Toggle
    const sidebarToggler = document.getElementById('sidebar-toggler');
    const adminSidebar = document.getElementById('admin-sidebar');

    if (sidebarToggler && adminSidebar) {
        sidebarToggler.addEventListener('click', (e) => {
            e.preventDefault();
            adminSidebar.classList.toggle('show');
        });
    }

    // Fullscreen Toggle Button
    const fullscreenBtn = document.getElementById('btn-fullscreen-toggle');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    }

    // Global Search Shortcut Focus (Ctrl + K)
    const globalSearchInput = document.getElementById('global-search-input');
    if (globalSearchInput) {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                globalSearchInput.focus();
            }
        });
    }
});
