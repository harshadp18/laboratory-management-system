<?php
$pageTitle = 'Maintenance Logs | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$allTasks = getMaintenanceTasks();
$scheduledTasks = array_filter($allTasks, fn($t) => $t['status'] === 'Scheduled');
$inProgressTasks = array_filter($allTasks, fn($t) => $t['status'] === 'In Progress');
$completedTasks = array_filter($allTasks, fn($t) => $t['status'] === 'Completed');
$canManageMaintenance = $currentUser['role'] === 'Lab Assistant';
$labs = getLaboratories();
$workstationsByLab = [];
foreach ($labs as $labId => $lab) {
    $workstationsByLab[$labId] = getWorkstations($labId);
}
$complaints = getComplaints();
?>

<div class="page-header" style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Maintenance logs</h1>
        <p class="page-subtitle">Schedule, track and complete workstation maintenance.</p>
    </div>
    <?php if ($canManageMaintenance): ?>
    <div>
        <button class="btn btn-primary" type="button" onclick="document.getElementById('logMaintModal').classList.add('open')">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Log Maintenance
        </button>
    </div>
    <?php endif; ?>
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
                    <?php if ($canManageMaintenance && !empty($task['maintenance_id'])): ?>
                        <form method="post" class="maintenance-transition-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="advance_maintenance">
                            <input type="hidden" name="maintenance_id" value="<?= (int) $task['maintenance_id'] ?>">
                            <input type="hidden" name="status" value="In Progress">
                            <button class="btn btn-outline" type="submit">Start task</button>
                        </form>
                    <?php endif; ?>
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
                    <?php if ($canManageMaintenance && !empty($task['maintenance_id'])): ?>
                        <form method="post" class="maintenance-transition-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="advance_maintenance">
                            <input type="hidden" name="maintenance_id" value="<?= (int) $task['maintenance_id'] ?>">
                            <input type="hidden" name="status" value="Completed">
                            <button class="btn btn-outline" type="submit">Complete task</button>
                        </form>
                    <?php endif; ?>
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
    <span>Use the Start task and Complete task actions to move maintenance through Scheduled, In Progress, and Completed.</span>
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

        <form method="post" style="display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem; flex-grow: 1;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create_maintenance">
            <div class="form-group">
                <label class="form-label" for="maintenanceWorkstation">Workstation *</label>
                <select class="form-control" id="maintenanceWorkstation" name="workstation" required>
                    <?php foreach ($workstationsByLab as $labId => $workstations): ?>
                        <?php foreach ($workstations as $workstation): ?>
                            <?php $workstationNo = $workstation['workstation_no'] ?? substr($workstation['code'], strrpos($workstation['code'], ' - ') + 3); ?>
                            <option value="<?= htmlspecialchars($labId . '|' . $workstationNo) ?>">
                                <?= htmlspecialchars($workstation['code'] . ($workstation['status'] === 'Under Repair' ? ' (Under Repair)' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="maintenanceType">Maintenance Type *</label>
                <select class="form-control" id="maintenanceType" name="maintenance_type" required>
                    <option value="Corrective">Corrective Maintenance</option>
                    <option value="Preventive">Preventive Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="maintenanceStartTime">Scheduled date and time *</label>
                <input class="form-control" id="maintenanceStartTime" name="start_time" type="datetime-local" min="<?= date('Y-m-d\TH:i') ?>" value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="maintenanceTitle">Task Title *</label>
                <input class="form-control" id="maintenanceTitle" name="title" type="text" maxlength="200" placeholder="e.g. Inspect display connection" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="maintenanceDescription">Description / Instructions *</label>
                <textarea class="form-control" id="maintenanceDescription" name="description" rows="3" maxlength="5000" placeholder="Actionable work steps..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="maintenanceComplaint">Linked Complaint (Optional)</label>
                <select class="form-control" id="maintenanceComplaint" name="complaint_id">
                    <option value="">None / Preventive</option>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php if (!empty($complaint['complaint_id']) && in_array($complaint['status'], ['Open', 'In Progress'], true)): ?>
                            <option value="<?= (int) $complaint['complaint_id'] ?>"><?= htmlspecialchars($complaint['code'] . ' · ' . $complaint['title']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
