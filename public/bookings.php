<?php
$pageTitle = 'Lab Bookings | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$labs = getLaboratories();
$selectedLab = 'Lab A - Room 201';
$myBookings = [
    [
        'code' => 'BK-302',
        'lab' => 'Lab A',
        'datetime' => '06 Oct 2026 &middot; 10:00&ndash;12:00',
        'status' => 'Pending',
        'status_class' => 'pending',
    ],
    [
        'code' => 'BK-301',
        'lab' => 'Lab A',
        'datetime' => '05 Oct 2026 &middot; 11:00&ndash;13:00',
        'status' => 'Approved',
        'status_class' => 'approved',
    ],
    [
        'code' => 'BK-298',
        'lab' => 'Lab A',
        'datetime' => '02 Oct 2026 &middot; 09:00&ndash;11:00',
        'status' => 'Completed',
        'status_class' => 'completed',
    ],
    [
        'code' => 'BK-297',
        'lab' => 'Lab B',
        'datetime' => '01 Oct 2026 &middot; 13:00&ndash;15:00',
        'status' => 'Rejected',
        'status_class' => 'rejected',
    ],
];
?>

<div class="page-header" style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Lab bookings</h1>
        <p class="page-subtitle">Find an available slot and request a lab for your next teaching session.</p>
        
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem;">
            <label class="form-label" style="margin: 0; white-space: nowrap;" for="viewAvailabilityLab">View availability for</label>
            <select class="form-control" id="viewAvailabilityLab" style="width: 220px;">
                <option value="Lab A">Lab A - Room 201</option>
                <option value="Lab B">Lab B - Room 202</option>
                <option value="Lab C">Lab C - Room 301</option>
                <option value="Lab D">Lab D - Room 302</option>
            </select>
        </div>
    </div>
    <div style="font-size: 0.825rem; color: var(--text-muted); align-self: flex-end;">
        Faculty requests &middot; Lab Assistant approves
    </div>
</div>

<!-- Main Bookings Grid (Calendar View + Booking Form Card) -->
<div class="bookings-layout">
    <!-- Left: Weekly Schedule Calendar Grid -->
    <div class="card-panel">
        <div class="calendar-header">
            <div class="calendar-date-nav">
                <span style="font-weight: 700; font-size: 0.95rem;">05–09 October 2026</span>
                <div style="display: flex; gap: 0.25rem; margin-left: 0.5rem;">
                    <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" type="button">&lsaquo;</button>
                    <button class="btn btn-outline" style="padding: 0.2rem 0.65rem;" type="button">Today</button>
                    <button class="btn btn-outline" style="padding: 0.2rem 0.5rem;" type="button">&rsaquo;</button>
                </div>
            </div>

            <div class="calendar-legend">
                <span class="legend-tag"><span class="legend-swatch available"></span> Available</span>
                <span class="legend-tag"><span class="legend-swatch approved"></span> Approved</span>
                <span class="legend-tag"><span class="legend-swatch pending"></span> Pending</span>
                <span class="legend-tag"><span class="legend-swatch maintenance"></span> Maintenance</span>
            </div>
        </div>

        <div class="data-table-wrap">
            <div class="weekly-schedule-grid">
                <!-- Day Headers -->
                <div class="schedule-header-cell">Time</div>
                <div class="schedule-header-cell" style="background: #eef2ff; color: var(--primary);">Mon 05</div>
                <div class="schedule-header-cell">Tue 06</div>
                <div class="schedule-header-cell">Wed 07</div>
                <div class="schedule-header-cell">Thu 08</div>
                <div class="schedule-header-cell">Fri 09</div>

                <!-- 09:00 Row -->
                <div class="schedule-time-label">09:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <!-- Fri 09:00-11:00 Maintenance -->
                <div class="schedule-cell" style="grid-row: span 2;">
                    <div class="event-block maintenance" style="height: 114px;">
                        <strong>Lab inspection</strong>
                        <span>09:00–11:00</span>
                        <span style="color: #64748b;">Maintenance</span>
                    </div>
                </div>

                <!-- 10:00 Row -->
                <div class="schedule-time-label">10:00</div>
                <div class="schedule-cell"></div>
                <!-- Tue 10:00-12:00 Pending BK-302 -->
                <div class="schedule-cell" style="grid-row: span 2;">
                    <div class="event-block pending" style="height: 114px;">
                        <strong>Database systems</strong>
                        <span>10:00–12:00</span>
                        <span>Pending &middot; BK-302</span>
                    </div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <!-- 11:00 Row -->
                <div class="schedule-time-label">11:00</div>
                <!-- Mon 11:00-13:00 Approved BK-301 -->
                <div class="schedule-cell" style="grid-row: span 2;">
                    <div class="event-block approved" style="height: 114px;">
                        <strong>DBMS practical</strong>
                        <span>11:00–13:00</span>
                        <span>Approved &middot; BK-301</span>
                    </div>
                </div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <!-- 12:00 Row -->
                <div class="schedule-time-label">12:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <!-- 13:00 Row -->
                <div class="schedule-time-label">13:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <!-- Fri 13:00-15:00 Selection -->
                <div class="schedule-cell" style="grid-row: span 2;">
                    <div class="event-block selection" style="height: 114px;">
                        <strong>Your selection</strong>
                        <span>13:00–15:00</span>
                    </div>
                </div>

                <!-- 14:00 Row -->
                <div class="schedule-time-label">14:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <!-- Wed 14:00-16:00 Approved BK-306 -->
                <div class="schedule-cell" style="grid-row: span 2;">
                    <div class="event-block approved" style="height: 114px;">
                        <strong>Programming lab</strong>
                        <span>14:00–16:00</span>
                        <span>Approved &middot; BK-306</span>
                    </div>
                </div>
                <div class="schedule-cell"></div>

                <!-- 15:00 Row -->
                <div class="schedule-time-label">15:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>

                <!-- 16:00 Row -->
                <div class="schedule-time-label">16:00</div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
                <div class="schedule-cell"></div>
            </div>
        </div>

        <div class="panel-footer-note">
            All times in IST (UTC+05:30) &middot; Blank slots are available
        </div>
    </div>

    <!-- Right: Request a Booking Drawer/Card (Images 5 & 7) -->
    <div class="booking-form-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.5rem;">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Request a booking</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Reserve a laboratory for your teaching session. Requests are reviewed by the Lab Assistant.
                </p>
            </div>
        </div>

        <form style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;" data-ajax-toast="Booking request BK-305 submitted for Lab Assistant review.">
            <div class="form-group">
                <label class="form-label" for="bookingLab">Lab *</label>
                <select class="form-control" id="bookingLab" required>
                    <option value="Lab A">Lab A - Room 201</option>
                    <option value="Lab B">Lab B - Room 202</option>
                    <option value="Lab C">Lab C - Room 301</option>
                    <option value="Lab D">Lab D - Room 302</option>
                </select>
                <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.15rem;">
                    Main building &middot; Floor 2 &middot; Capacity 30
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="bookingDate">Date *</label>
                <input class="form-control" id="bookingDate" type="date" value="2026-10-09" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label" for="bookingStartTime">Start time *</label>
                    <input class="form-control" id="bookingStartTime" type="time" value="13:00" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="bookingEndTime">End time *</label>
                    <input class="form-control" id="bookingEndTime" type="time" value="15:00" required>
                </div>
            </div>

            <!-- Validation Feedback Indicator (End time > Start time) -->
            <div id="timeValidationFeedback" class="validation-feedback valid">
                <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>End time is after start time &middot; 2-hour session</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="bookingPurpose">Session / purpose *</label>
                <input class="form-control" id="bookingPurpose" type="text" value="Database systems practical" placeholder="e.g. Database systems practical" required>
            </div>

            <!-- Booking Workflow Linear Indicator -->
            <div style="border-top: 1px solid var(--border-light); padding-top: 0.75rem;">
                <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase;">
                    Booking Workflow
                </div>
                <div class="workflow-indicator">
                    <span class="workflow-step" style="color: var(--amber-text); font-weight: 600;">
                        <span class="status-dot" style="background: var(--amber-dot);"></span> Pending
                    </span>
                    <span>&rarr;</span>
                    <span class="workflow-step" style="color: var(--green-text); font-weight: 600;">
                        <span class="status-dot" style="background: var(--green-dot);"></span> Approved
                    </span>
                    <span>&rarr;</span>
                    <span class="workflow-step" style="color: #059669; font-weight: 600;">
                        <span class="status-dot" style="background: #059669;"></span> Completed
                    </span>
                </div>
                <div style="font-size: 0.725rem; color: var(--text-subtle);">
                    A request may also be rejected during review.
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button class="btn btn-outline" type="reset">Cancel</button>
                <button class="btn btn-primary" id="bookingSubmitBtn" type="submit">Submit request</button>
            </div>
        </form>
    </div>
</div>

<!-- Bottom Section: My Bookings Table -->
<section class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title">My bookings</h2>
        <span class="card-panel-tag">Prof. Neha Rao &middot; <?= count($myBookings) ?> requests</span>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Lab</th>
                    <th>Date & time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($myBookings as $b): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($b['code']) ?></td>
                        <td><?= htmlspecialchars($b['lab']) ?></td>
                        <td><?= $b['datetime'] ?></td>
                        <td>
                            <span class="status-pill <?= $b['status_class'] ?>">
                                <span class="status-dot"></span>
                                <?= htmlspecialchars($b['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
