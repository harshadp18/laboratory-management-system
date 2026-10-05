<?php
$pageTitle = 'Database Reports & Views | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$views = [
    [
        'name' => 'v_lab_workstation_status',
        'description' => 'Aggregates laboratory capacity, total workstations, working count, and under-repair count via outer joins.',
        'sql' => "CREATE OR REPLACE VIEW v_lab_workstation_status AS
SELECT 
    l.lab_id,
    l.lab_name,
    l.capacity,
    COUNT(w.workstation_id) AS total_workstations,
    COUNT(CASE WHEN w.status = 'Working' THEN 1 END) AS working_count,
    COUNT(CASE WHEN w.status = 'Under Repair' THEN 1 END) AS under_repair_count
FROM laboratories l
LEFT JOIN workstations w ON l.lab_id = w.lab_id
GROUP BY l.lab_id, l.lab_name, l.capacity;",
        'sample_headers' => ['Lab Code', 'Lab Name', 'Capacity', 'Total Systems', 'Working', 'Under Repair'],
        'sample_rows' => [
            ['LAB-A', 'Lab A - Main building, Room 201', '30', '30', '29', '1'],
            ['LAB-B', 'Lab B - Room 202', '24', '24', '23', '1'],
            ['LAB-C', 'Lab C - Room 301', '20', '20', '19', '1'],
            ['LAB-D', 'Lab D - Room 302', '20', '20', '20', '0'],
        ],
    ],
    [
        'name' => 'v_open_complaints',
        'description' => 'Joins complaints with workstation location, reporting user, and assigned lab assistant for fast triage.',
        'sql' => "CREATE OR REPLACE VIEW v_open_complaints AS
SELECT 
    c.complaint_id,
    c.complaint_code,
    c.title,
    w.workstation_code,
    l.lab_name,
    u_student.full_name AS student_name,
    u_assistant.full_name AS assigned_assistant,
    c.status,
    c.raised_at
FROM complaints c
JOIN workstations w ON c.workstation_id = w.workstation_id
JOIN laboratories l ON w.lab_id = l.lab_id
JOIN users u_student ON c.student_id = u_student.user_id
LEFT JOIN users u_assistant ON c.assigned_to = u_assistant.user_id
WHERE c.status <> 'Resolved';",
        'sample_headers' => ['Code', 'Title', 'Workstation', 'Student', 'Assigned To', 'Status', 'Raised At'],
        'sample_rows' => [
            ['C-1048', 'Monitor flickers during use', 'Lab A / WS-03', 'Riya Patel', 'Aditi Shah', 'Open', '05 Oct 2026, 10:20'],
            ['C-1046', 'Keyboard keys not responding', 'Lab C / WS-12', 'Riya Patel', 'Aditi Shah', 'In Progress', '05 Oct 2026, 09:15'],
        ],
    ],
    [
        'name' => 'v_booking_summary',
        'description' => 'Combines faculty reservations with laboratory availability and session details for scheduling reviews.',
        'sql' => "CREATE OR REPLACE VIEW v_booking_summary AS
SELECT 
    b.booking_id,
    b.booking_code,
    u.full_name AS faculty_name,
    l.lab_name,
    b.session_date,
    b.start_time,
    b.end_time,
    b.purpose,
    b.status
FROM bookings b
JOIN users u ON b.faculty_id = u.user_id
JOIN laboratories l ON b.lab_id = l.lab_id
ORDER BY b.session_date DESC, b.start_time ASC;",
        'sample_headers' => ['Code', 'Faculty', 'Laboratory', 'Date', 'Time Slot', 'Purpose', 'Status'],
        'sample_rows' => [
            ['BK-302', 'Prof. Neha Rao', 'Lab A', '06 Oct 2026', '10:00–12:00', 'Database systems', 'Pending'],
            ['BK-303', 'Prof. Arjun Mehta', 'Lab B', '07 Oct 2026', '13:00–15:00', 'Computer networks', 'Pending'],
            ['BK-304', 'Prof. Kavya Iyer', 'Lab D', '08 Oct 2026', '09:00–11:00', 'Programming practice', 'Pending'],
            ['BK-301', 'Prof. Neha Rao', 'Lab A', '05 Oct 2026', '11:00–13:00', 'DBMS practical', 'Approved'],
        ],
    ],
];
?>

<div class="page-header">
    <h1 class="page-title">Database Reports &amp; Views</h1>
    <p class="page-subtitle">Demonstrate relational DBMS concepts: multi-table JOINs, GROUP BY aggregations, and PostgreSQL VIEWs.</p>
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
        <div class="stat-value">3</div>
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
        <div class="stat-value">96.8%</div>
        <div class="stat-subtext">91 of 94 workstations online</div>
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
        <div class="stat-value">1h 15m</div>
        <div class="stat-subtext">Average ticket triage turnaround</div>
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
        <div class="stat-value">846</div>
        <div class="stat-subtext">Across 9 hardware categories</div>
    </article>
</section>

<!-- PostgreSQL Views Showcase -->
<div style="display: flex; flex-direction: column; gap: 2rem;">
    <?php foreach ($views as $view): ?>
        <section class="card-panel">
            <div class="card-panel-header" style="background: #fafafa;">
                <div>
                    <h2 class="card-panel-title" style="font-family: monospace; font-size: 1.05rem; color: var(--primary);">
                        <?= htmlspecialchars($view['name']) ?>
                    </h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                        <?= htmlspecialchars($view['description']) ?>
                    </div>
                </div>
                <span class="status-pill completed">
                    <span class="status-dot"></span> View Ready
                </span>
            </div>

            <!-- SQL Definition Box -->
            <div style="background: #0f172a; color: #f8fafc; padding: 1rem 1.25rem; font-family: monospace; font-size: 0.775rem; line-height: 1.6; overflow-x: auto;">
                <pre style="margin: 0;"><code><?= htmlspecialchars($view['sql']) ?></code></pre>
            </div>

            <!-- Result Data Preview -->
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
                        <?php foreach ($view['sample_rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
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
    <span>These views are ready to be included in <code>database/views.sql</code> to demonstrate SQL JOINs, aggregate functions, and relational normalization in your DBMS project evaluation.</span>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
