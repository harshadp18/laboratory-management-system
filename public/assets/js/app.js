/**
 * Laboratory Management System - Frontend App Scripts
 * Vanilla JavaScript for interaction, modal handling, validation and live filters.
 */

document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const updateThemeControl = () => {
            const isDark = document.documentElement.dataset.theme === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
            themeToggle.title = `Switch to ${nextTheme} theme`;
        };
        themeToggle.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            try {
                localStorage.setItem('lms_theme', nextTheme);
            } catch (error) {
                // The theme still applies for this page if storage is unavailable.
            }
            updateThemeControl();
        });
        updateThemeControl();
    }

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
    const componentTableBody = document.getElementById('componentTableBody');

    window.openComponentDetails = function (wsCode, wsStatus, issueNote, components = []) {
        if (!modalBackdrop) return;
        const nameEl = document.getElementById('modalWsName');
        const badgeEl = document.getElementById('modalWsBadge');
        const alertEl = document.getElementById('modalAlertBanner');
        const componentCount = document.getElementById('componentCount');

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
        if (componentTableBody) {
            componentTableBody.replaceChildren();
            components.forEach((component) => {
                const row = document.createElement('tr');
                row.dataset.category = component.category || '';
                const values = [
                    component.category || '',
                    `${component.brand || '—'}\n${component.model || '—'}`,
                    component.serial || '—',
                    component.installed_at || '—',
                    component.removed_at || '—',
                ];
                values.forEach((value, index) => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    if (index === 0) cell.style.fontWeight = '600';
                    if (index === 1) cell.style.whiteSpace = 'pre-line';
                    if (index === 2) {
                        cell.style.fontFamily = 'monospace';
                        cell.style.fontSize = '0.8rem';
                    }
                    row.appendChild(cell);
                });
                componentTableBody.appendChild(row);
            });
            if (componentCount) componentCount.textContent = `${components.length} installed components`;
            if (categorySelect) categorySelect.dispatchEvent(new Event('change'));
        }

        modalBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    document.querySelectorAll('[data-component-details]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            let components = [];
            try {
                components = JSON.parse(link.dataset.components || '[]');
            } catch (error) {
                components = [];
            }
            window.openComponentDetails(link.dataset.wsCode, link.dataset.wsStatus, link.dataset.issue, components);
        });
    });

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
    if (categorySelect && componentTableBody) {
        categorySelect.addEventListener('change', () => {
            const val = categorySelect.value.toLowerCase();
            componentTableBody.querySelectorAll('tr').forEach((tr) => {
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
    const bookingDateInput = document.getElementById('bookingDate');
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

    const bookingLab = document.getElementById('bookingLab');
    const bookingLabCapacity = document.getElementById('bookingLabCapacity');
    if (bookingLab && bookingLabCapacity) {
        const updateCapacity = () => {
            bookingLabCapacity.textContent = bookingLab.selectedOptions[0]?.dataset.capacity || '—';
        };
        bookingLab.addEventListener('change', updateCapacity);
        updateCapacity();
    }

    const availabilityLab = document.getElementById('viewAvailabilityLab');
    const calendarEvents = document.querySelectorAll('[data-calendar-event]');
    const calendarCells = document.querySelectorAll('[data-calendar-cell]');
    if (availabilityLab) {
        const filterCalendarEvents = () => {
            calendarEvents.forEach((event) => {
                event.hidden = availabilityLab.value !== 'all' && event.dataset.lab !== availabilityLab.value;
            });
            calendarCells.forEach((cell) => {
                const occupied = [...cell.querySelectorAll('[data-calendar-event]')].some((event) => !event.hidden);
                cell.setAttribute('aria-disabled', String(occupied));
                cell.tabIndex = occupied ? -1 : 0;
            });
            if (bookingLab && availabilityLab.value !== 'all') {
                bookingLab.value = availabilityLab.value;
                bookingLab.dispatchEvent(new Event('change'));
            }
        };
        availabilityLab.addEventListener('change', filterCalendarEvents);
        filterCalendarEvents();
    }

    const calendarPanel = document.getElementById('bookingCalendarPanel');
    const expandCalendarButton = document.getElementById('expandBookingCalendar');
    if (calendarPanel && expandCalendarButton) {
        const label = expandCalendarButton.querySelector('span');
        const setCalendarExpanded = (expanded) => {
            calendarPanel.classList.toggle('is-expanded', expanded);
            expandCalendarButton.setAttribute('aria-expanded', String(expanded));
            if (label) label.textContent = expanded ? 'Collapse' : 'Expand';
            document.body.classList.toggle('calendar-expanded-open', expanded);
            if (!expanded) expandCalendarButton.focus();
        };
        expandCalendarButton.addEventListener('click', () => {
            setCalendarExpanded(!calendarPanel.classList.contains('is-expanded'));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && calendarPanel.classList.contains('is-expanded')) {
                setCalendarExpanded(false);
            }
        });
    }

    if (calendarCells.length && bookingLab && bookingDateInput && startTimeInput && endTimeInput) {
        let selectedCalendarCell = null;
        const selectSlot = (cell) => {
            const occupied = [...cell.querySelectorAll('[data-calendar-event]')]
                .some((event) => availabilityLab?.value === 'all' || !event.hidden);
            if (occupied) return;

            const date = cell.dataset.day;
            if (!date || date < bookingDateInput.min) {
                window.showToast('Choose a current or future date for your booking.');
                return;
            }

            const selectedLab = availabilityLab?.value;
            if (selectedLab && selectedLab !== 'all') {
                bookingLab.value = selectedLab;
            }
            if (!bookingLab.value && bookingLab.options.length) {
                bookingLab.selectedIndex = 0;
            }

            const hour = Number(cell.dataset.hour);
            const endHour = Math.min(hour + 1, 17);
            bookingDateInput.value = date;
            startTimeInput.value = `${String(hour).padStart(2, '0')}:00`;
            endTimeInput.value = `${String(endHour).padStart(2, '0')}:00`;
            bookingLab.dispatchEvent(new Event('change'));
            startTimeInput.dispatchEvent(new Event('input'));
            endTimeInput.dispatchEvent(new Event('input'));

            if (selectedCalendarCell) selectedCalendarCell.classList.remove('is-selected');
            selectedCalendarCell = cell;
            selectedCalendarCell.classList.add('is-selected');
            document.getElementById('bookingPurpose')?.focus();
        };

        calendarCells.forEach((cell) => {
            cell.addEventListener('click', () => selectSlot(cell));
            cell.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectSlot(cell);
                }
            });
        });
    }

    const complaintLab = document.getElementById('complaintLab');
    const complaintWorkstation = document.getElementById('complaintWorkstation');
    if (complaintLab && complaintWorkstation) {
        const filterComplaintWorkstations = () => {
            const options = [...complaintWorkstation.options].filter((option) => option.value !== '');
            options.forEach((option) => {
                option.hidden = option.dataset.lab !== complaintLab.value;
                option.disabled = option.hidden;
            });
            const available = options.find((option) => !option.hidden);
            if (available) complaintWorkstation.value = available.value;
        };
        complaintLab.addEventListener('change', filterComplaintWorkstations);
        filterComplaintWorkstations();
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

    // Confirmation popups
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.confirm)) event.preventDefault();
        });
    });
});
