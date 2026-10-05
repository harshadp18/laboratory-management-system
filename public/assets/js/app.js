/**
 * Laboratory Management System - Frontend App Scripts
 * Vanilla JavaScript for interaction, modal handling, validation and live filters.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('collapseSidebarBtn');
    if (collapseBtn && sidebar) {
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('lms_sidebar_collapsed', isCollapsed ? '1' : '0');
        });
        if (localStorage.getItem('lms_sidebar_collapsed') === '1') {
            sidebar.classList.add('collapsed');
        }
    }

    // 2. Profile Dropdown Menu
    const profileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('userMenuDropdown');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        document.addEventListener('click', (e) => {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
    }

    // 3. Workstation Component Details Modal
    const modalBackdrop = document.getElementById('componentModal');
    const modalCloseButtons = document.querySelectorAll('[data-close-modal]');
    const categorySelect = document.getElementById('modalCategoryFilter');
    const componentRows = document.querySelectorAll('#componentTableBody tr');

    window.openComponentDetails = function (wsCode, wsStatus, issueNote) {
        if (!modalBackdrop) return;
        const nameEl = document.getElementById('modalWsName');
        const badgeEl = document.getElementById('modalWsBadge');
        const alertEl = document.getElementById('modalAlertBanner');

        if (nameEl) nameEl.textContent = wsCode || 'Lab A - WS-03';
        if (badgeEl) {
            badgeEl.className = 'status-pill ' + (wsStatus === 'Under Repair' ? 'under-repair' : 'working');
            badgeEl.innerHTML = `<span class="status-dot"></span> ${wsStatus || 'Under Repair'}`;
        }
        if (alertEl) {
            if (issueNote) {
                alertEl.style.display = 'flex';
                document.getElementById('modalAlertText').textContent = issueNote;
            } else {
                alertEl.style.display = 'none';
            }
        }

        modalBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeComponentDetails = function () {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('open');
        document.body.style.overflow = '';
    };

    modalCloseButtons.forEach((btn) => {
        btn.addEventListener('click', window.closeComponentDetails);
    });

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) window.closeComponentDetails();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalBackdrop.classList.contains('open')) {
                window.closeComponentDetails();
            }
        });
    }

    // Filter components by category in modal
    if (categorySelect && componentRows.length > 0) {
        categorySelect.addEventListener('change', () => {
            const val = categorySelect.value.toLowerCase();
            componentRows.forEach((tr) => {
                const cat = (tr.dataset.category || '').toLowerCase();
                if (val === 'all' || val === '' || cat === val) {
                    tr.style.display = '';
                } else {
                    tr.style.display = 'none';
                }
            });
        });
    }

    // 4. Booking Time Validation (End Time > Start Time rule)
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    const timeFeedback = document.getElementById('timeValidationFeedback');
    const bookingSubmitBtn = document.getElementById('bookingSubmitBtn');

    function validateBookingTimes() {
        if (!startTimeInput || !endTimeInput || !timeFeedback) return;
        const start = startTimeInput.value;
        const end = endTimeInput.value;

        if (!start || !end) {
            timeFeedback.innerHTML = '';
            timeFeedback.className = 'validation-feedback';
            if (bookingSubmitBtn) bookingSubmitBtn.disabled = false;
            return;
        }

        const [startH, startM] = start.split(':').map(Number);
        const [endH, endM] = end.split(':').map(Number);
        const startTotal = startH * 60 + startM;
        const endTotal = endH * 60 + endM;

        if (endTotal <= startTotal) {
            endTimeInput.style.borderColor = '#dc2626';
            timeFeedback.className = 'validation-feedback invalid';
            timeFeedback.innerHTML = `
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>
                </svg>
                <span>End time must be strictly after start time. Choose a time later than ${start}.</span>
            `;
            if (bookingSubmitBtn) {
                bookingSubmitBtn.disabled = true;
                bookingSubmitBtn.style.opacity = '0.5';
                bookingSubmitBtn.style.cursor = 'not-allowed';
            }
        } else {
            endTimeInput.style.borderColor = '';
            const diffHours = ((endTotal - startTotal) / 60).toFixed(1).replace('.0', '');
            timeFeedback.className = 'validation-feedback valid';
            timeFeedback.innerHTML = `
                <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>End time is after start time · ${diffHours}-hour session</span>
            `;
            if (bookingSubmitBtn) {
                bookingSubmitBtn.disabled = false;
                bookingSubmitBtn.style.opacity = '1';
                bookingSubmitBtn.style.cursor = 'pointer';
            }
        }
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('input', validateBookingTimes);
        endTimeInput.addEventListener('input', validateBookingTimes);
        validateBookingTimes();
    }

    // 5. Workstation Live Search & Filter (labs.php)
    const wsSearch = document.getElementById('workstationSearch');
    const wsStatusFilter = document.getElementById('workstationStatusFilter');
    const wsCards = document.querySelectorAll('.workstation-card');

    function filterWorkstations() {
        if (!wsCards.length) return;
        const query = (wsSearch ? wsSearch.value : '').toLowerCase().trim();
        const status = (wsStatusFilter ? wsStatusFilter.value : '').toLowerCase();

        wsCards.forEach((card) => {
            const name = (card.dataset.name || '').toLowerCase();
            const cardStatus = (card.dataset.status || '').toLowerCase();

            const matchesQuery = !query || name.includes(query);
            const matchesStatus = !status || status === 'all' || cardStatus === status;

            if (matchesQuery && matchesStatus) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (wsSearch) wsSearch.addEventListener('input', filterWorkstations);
    if (wsStatusFilter) wsStatusFilter.addEventListener('change', filterWorkstations);

    // 6. Maintenance Board Filters (maintenance.php)
    const maintTypeFilter = document.getElementById('maintenanceTypeFilter');
    const maintStatusFilter = document.getElementById('maintenanceStatusFilter');
    const maintCards = document.querySelectorAll('.kanban-card');

    function filterMaintenance() {
        if (!maintCards.length) return;
        const type = (maintTypeFilter ? maintTypeFilter.value : '').toLowerCase();
        const status = (maintStatusFilter ? maintStatusFilter.value : '').toLowerCase();

        maintCards.forEach((card) => {
            const cardType = (card.dataset.type || '').toLowerCase();
            const cardStatus = (card.dataset.status || '').toLowerCase();

            const matchesType = !type || type === 'all' || cardType === type;
            const matchesStatus = !status || status === 'all' || cardStatus === status;

            if (matchesType && matchesStatus) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (maintTypeFilter) maintTypeFilter.addEventListener('change', filterMaintenance);
    if (maintStatusFilter) maintStatusFilter.addEventListener('change', filterMaintenance);

    // 7. Toast Notifications
    window.showToast = function (message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <svg width="18" height="18" fill="none" stroke="#22c55e" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>${message}</span>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    };

    // Form handlers
    const forms = document.querySelectorAll('form[data-ajax-toast]');
    forms.forEach((f) => {
        f.addEventListener('submit', (e) => {
            e.preventDefault();
            const msg = f.dataset.ajaxToast || 'Record saved successfully.';
            window.showToast(msg);
            f.reset();
            validateBookingTimes();
        });
    });

    // Confirmation popups
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.confirm)) event.preventDefault();
        });
    });
});
