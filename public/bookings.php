<?php
$pageTitle = 'Lab Bookings | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$labs = getLaboratories();
$allBookings = getBookings();
$isFaculty = $currentUser['role'] === 'Faculty';
$myBookings = $isFaculty
    ? array_values(array_filter($allBookings, static fn($booking) => $booking['user_id'] === $currentUser['user_id']))
    : $allBookings;
$bookingHistory = getBookingHistory($isFaculty ? (int) $currentUser['user_id'] : null);
$timezone = new DateTimeZone('Asia/Kolkata');
$weekOffset = max(-52, min(52, (int) ($_GET['week'] ?? 0)));
$weekStart = (new DateTimeImmutable('monday this week', $timezone))->modify(($weekOffset >= 0 ? '+' : '') . $weekOffset . ' weeks');
$weekEnd = $weekStart->modify('+4 days');
$selectedAvailabilityLab = $_GET['lab'] ?? 'all';
$defaultBookingDate = date('Y-m-d', strtotime('+1 day'));
$calendarDays = [];
for ($day = 0; $day < 5; $day++) {
    $calendarDays[] = $weekStart->modify('+' . $day . ' days');
}
$calendarEvents = [];
foreach ($allBookings as $booking) {
    if (!in_array($booking['status'], ['Pending', 'Approved'], true)) {
        continue;
    }
    $start = DateTimeImmutable::createFromFormat('!d M Y H:i', $booking['date'] . ' ' . $booking['start_time'], $timezone);
    $end = DateTimeImmutable::createFromFormat('!d M Y H:i', $booking['date'] . ' ' . $booking['end_time'], $timezone);
    if (!$start || !$end || $start < $weekStart || $start >= $weekEnd->modify('+1 day')) {
        continue;
    }
    $hour = (int) $start->format('G');
    if ($hour < 9 || $hour > 16) {
        continue;
    }
    $calendarEvents[$start->format('Y-m-d')][$hour][] = [
        'lab_id' => $booking['lab_id'],
        'lab' => $booking['lab'],
        'class' => strtolower($booking['status']),
        'title' => $booking['purpose'],
        'time' => $start->format('H:i') . '–' . $end->format('H:i'),
        'status' => $booking['status'] . ' · ' . $booking['code'],
    ];
}
$maintenanceTasks = getMaintenanceTasks();
foreach ($maintenanceTasks as $task) {
    if (!in_array($task['status'], ['Scheduled', 'In Progress'], true)) {
        continue;
    }
    $taskLabId = $task['lab_id'] ?? null;
    if ($taskLabId === null) {
        foreach ($labs as $labKey => $lab) {
            if ($lab['code'] === $task['lab']) {
                $taskLabId = $labKey;
                break;
            }
        }
    }
    if (!empty($task['raw_start_time'])) {
        $start = new DateTimeImmutable($task['raw_start_time'], $timezone);
    } else {
        $timeLabel = preg_replace('/^(Started|Completed) /', '', $task['time']);
        if (!preg_match('/^(\d{2} [A-Za-z]{3})(?: (\d{4}))? · (\d{2}:\d{2})$/', $timeLabel, $parts)) {
            continue;
        }
        $start = DateTimeImmutable::createFromFormat('!d M Y H:i', $parts[1] . ' ' . ($parts[2] ?? date('Y')) . ' ' . $parts[3], $timezone);
    }
    if (!$start || $start < $weekStart || $start >= $weekEnd->modify('+1 day')) {
        continue;
    }
    $hour = (int) $start->format('G');
    if ($hour < 9 || $hour > 16) {
        continue;
    }
    $calendarEvents[$start->format('Y-m-d')][$hour][] = [
        'lab_id' => $taskLabId ?? 'LAB-A',
        'lab' => $task['lab'],
        'class' => 'maintenance',
        'title' => $task['title'],
        'time' => $start->format('H:i'),
        'status' => 'Maintenance · ' . $task['status'],
    ];
}
$weekLabel = $weekStart->format('d M') . ' – ' . $weekEnd->format('d M Y');
?>

<div class="page-header" style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Lab bookings</h1>
        <p class="page-subtitle">Find an available slot and request a lab for your next teaching session.</p>
        
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem;">
            <label class="form-label" style="margin: 0; white-space: nowrap;" for="viewAvailabilityLab">View availability for</label>
            <select class="form-control" id="viewAvailabilityLab" style="width: 220px;">
                <option value="all" <?= $selectedAvailabilityLab === 'all' ? 'selected' : '' ?>>All laboratories</option>
                <?php foreach ($labs as $id => $lab): ?>
                    <option value="<?= htmlspecialchars($id) ?>" <?= $selectedAvailabilityLab === $id ? 'selected' : '' ?>><?= htmlspecialchars($lab['name']) ?></option>
                <?php endforeach; ?>
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
    <div class="card-panel calendar-panel" id="bookingCalendarPanel">
        <div class="calendar-header">
            <div class="calendar-date-nav">
                <span style="font-weight: 700; font-size: 0.95rem;"><?= htmlspecialchars($weekLabel) ?></span>
                <div style="display: flex; gap: 0.25rem; margin-left: 0.5rem;">
                    <a class="btn btn-outline calendar-nav-button" aria-label="Previous week" href="?week=<?= $weekOffset - 1 ?>&amp;lab=<?= urlencode($selectedAvailabilityLab) ?>">&lsaquo;</a>
                    <a class="btn btn-outline calendar-nav-today" href="?week=0&amp;lab=<?= urlencode($selectedAvailabilityLab) ?>">Today</a>
                    <a class="btn btn-outline calendar-nav-button" aria-label="Next week" href="?week=<?= $weekOffset + 1 ?>&amp;lab=<?= urlencode($selectedAvailabilityLab) ?>">&rsaquo;</a>
                </div>
            </div>

            <div class="calendar-legend">
                <span class="legend-tag"><span class="legend-swatch available"></span> Available</span>
                <span class="legend-tag"><span class="legend-swatch approved"></span> Approved</span>
                <span class="legend-tag"><span class="legend-swatch pending"></span> Pending</span>
                <span class="legend-tag"><span class="legend-swatch maintenance"></span> Maintenance</span>
            </div>
            <button class="btn btn-outline calendar-expand-button" id="expandBookingCalendar" type="button" aria-expanded="false" aria-controls="bookingCalendarPanel">
                <svg class="expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3H5a2 2 0 00-2 2v3m13-5h3a2 2 0 012 2v3M3 16v3a2 2 0 002 2h3m13-5v3a2 2 0 01-2 2h-3"/>
                </svg>
                <span>Expand</span>
            </button>
        </div>

        <div class="data-table-wrap calendar-scroll">
            <div class="weekly-schedule-grid" id="bookingCalendar">
                <div class="schedule-header-cell">Time</div>
                <?php foreach ($calendarDays as $day): ?>
                    <div class="schedule-header-cell" data-calendar-day="<?= $day->format('Y-m-d') ?>" <?= $day->format('Y-m-d') === (new DateTimeImmutable('today', $timezone))->format('Y-m-d') ? 'style="background: #eef2ff; color: var(--primary);"' : '' ?>>
                        <?= htmlspecialchars($day->format('D d')) ?>
                    </div>
                <?php endforeach; ?>
                <?php for ($hour = 9; $hour <= 16; $hour++): ?>
                    <div class="schedule-time-label"><?= sprintf('%02d:00', $hour) ?></div>
                    <?php foreach ($calendarDays as $day): ?>
                        <div class="schedule-cell" data-calendar-cell="<?= $day->format('Y-m-d') ?>-<?= $hour ?>" data-day="<?= $day->format('Y-m-d') ?>" data-hour="<?= $hour ?>" tabindex="0" role="button" aria-label="Select <?= htmlspecialchars($day->format('l d M')) ?> at <?= sprintf('%02d:00', $hour) ?>">
                            <?php foreach ($calendarEvents[$day->format('Y-m-d')][$hour] ?? [] as $event): ?>
                                <div class="event-block <?= htmlspecialchars($event['class']) ?>" data-calendar-event data-lab="<?= htmlspecialchars($event['lab_id']) ?>" title="<?= htmlspecialchars($event['title'] . ' · ' . $event['time'] . ' · ' . $event['lab'] . ' · ' . $event['status'], ENT_QUOTES) ?>">
                                    <strong class="event-title"><?= htmlspecialchars($event['title']) ?></strong>
                                    <span class="event-time"><?= htmlspecialchars($event['time']) ?></span>
                                    <span class="event-meta"><?= htmlspecialchars($event['lab']) ?> · <?= htmlspecialchars($event['status']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>

        <div class="panel-footer-note">
            All times in IST (UTC+05:30) &middot; Select an empty hour to start a booking request
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

        <?php if ($currentUser['role'] === 'Faculty'): ?>
        <form method="post" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create_booking">
            <div class="form-group">
                <label class="form-label" for="bookingLab">Lab *</label>
                <select class="form-control" id="bookingLab" name="lab_id" required>
                    <?php foreach ($labs as $id => $lab): ?>
                        <option value="<?= htmlspecialchars($id) ?>" data-capacity="<?= (int) $lab['capacity'] ?>"><?= htmlspecialchars($lab['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.15rem;">
                    Capacity <span id="bookingLabCapacity"><?= $labs ? (int) reset($labs)['capacity'] : '—' ?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="bookingDate">Date *</label>
                <input class="form-control" id="bookingDate" name="booking_date" type="date" value="<?= htmlspecialchars($defaultBookingDate) ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label" for="bookingStartTime">Start time *</label>
                    <input class="form-control" id="bookingStartTime" name="start_time" type="time" value="13:00" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="bookingEndTime">End time *</label>
                    <input class="form-control" id="bookingEndTime" name="end_time" type="time" value="15:00" required>
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
                <input class="form-control" id="bookingPurpose" name="purpose" type="text" maxlength="2000" placeholder="e.g. Database systems practical" required>
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
        <?php else: ?>
            <div class="info-callout">Booking requests can only be submitted from a Faculty account.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Section: My Bookings Table -->
<section class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title"><?= $isFaculty ? 'My bookings' : 'All bookings' ?></h2>
        <span class="card-panel-tag"><?= htmlspecialchars($currentUser['name']) ?> &middot; <?= count($myBookings) ?> requests</span>
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
                <?php if (!$myBookings): ?>
                    <tr><td colspan="4">No booking records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($myBookings as $b): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($b['code']) ?></td>
                        <td><?= htmlspecialchars($b['lab']) ?></td>
                        <td><?= htmlspecialchars($b['date'] . ' · ' . $b['time']) ?></td>
                        <td>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $b['status'])) ?>">
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

<section class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title">Booking history</h2>
        <span class="card-panel-tag"><?= count($bookingHistory) ?> events</span>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Change</th>
                    <th>Changed by</th>
                    <th>Note</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$bookingHistory): ?>
                    <tr><td colspan="5">No booking history is available.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookingHistory as $event): ?>
                    <tr>
                        <td>
                            <div class="table-primary-text"><?= htmlspecialchars($event['code']) ?></div>
                            <div class="table-sub-text"><?= htmlspecialchars($event['purpose']) ?></div>
                        </td>
                        <td>
                            <?php if ($event['previous_status'] !== '—'): ?>
                                <?= htmlspecialchars($event['previous_status']) ?> &rarr;
                            <?php endif; ?>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $event['new_status'])) ?>">
                                <span class="status-dot"></span>
                                <?= htmlspecialchars($event['new_status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($event['changed_by']) ?></td>
                        <td><?= htmlspecialchars($event['note']) ?></td>
                        <td><?= htmlspecialchars($event['changed_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
