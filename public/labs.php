<?php
$pageTitle = 'Inventory & Workstation Status | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$labs = getLaboratories();
$selectedLabId = $_GET['lab'] ?? 'LAB-A';
$selectedLab = $labs[$selectedLabId] ?? $labs['LAB-A'];
$workstations = getWorkstations($selectedLabId);
$components = getWorkstationComponents('Lab A - WS-03');
?>

<div class="page-header">
    <h1 class="page-title">Inventory & workstation status</h1>
    <p class="page-subtitle">View your laboratories, workstations and installed hardware.</p>
</div>

<!-- Lab Selector Tabs (Image 2) -->
<section class="lab-tabs-row" aria-label="Laboratory selector">
    <?php foreach ($labs as $id => $lab): ?>
        <a class="lab-tab-card <?= $selectedLabId === $id ? 'active' : '' ?>" href="?lab=<?= urlencode($id) ?><?= $activeRole ? '&role=' . htmlspecialchars($activeRole) : '' ?>">
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
    <div>
        <div class="lab-summary-title"><?= htmlspecialchars($selectedLab['code']) ?></div>
        <div class="lab-summary-sub">
            <?= htmlspecialchars($selectedLab['building']) ?> &middot;
            <?= htmlspecialchars($selectedLab['floor']) ?> &middot;
            <?= htmlspecialchars($selectedLab['room']) ?> &middot;
            Capacity <?= $selectedLab['capacity'] ?>
        </div>
    </div>
    <div class="lab-summary-badges">
        <span class="status-pill working">
            <span class="status-dot"></span>
            <?= $selectedLab['working_count'] ?> Working
        </span>
        <?php if ($selectedLab['repair_count'] > 0): ?>
            <span class="status-pill under-repair">
                <span class="status-dot"></span>
                <?= $selectedLab['repair_count'] ?> Under Repair
            </span>
        <?php endif; ?>
    </div>
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
    <?php foreach ($workstations as $ws): ?>
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
                <a class="view-details-link" href="javascript:void(0)"
                   onclick="openComponentDetails('<?= htmlspecialchars($ws['code']) ?>', '<?= htmlspecialchars($ws['status']) ?>', '<?= $ws['issue'] ? 'C-1048 · Monitor flickers during use. Assigned to Aditi Shah, Lab Assistant. M-201 monitor inspection is scheduled.' : '' ?>')">
                    <?= $ws['status'] === 'Under Repair' ? 'Component details selected &rarr;' : 'View component details &rarr;' ?>
                </a>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<!-- Pagination Bar -->
<div class="pagination-bar">
    <div>Showing 1–6 of <?= $selectedLab['workstations_count'] ?> workstations in <?= htmlspecialchars($selectedLab['code']) ?></div>
    <div class="pagination-controls">
        <button class="page-btn" type="button" disabled>Previous</button>
        <button class="page-btn active" type="button">1</button>
        <button class="page-btn" type="button">Next</button>
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
                    <span class="modal-ws-name" id="modalWsName">Lab A - WS-03</span>
                    <span id="modalWsBadge" class="status-pill under-repair">
                        <span class="status-dot"></span> Under Repair
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Main building &middot; Floor 2 &middot; Room 201
                </div>
            </div>
            <button class="btn btn-outline" style="padding: 0.35rem 0.65rem;" data-close-modal aria-label="Close modal">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Alert banner for linked complaint/repair -->
            <div class="modal-alert-banner" id="modalAlertBanner">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
                </svg>
                <div id="modalAlertText">
                    C-1048 &middot; Monitor flickers during use. Assigned to Aditi Shah, Lab Assistant. M-201 monitor inspection is scheduled.
                </div>
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
                    9 installed components
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
                A dash in removed_at means the component is still installed. Serial numbers and component records shown are illustrative demo data.
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline" type="button" data-close-modal>Close details</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
