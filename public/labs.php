<?php
$pageTitle = 'Inventory & Workstation Status | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$labs = getLaboratories();
$selectedLabId = $_GET['lab'] ?? 'LAB-A';
$selectedLab = $labs[$selectedLabId] ?? (reset($labs) ?: null);
$selectedLabId = $selectedLab['id'] ?? '';
$workstations = $selectedLab ? getWorkstations($selectedLabId) : [];
$components = getWorkstationComponents($workstations[0]['code'] ?? 'Lab A - WS-03');
?>

<div class="page-header">
    <h1 class="page-title">Inventory & workstation status</h1>
    <p class="page-subtitle">View your laboratories, workstations and installed hardware.</p>
</div>

<!-- Lab Selector Tabs (Image 2) -->
<section class="lab-tabs-row" aria-label="Laboratory selector">
    <?php foreach ($labs as $id => $lab): ?>
        <a class="lab-tab-card <?= $selectedLabId === $id ? 'active' : '' ?>" href="?lab=<?= urlencode($id) ?>">
            <svg class="lab-tab-icon" width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <div>
                <div class="lab-tab-name"><?= htmlspecialchars($lab['code']) ?></div>
                <div class="lab-tab-count"><?= $lab['workstations_count'] ?> workstations</div>
            </div>
        </a>
    <?php endforeach; ?>
</section>

<!-- Active Lab Summary Bar -->
<section class="lab-summary-bar">
    <?php if ($selectedLab): ?>
        <div>
            <div class="lab-summary-title"><?= htmlspecialchars($selectedLab['code']) ?></div>
            <div class="lab-summary-sub">
                <?= htmlspecialchars($selectedLab['building']) ?> &middot;
                <?= htmlspecialchars($selectedLab['floor']) ?> &middot;
                <?= htmlspecialchars($selectedLab['room']) ?> &middot;
                Capacity <?= (int) $selectedLab['capacity'] ?>
            </div>
        </div>
        <div class="lab-summary-badges">
            <span class="status-pill working">
                <span class="status-dot"></span>
                <?= (int) $selectedLab['working_count'] ?> Working
            </span>
            <?php if ($selectedLab['repair_count'] > 0): ?>
                <span class="status-pill under-repair">
                    <span class="status-dot"></span>
                    <?= (int) $selectedLab['repair_count'] ?> Under Repair
                </span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="lab-summary-title">No laboratory records</div>
    <?php endif; ?>
</section>

<!-- Workstation Search & Filters -->
<section class="workstation-controls">
    <div class="controls-left">
        <div class="form-group search-input-wrap">
            <label class="form-label" for="workstationSearch">Find a workstation</label>
            <input class="form-control" id="workstationSearch" type="text" placeholder="Search by workstation number" autocomplete="off">
            <button class="search-icon-btn" type="button" aria-label="Search">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" stroke-width="2"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/>
                </svg>
            </button>
        </div>

        <div class="form-group">
            <label class="form-label" for="workstationStatusFilter">Status</label>
            <select class="form-control" id="workstationStatusFilter" style="width: 170px;">
                <option value="all">All statuses</option>
                <option value="working">Working</option>
                <option value="under repair">Under Repair</option>
            </select>
        </div>
    </div>

    <div>
        <button class="btn btn-outline" type="button" style="display: flex; align-items: center; gap: 0.4rem;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span>Grid view</span>
        </button>
    </div>
</section>

<!-- Workstation Cards Grid -->
<section class="workstations-grid" id="workstationsContainer">
    <?php if (!$workstations): ?>
        <div class="info-callout">No workstation records are available for this laboratory.</div>
    <?php endif; ?>
    <?php foreach ($workstations as $ws): ?>
        <?php $componentData = htmlspecialchars(json_encode(getWorkstationComponents($ws['code']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>
        <article class="workstation-card <?= $ws['status'] === 'Under Repair' ? 'repair' : '' ?>"
                 data-name="<?= htmlspecialchars($ws['code']) ?>"
                 data-status="<?= htmlspecialchars($ws['status']) ?>">
            <div class="workstation-card-top">
                <svg width="22" height="22" fill="none" stroke="var(--text-muted)" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="status-pill <?= $ws['status'] === 'Under Repair' ? 'under-repair' : 'working' ?>">
                    <span class="status-dot"></span>
                    <?= htmlspecialchars($ws['status']) ?>
                </span>
            </div>

            <div>
                <h2 class="workstation-name"><?= htmlspecialchars($ws['code']) ?></h2>
                <div class="workstation-specs"><?= htmlspecialchars($ws['specs']) ?></div>
            </div>

            <div class="workstation-meta-block">
                <div><?= $ws['components_count'] ?> installed components</div>
                <?php if ($ws['issue']): ?>
                    <div class="workstation-fault-pill"><?= htmlspecialchars($ws['issue']) ?></div>
                <?php else: ?>
                    <div>Last serviced &middot; <?= htmlspecialchars($ws['last_serviced']) ?></div>
                <?php endif; ?>
            </div>

            <div class="workstation-card-footer">
                     <a class="view-details-link" href="#componentModal" data-component-details
                         data-ws-code="<?= htmlspecialchars($ws['code'], ENT_QUOTES) ?>"
                         data-ws-status="<?= htmlspecialchars($ws['status'], ENT_QUOTES) ?>"
                         data-issue="<?= htmlspecialchars($ws['issue'] ?? '', ENT_QUOTES) ?>"
                         data-components="<?= $componentData ?>">
                    <?= $ws['status'] === 'Under Repair' ? 'Component details selected &rarr;' : 'View component details &rarr;' ?>
                </a>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<!-- Pagination Bar -->
<div class="pagination-bar">
    <div>Showing <?= count($workstations) ?> of <?= (int) ($selectedLab['workstations_count'] ?? 0) ?> workstations<?= $selectedLab ? ' in ' . htmlspecialchars($selectedLab['code']) : '' ?></div>
    <div class="pagination-controls">
        <button class="page-btn" type="button" disabled>Previous</button>
        <button class="page-btn active" type="button" aria-current="page">1</button>
        <button class="page-btn" type="button" disabled>Next</button>
    </div>
</div>

<div class="info-callout">
    <svg class="info-callout-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke-width="2"/>
        <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
        <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
    </svg>
    <span>Working / Under Repair is derived from open complaints and maintenance records. Workstation status cannot be changed manually.</span>
</div>

<!-- Workstation Component Details Slide-in Modal (Image 6) -->
<div class="modal-backdrop" id="componentModal">
    <div class="modal-drawer">
        <div class="modal-header">
            <div class="modal-title-wrap">
                <div class="modal-title">Component details</div>
                <div class="modal-subhead">
                    <span class="modal-ws-name" id="modalWsName"><?= htmlspecialchars($workstations[0]['code'] ?? 'No workstation selected') ?></span>
                    <span id="modalWsBadge" class="status-pill <?= ($workstations[0]['status'] ?? '') === 'Under Repair' ? 'under-repair' : (($workstations[0]['status'] ?? '') === 'Working' ? 'working' : '') ?>">
                        <span class="status-dot"></span> <?= htmlspecialchars($workstations[0]['status'] ?? 'Unavailable') ?>
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    <?= $selectedLab ? htmlspecialchars($selectedLab['building'] . ' · ' . $selectedLab['floor'] . ' · ' . $selectedLab['room']) : 'No laboratory selected' ?>
                </div>
            </div>
            <button class="btn btn-outline" style="padding: 0.35rem 0.65rem;" data-close-modal aria-label="Close modal">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Alert banner for linked complaint/repair -->
            <div class="modal-alert-banner" id="modalAlertBanner" style="display: none;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
                </svg>
                <div id="modalAlertText"></div>
            </div>

            <!-- Category Filter Row -->
            <div class="modal-filter-row">
                <div class="form-group">
                    <label class="form-label" for="modalCategoryFilter">Component category</label>
                    <select class="form-control" id="modalCategoryFilter" style="width: 220px;">
                        <option value="all">All categories</option>
                        <option value="Processor">Processor</option>
                        <option value="RAM">RAM</option>
                        <option value="Storage">Storage</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Keyboard">Keyboard</option>
                        <option value="Mouse">Mouse</option>
                        <option value="Network Device">Network Device</option>
                        <option value="Peripheral">Peripheral</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1.25rem;">
                    <span id="componentCount"><?= count($components) ?> installed components</span>
                </div>
            </div>

            <!-- Installed Components Table -->
            <div class="data-table-wrap" style="border: 1px solid var(--border); border-radius: var(--radius-md);">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Brand / model</th>
                            <th>Serial number</th>
                            <th>installed_at</th>
                            <th>removed_at</th>
                        </tr>
                    </thead>
                    <tbody id="componentTableBody">
                        <?php foreach ($components as $c): ?>
                            <tr class="<?= $c['is_faulty'] ? 'tr-faulty' : '' ?>" data-category="<?= htmlspecialchars($c['category']) ?>">
                                <td style="font-weight: 600;"><?= htmlspecialchars($c['category']) ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($c['brand']) ?></div>
                                    <div style="font-size: 0.775rem; color: var(--text-muted);"><?= htmlspecialchars($c['model']) ?></div>
                                </td>
                                <td style="font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($c['serial']) ?></td>
                                <td><?= htmlspecialchars($c['installed_at']) ?></td>
                                <td><?= $c['removed_at'] ? htmlspecialchars($c['removed_at']) : '&mdash;' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="font-size: 0.775rem; color: var(--text-subtle);">
                A dash in removed_at means the component is still installed. Component records are read from PostgreSQL.
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline" type="button" data-close-modal>Close details</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
