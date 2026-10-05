<?php
$pageTitle = 'Maintenance Logs | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$allTasks = getMaintenanceTasks();
$scheduledTasks = array_filter($allTasks, fn($t) => $t['status'] === 'Scheduled');
$inProgressTasks = array_filter($allTasks, fn($t) => $t['status'] === 'In Progress');
$completedTasks = array_filter($allTasks, fn($t) => $t['status'] === 'Completed');
?>

<div class="page-header" style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Maintenance logs</h1>
        <p class="page-subtitle">Schedule, track and complete workstation maintenance.</p>
    </div>
    <div>
        <button class="btn btn-primary" type="button" onclick="document.getElementById('logMaintModal').classList.add('open')">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Log Maintenance
        </button>
    </div>
</div>

<!-- Filters Bar (Image 3) -->
<section class="workstation-controls">
    <div class="controls-left">
        <div class="form-group">
            <label class="form-label" for="maintenanceTypeFilter">Maintenance type</label>
            <select class="form-control" id="maintenanceTypeFilter" style="width: 170px;">
                <option value="all">All types</option>
                <option value="corrective">Corrective</option>
                <option value="preventive">Preventive</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="maintenanceStatusFilter">Status</label>
            <select class="form-control" id="maintenanceStatusFilter" style="width: 170px;">
                <option value="all">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="in progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </div>

    <div style="font-size: 0.8rem; color: var(--text-muted); padding-bottom: 0.35rem;">
        <?= count($allTasks) ?> tasks &middot; Performed by Lab Assistant
    </div>
</section>

<!-- 3-Column Kanban Board -->
<div class="kanban-grid">
    <!-- Column 1: Scheduled -->
    <div class="kanban-column">
        <div class="kanban-col-header">
            <span style="display: flex; align-items: center; gap: 0.4rem;">
                <span class="status-dot" style="background: #64748b;"></span>
                Scheduled
            </span>
            <span class="kanban-count-badge"><?= count($scheduledTasks) ?></span>
        </div>

        <?php foreach ($scheduledTasks as $task): ?>
            <article class="kanban-card" data-type="<?= htmlspecialchars($task['type']) ?>" data-status="scheduled">
                <div class="kanban-card-top">
                    <span class="kanban-card-code"><?= htmlspecialchars($task['code']) ?></span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="status-pill <?= strtolower($task['type']) ?>"><?= htmlspecialchars($task['type']) ?></span>
                        <svg width="14" height="14" fill="none" stroke="var(--text-subtle)" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="kanban-card-title"><?= htmlspecialchars($task['title']) ?></h2>
                    <a class="kanban-card-ws" href="labs.php?lab=<?= $task['lab'] === 'Lab A' ? 'LAB-A' : ($task['lab'] === 'Lab B' ? 'LAB-B' : 'LAB-C') ?>">
                        <?= htmlspecialchars($task['workstation']) ?>
                    </a>
                </div>

                <div class="kanban-card-desc"><?= htmlspecialchars($task['description']) ?></div>

                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    </svg>
                    <span><?= htmlspecialchars($task['time']) ?></span>
                </div>

                <div class="kanban-card-footer">
                    <div>Lab Assistant &middot; <?= htmlspecialchars($task['assistant']) ?></div>
                    <div>
                        <?= $task['linked_complaint'] ? 'Linked complaint &middot; ' . htmlspecialchars($task['linked_complaint']) : 'No linked complaint &middot; Preventive task' ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Column 2: In Progress -->
    <div class="kanban-column">
        <div class="kanban-col-header">
            <span style="display: flex; align-items: center; gap: 0.4rem;">
                <span class="status-dot" style="background: var(--amber-dot);"></span>
                In Progress
            </span>
            <span class="kanban-count-badge"><?= count($inProgressTasks) ?></span>
        </div>

        <?php foreach ($inProgressTasks as $task): ?>
            <article class="kanban-card" data-type="<?= htmlspecialchars($task['type']) ?>" data-status="in progress">
                <div class="kanban-card-top">
                    <span class="kanban-card-code"><?= htmlspecialchars($task['code']) ?></span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="status-pill <?= strtolower($task['type']) ?>"><?= htmlspecialchars($task['type']) ?></span>
                        <svg width="14" height="14" fill="none" stroke="var(--text-subtle)" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="kanban-card-title"><?= htmlspecialchars($task['title']) ?></h2>
                    <a class="kanban-card-ws" href="labs.php">
                        <?= htmlspecialchars($task['workstation']) ?>
                    </a>
                </div>

                <div class="kanban-card-desc"><?= htmlspecialchars($task['description']) ?></div>

                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    </svg>
                    <span><?= htmlspecialchars($task['time']) ?></span>
                </div>

                <div class="kanban-card-footer">
                    <div>Lab Assistant &middot; <?= htmlspecialchars($task['assistant']) ?></div>
                    <div>
                        <?= $task['linked_complaint'] ? 'Linked complaint &middot; ' . htmlspecialchars($task['linked_complaint']) : 'No linked complaint &middot; Preventive task' ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Column 3: Completed -->
    <div class="kanban-column">
        <div class="kanban-col-header">
            <span style="display: flex; align-items: center; gap: 0.4rem;">
                <span class="status-dot" style="background: var(--green-dot);"></span>
                Completed
            </span>
            <span class="kanban-count-badge"><?= count($completedTasks) ?></span>
        </div>

        <?php foreach ($completedTasks as $task): ?>
            <article class="kanban-card" data-type="<?= htmlspecialchars($task['type']) ?>" data-status="completed">
                <div class="kanban-card-top">
                    <span class="kanban-card-code"><?= htmlspecialchars($task['code']) ?></span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="status-pill <?= strtolower($task['type']) ?>"><?= htmlspecialchars($task['type']) ?></span>
                        <svg width="14" height="14" fill="none" stroke="var(--text-subtle)" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="kanban-card-title"><?= htmlspecialchars($task['title']) ?></h2>
                    <a class="kanban-card-ws" href="labs.php">
                        <?= htmlspecialchars($task['workstation']) ?>
                    </a>
                </div>

                <div class="kanban-card-desc"><?= htmlspecialchars($task['description']) ?></div>

                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    </svg>
                    <span><?= htmlspecialchars($task['time']) ?></span>
                </div>

                <div class="kanban-card-footer">
                    <div>Lab Assistant &middot; <?= htmlspecialchars($task['assistant']) ?></div>
                    <div>
                        <?= $task['linked_complaint'] ? 'Linked complaint &middot; ' . htmlspecialchars($task['linked_complaint']) : 'No linked complaint &middot; Preventive task' ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<div class="info-callout">
    <svg class="info-callout-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke-width="2"/>
        <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
        <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
    </svg>
    <span>Interaction specification (static): drag a Scheduled task into In Progress. The destination card receives a subtle pulse to indicate active work. These frames do not implement drag-and-drop or animation.</span>
</div>

<!-- Log Maintenance Modal Dialog -->
<div class="modal-backdrop" id="logMaintModal">
    <div class="modal-drawer" style="max-width: 520px;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title" style="font-size: 1.25rem;">Log Maintenance Task</h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Schedule or record workstation maintenance activity.
                </div>
            </div>
            <button class="btn btn-outline" style="padding: 0.35rem 0.65rem;" onclick="document.getElementById('logMaintModal').classList.remove('open')">&times;</button>
        </div>

        <form style="display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem; flex-grow: 1;" data-ajax-toast="Maintenance task logged and scheduled." onsubmit="document.getElementById('logMaintModal').classList.remove('open')">
            <div class="form-group">
                <label class="form-label">Workstation *</label>
                <select class="form-control" required>
                    <option value="Lab A / WS-03">Lab A / WS-03 (Under Repair)</option>
                    <option value="Lab A / WS-01">Lab A / WS-01</option>
                    <option value="Lab B / WS-07">Lab B / WS-07</option>
                    <option value="Lab C / WS-12">Lab C / WS-12</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Maintenance Type *</label>
                <select class="form-control" required>
                    <option value="Corrective">Corrective Maintenance</option>
                    <option value="Preventive">Preventive Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Task Title *</label>
                <input class="form-control" type="text" placeholder="e.g. Inspect display connection" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description / Instructions *</label>
                <textarea class="form-control" rows="3" placeholder="Actionable work steps..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Linked Complaint (Optional)</label>
                <select class="form-control">
                    <option value="">None / Preventive</option>
                    <option value="C-1048">C-1048 · Monitor flickers during use</option>
                    <option value="C-1046">C-1046 · Keyboard keys not responding</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                <button class="btn btn-outline" type="button" onclick="document.getElementById('logMaintModal').classList.remove('open')">Cancel</button>
                <button class="btn btn-primary" type="submit">Schedule Task</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
