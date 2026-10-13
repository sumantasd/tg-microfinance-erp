// Grihalaxmi Finance ERP - Mobile-First Admin Interactions & Polish

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Mobile Toggle (Desktop fallback)
    const sidebarToggler = document.getElementById('sidebar-toggler');
    const adminSidebar = document.getElementById('admin-sidebar');

    if (sidebarToggler && adminSidebar) {
        sidebarToggler.addEventListener('click', (e) => {
            e.preventDefault();
            adminSidebar.classList.toggle('show');
        });
    }

    // 2. Fullscreen Toggle Button
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

    // 3. Global Search Shortcut Focus (Ctrl + K)
    const globalSearchInput = document.getElementById('global-search-input');
    if (globalSearchInput) {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                globalSearchInput.focus();
            }
        });
    }

    // 4. Smooth Anchor Scrolling for Duty Tracker Card (#employeeDutyTrackerCard)
    const dutyLinks = document.querySelectorAll('a[href*="#employeeDutyTrackerCard"], .mobile-nav-duty-trigger');
    dutyLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const dutyCard = document.getElementById('employeeDutyTrackerCard');
            if (dutyCard) {
                // If we're already on the dashboard page, smooth scroll directly to it
                if (window.location.pathname === '/admin' || window.location.pathname === '/admin/dashboard') {
                    e.preventDefault();
                    dutyCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Visual pulse feedback
                    dutyCard.classList.add('shadow-lg', 'border', 'border-primary');
                    setTimeout(() => {
                        dutyCard.classList.remove('shadow-lg', 'border', 'border-primary');
                    }, 1800);
                }
            }
        });
    });

    // Check if initial load has #employeeDutyTrackerCard hash
    if (window.location.hash === '#employeeDutyTrackerCard') {
        const dutyCard = document.getElementById('employeeDutyTrackerCard');
        if (dutyCard) {
            setTimeout(() => {
                dutyCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                dutyCard.classList.add('shadow-lg', 'border', 'border-primary');
                setTimeout(() => dutyCard.classList.remove('shadow-lg', 'border', 'border-primary'), 1800);
            }, 300);
        }
    }

    // 5. Mobile Data Table Scroll Shadow Indicator Hint
    const responsiveTables = document.querySelectorAll('.table-responsive');
    responsiveTables.forEach(wrapper => {
        const checkScroll = () => {
            const maxScrollLeft = wrapper.scrollWidth - wrapper.clientWidth;
            if (maxScrollLeft > 10) {
                if (wrapper.scrollLeft < maxScrollLeft - 5) {
                    wrapper.style.boxShadow = 'inset -10px 0 8px -8px rgba(10, 30, 54, 0.15)';
                } else {
                    wrapper.style.boxShadow = 'none';
                }
            }
        };
        wrapper.addEventListener('scroll', checkScroll, { passive: true });
        checkScroll();
    });
});
