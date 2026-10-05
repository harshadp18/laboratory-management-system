<?php
$pageTitle = 'Lab Assistant Dashboard | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$stats = getSystemStats();
$labs = getLaboratories();
$recentActivities = getRecentActivity();
$allBookings = getBookings();
$pendingBookings = array_filter($allBookings, fn($b) => $b['status'] === 'Pending');
?>

<div class="page-header">
    <h1 class="page-title">Lab Assistant Dashboard</h1>
    <p class="page-subtitle">Your control center for bookings, complaints and lab maintenance.</p>
</div>

<!-- 4 Key Stat Cards (Image 4 & Screen 1) -->
<section class="stats-grid" aria-label="Quick statistics">
    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Pending Bookings</span>
            <div class="stat-icon-wrap blue">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
        <div class="stat-subtext">Awaiting your approval</div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Open Complaints</span>
            <div class="stat-icon-wrap red">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= $stats['open_complaints'] ?></div>
        <div class="stat-subtext"><?= htmlspecialchars($stats['open_complaints_detail']) ?></div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Systems Under Repair</span>
            <div class="stat-icon-wrap amber">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= $stats['systems_under_repair'] ?></div>
        <div class="stat-subtext"><?= htmlspecialchars($stats['systems_under_repair_detail']) ?></div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Available Labs</span>
            <div class="stat-icon-wrap green">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= $stats['available_labs'] ?> / <?= $stats['total_labs'] ?></div>
        <div class="stat-subtext"><?= htmlspecialchars($stats['available_detail']) ?></div>
    </article>
</section>

<!-- Quick Action Buttons -->
<section class="quick-actions-bar">
    <div class="quick-actions-left">
        <span class="quick-actions-label">Quick actions</span>
        <a class="btn btn-primary" href="maintenance.php">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Log Maintenance
        </a>
        <a class="btn btn-outline" href="#pending-bookings">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Approve Booking
        </a>
        <a class="btn btn-outline" href="complaints.php">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Report Issue
        </a>
    </div>
    <div class="date-stamp"><?= date('l, d F Y') ?></div>
</section>

<!-- Two-Column Section: Recent Activity & Lab Availability -->
<div class="dashboard-grid">
    <!-- Left: Recent Activity Feed -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h2 class="card-panel-title">Recent activity</h2>
            <span class="card-panel-tag">Today</span>
        </div>
        <div class="activity-feed">
            <?php foreach ($recentActivities as $item): ?>
                <div class="activity-item">
                    <div class="activity-icon" style="background: <?= $item['color'] ?>15; color: <?= $item['color'] ?>;">
                        <?php if ($item['icon'] === 'complaint'): ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <?php elseif ($item['icon'] === 'escalate'): ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                        <?php elseif ($item['icon'] === 'wrench'): ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php elseif ($item['icon'] === 'calendar'): ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php else: ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="activity-meta"><?= htmlspecialchars($item['subtitle']) ?></div>
                    </div>
                    <div class="activity-time"><?= htmlspecialchars($item['time']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: Lab Availability Card -->
    <div class="card-panel">
        <div class="card-panel-header">
            <h2 class="card-panel-title">Lab availability</h2>
            <span class="card-panel-tag">Right now</span>
        </div>
        <div class="lab-availability-list">
            <?php foreach ($labs as $lab): ?>
                <div class="lab-item-row">
                    <div class="lab-item-info">
                        <svg class="lab-item-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <div class="lab-item-name"><?= htmlspecialchars($lab['code']) ?></div>
                            <div class="lab-item-sub"><?= htmlspecialchars($lab['room']) ?> &middot; <?= $lab['capacity'] ?> seats</div>
                        </div>
                    </div>
                    <span class="status-pill <?= strtolower($lab['status']) ?>">
                        <span class="status-dot"></span>
                        <?= htmlspecialchars($lab['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="panel-footer-note">
            Availability reflects current bookings and scheduled lab maintenance.
        </div>
    </div>
</div>

<!-- Bottom Section: Bookings Awaiting Approval -->
<section class="card-panel" id="pending-bookings">
    <div class="card-panel-header">
        <h2 class="card-panel-title">Bookings awaiting approval</h2>
        <span class="card-panel-tag"><?= count($pendingBookings) ?> pending requests</span>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Faculty / request</th>
                    <th>Lab & session</th>
                    <th>Status</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingBookings as $booking): ?>
                    <tr>
                        <td>
                            <div class="table-primary-text"><?= htmlspecialchars($booking['faculty']) ?></div>
                            <div class="table-sub-text"><?= htmlspecialchars($booking['code']) ?> &middot; <?= htmlspecialchars($booking['purpose']) ?></div>
                        </td>
                        <td>
                            <div class="table-primary-text"><?= htmlspecialchars($booking['lab']) ?></div>
                            <div class="table-sub-text"><?= htmlspecialchars($booking['date']) ?> &middot; <?= htmlspecialchars($booking['time']) ?></div>
                        </td>
                        <td>
                            <span class="status-pill pending">
                                <span class="status-dot"></span>
                                <?= htmlspecialchars($booking['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-links">
                                <a class="action-link primary" href="javascript:void(0)" onclick="showToast('Booking <?= $booking['code'] ?> approved.')">Approve</a>
                                <a class="action-link danger" href="javascript:void(0)" onclick="showToast('Booking <?= $booking['code'] ?> rejected.')">Reject</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
