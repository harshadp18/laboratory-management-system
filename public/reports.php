<?php
$pageTitle = 'Database Reports & Views | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$views = [
    [
        'name' => 'People and roles',
        'description' => 'Users and their role-specific faculty, student, assistant, and administrator details.',
        'query' => 'SELECT user_id, full_name, role, department, shift_timing, roll_no, year_of_study, access_level FROM v_user_directory ORDER BY user_id',
        'sample_headers' => ['ID', 'Name', 'Role', 'Department', 'Shift', 'Roll number', 'Year', 'Access level'],
        'rows' => [],
    ],
    [
        'name' => 'Phone directory',
        'description' => 'Phone numbers associated with each user account.',
        'query' => "SELECT u.first_name || ' ' || u.last_name AS full_name, u.role, p.phone_number
            FROM user_phone_numbers p JOIN users u ON u.user_id = p.user_id
            ORDER BY u.user_id, p.phone_number",
        'sample_headers' => ['Name', 'Role', 'Phone number'],
        'rows' => [],
    ],
    [
        'name' => 'Laboratory summary',
        'description' => 'Capacity and record counts for each laboratory.',
        'query' => 'SELECT lab_id, lab_name, capacity, workstation_count, booking_count, complaint_count, unresolved_complaints, maintenance_count FROM v_lab_summary ORDER BY lab_id',
        'sample_headers' => ['ID', 'Laboratory', 'Capacity', 'Workstations', 'Bookings', 'Complaints', 'Unresolved', 'Maintenance'],
        'rows' => [],
    ],
    [
        'name' => 'Installed components',
        'description' => 'Currently installed hardware by workstation, including its serial number.',
        'query' => 'SELECT lab_name, workstation_no, component_name, category, brand, model, serial_number, installed_at FROM v_workstation_inventory WHERE component_name IS NOT NULL ORDER BY lab_name, workstation_no, category',
        'sample_headers' => ['Laboratory', 'Workstation', 'Component', 'Category', 'Brand', 'Model', 'Serial number', 'Installed'],
        'rows' => [],
    ],
    [
        'name' => 'Component register',
        'description' => 'Every component record, including hardware that is not currently installed.',
        'query' => "SELECT c.component_id, c.component_name, c.category, c.brand, c.model, c.serial_number,
                l.lab_name, ic.workstation_no, ic.installed_at
            FROM components c
            LEFT JOIN installed_components ic ON ic.component_id = c.component_id AND ic.removed_at IS NULL
            LEFT JOIN laboratories l ON l.lab_id = ic.lab_id
            ORDER BY c.component_id",
        'sample_headers' => ['ID', 'Component', 'Category', 'Brand', 'Model', 'Serial number', 'Laboratory', 'Workstation', 'Installed'],
        'rows' => [],
    ],
    [
        'name' => 'Bookings',
        'description' => 'All booking requests, requesters, approvers, dates, and statuses.',
        'query' => 'SELECT booking_id, lab_name, requested_by, approved_by_name, purpose, start_time, end_time, duration, status FROM v_booking_details ORDER BY start_time DESC',
        'sample_headers' => ['ID', 'Laboratory', 'Requested by', 'Approved by', 'Purpose', 'Start', 'End', 'Duration', 'Status'],
        'rows' => [],
    ],
    [
        'name' => 'Complaint register',
        'description' => 'All submitted complaints, including resolved tickets.',
        'query' => "SELECT c.complaint_id, l.lab_name, c.workstation_no,
                reporter.first_name || ' ' || reporter.last_name AS reported_by,
                assistant.first_name || ' ' || assistant.last_name AS assigned_to,
                c.problem_description, c.escalation_reason, c.status, c.raised_at, c.resolved_at
            FROM complaints c
            JOIN laboratories l ON l.lab_id = c.lab_id
            JOIN users reporter ON reporter.user_id = c.user_id
            LEFT JOIN users assistant ON assistant.user_id = c.assigned_to
            ORDER BY c.raised_at DESC",
        'sample_headers' => ['ID', 'Laboratory', 'Workstation', 'Reported by', 'Assigned to', 'Description', 'Escalation', 'Status', 'Raised', 'Resolved'],
        'rows' => [],
    ],
    [
        'name' => 'Maintenance history',
        'description' => 'Scheduled, active, and completed maintenance tasks.',
        'query' => 'SELECT maintenance_id, lab_name, workstation_no, maintenance_type, description, performed_by_name, complaint_id, start_time, end_time, status FROM v_maintenance_history ORDER BY start_time DESC',
        'sample_headers' => ['ID', 'Laboratory', 'Workstation', 'Type', 'Description', 'Performed by', 'Complaint ID', 'Start', 'End', 'Status'],
        'rows' => [],
    ],
    [
        'name' => 'Software packages',
        'description' => 'Software catalog and license details.',
        'query' => 'SELECT software_id, software_name, version, license_type, license_expiry FROM software_packages ORDER BY software_name, version',
        'sample_headers' => ['ID', 'Software', 'Version', 'License type', 'License expiry'],
        'rows' => [],
    ],
    [
        'name' => 'Installed software',
        'description' => 'Software installed on each workstation.',
        'query' => 'SELECT lab_name, workstation_no, software_name, version, license_type, license_expiry, installed_at FROM v_workstation_software ORDER BY lab_name, workstation_no, software_name',
        'sample_headers' => ['Laboratory', 'Workstation', 'Software', 'Version', 'License type', 'License expiry', 'Installed'],
        'rows' => [],
    ],
    [
        'name' => 'Open complaints',
        'description' => 'Unresolved complaints with their reporting user and assigned assistant.',
        'query' => 'SELECT complaint_id, lab_name, workstation_no, raised_by, assigned_to_name, problem_description, escalation_reason, status, raised_at FROM v_open_complaints ORDER BY raised_at DESC',
        'sample_headers' => ['ID', 'Laboratory', 'Workstation', 'Reported by', 'Assigned to', 'Description', 'Escalation', 'Status', 'Raised'],
        'rows' => [],
    ],
];

$db = getDbOrNull();
foreach ($views as &$view) {
    $view['rows'] = [];
    $view['connected'] = false;
}
unset($view);
if ($db) {
    foreach ($views as &$view) {
        try {
            $view['rows'] = array_map('array_values', $db->query($view['query'])->fetchAll());
            $view['connected'] = true;
        } catch (Throwable $e) {
            $view['rows'] = [];
        }
    }
    unset($view);
}

$reportMetrics = [
    'view_count' => '—',
    'operational_rate' => '—',
    'operational_detail' => 'Connect PostgreSQL to load this metric',
    'resolution_time' => '—',
    'resolution_detail' => 'Connect PostgreSQL to load this metric',
    'component_count' => '—',
];
if ($db) {
    try {
        $reportMetrics['view_count'] = (int) $db->query("SELECT COUNT(*) FROM information_schema.views WHERE table_schema = 'public'")->fetchColumn();
        $totalWorkstations = (int) $db->query('SELECT COUNT(*) FROM workstations')->fetchColumn();
        $repairWorkstations = (int) $db->query("SELECT COUNT(DISTINCT (lab_id, workstation_no)) FROM complaints WHERE status IN ('Open', 'In Progress') AND workstation_no IS NOT NULL")->fetchColumn();
        $reportMetrics['operational_rate'] = $totalWorkstations > 0
            ? number_format((($totalWorkstations - $repairWorkstations) / $totalWorkstations) * 100, 1) . '%'
            : '0%';
        $reportMetrics['operational_detail'] = ($totalWorkstations - $repairWorkstations) . ' of ' . $totalWorkstations . ' workstations online';
        $averageMinutes = $db->query("SELECT AVG(EXTRACT(EPOCH FROM (resolved_at - raised_at)) / 60)
            FROM complaints WHERE resolved_at IS NOT NULL")->fetchColumn();
        if ($averageMinutes !== null) {
            $minutes = (int) round((float) $averageMinutes);
            $reportMetrics['resolution_time'] = intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm';
        }
        $reportMetrics['component_count'] = (string) $db->query('SELECT COUNT(*) FROM installed_components WHERE removed_at IS NULL')->fetchColumn();
    } catch (Throwable $e) {
        // Keep sample metrics if the database is only partially initialized.
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Database Reports &amp; Views</h1>
            <p class="page-subtitle">Records from your PostgreSQL users, laboratories, equipment, bookings, complaints, maintenance, and software.</p>
</div>

<!-- 3 Analytical Overview Cards -->
<section class="stats-grid" style="margin-bottom: 2rem;">
    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Active Database Views</span>
            <div class="stat-icon-wrap blue">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= htmlspecialchars((string) $reportMetrics['view_count']) ?></div>
        <div class="stat-subtext">Optimized relational views</div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">System Operational Rate</span>
            <div class="stat-icon-wrap green">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= htmlspecialchars($reportMetrics['operational_rate']) ?></div>
        <div class="stat-subtext"><?= htmlspecialchars($reportMetrics['operational_detail']) ?></div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Mean Resolution Time (MTTR)</span>
            <div class="stat-icon-wrap amber">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= htmlspecialchars($reportMetrics['resolution_time']) ?></div>
        <div class="stat-subtext"><?= htmlspecialchars($reportMetrics['resolution_detail']) ?></div>
    </article>

    <article class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Total Installed Hardware</span>
            <div class="stat-icon-wrap blue">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= htmlspecialchars($reportMetrics['component_count']) ?></div>
        <div class="stat-subtext">Across 9 hardware categories</div>
    </article>
</section>

<!-- Database Records -->
<div style="display: flex; flex-direction: column; gap: 2rem;">
    <?php foreach ($views as $view): ?>
        <section class="card-panel">
            <div class="card-panel-header" style="background: #fafafa;">
                <div>
                    <h2 class="card-panel-title">
                        <?= htmlspecialchars($view['name']) ?>
                    </h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                        <?= htmlspecialchars($view['description']) ?>
                    </div>
                </div>
                <span class="status-pill <?= $view['connected'] ? 'completed' : 'pending' ?>">
                    <span class="status-dot"></span> <?= $view['connected'] ? 'Live data' : 'View unavailable' ?>
                </span>
            </div>

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($view['sample_headers'] as $h): ?>
                                <th><?= htmlspecialchars($h) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$view['rows']): ?>
                            <tr><td colspan="<?= count($view['sample_headers']) ?>">No database rows available.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($view['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <?php $cell = $cell === null ? '—' : (string) $cell; ?>
                                    <td>
                                        <?php if (in_array($cell, ['Pending', 'Open', 'Under Repair'])): ?>
                                            <span class="status-pill pending"><span class="status-dot"></span> <?= htmlspecialchars($cell) ?></span>
                                        <?php elseif (in_array($cell, ['Approved', 'Completed', 'Working'])): ?>
                                            <span class="status-pill completed"><span class="status-dot"></span> <?= htmlspecialchars($cell) ?></span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($cell) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<div class="info-callout">
    <svg class="info-callout-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke-width="2"/>
        <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
        <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
    </svg>
    <span>Each table is read from the connected PostgreSQL database. Some sections use the database views defined in <code>database/views.sql</code>.</span>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
