<?php
$pageTitle = 'Student Ticketing & Complaints | Laboratory Management System';
require_once __DIR__ . '/../includes/header.php';

$complaints = getComplaints();
$labs = getLaboratories();
?>

<div class="page-header">
    <h1 class="page-title">Report an issue</h1>
    <p class="page-subtitle">Tell us what's not working. Your Lab Assistant will help you resolve it.</p>
</div>

<!-- New Complaint Form Card (Image 1) -->
<section class="card-panel" style="margin-bottom: 2rem;">
    <div class="card-panel-header">
        <h2 class="card-panel-title">New complaint</h2>
        <span class="card-panel-tag">Required fields marked *</span>
    </div>

    <form style="padding: 1.5rem;" data-ajax-toast="Complaint submitted successfully. Ticket C-1049 created.">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
            <div class="form-group">
                <label class="form-label" for="complaintLab">Lab *</label>
                <select class="form-control" id="complaintLab" required>
                    <option value="Lab A">Lab A - Main building, Room 201</option>
                    <option value="Lab B">Lab B - Room 202</option>
                    <option value="Lab C">Lab C - Room 301</option>
                    <option value="Lab D">Lab D - Room 302</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="complaintWorkstation">Workstation *</label>
                <select class="form-control" id="complaintWorkstation" required>
                    <option value="Lab A - WS-05">Lab A - WS-05</option>
                    <option value="Lab A - WS-01">Lab A - WS-01</option>
                    <option value="Lab A - WS-02">Lab A - WS-02</option>
                    <option value="Lab A - WS-03">Lab A - WS-03</option>
                    <option value="Lab A - WS-04">Lab A - WS-04</option>
                    <option value="Lab A - WS-06">Lab A - WS-06</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label" for="problemDescription">Problem description *</label>
            
            <!-- Clean Rich Text Toolbar -->
            <div class="editor-toolbar">
                <button class="toolbar-btn" type="button" title="Bold" onclick="document.execCommand('bold')"><b>B</b></button>
                <button class="toolbar-btn" type="button" title="Italic" onclick="document.execCommand('italic')"><i>I</i></button>
                <button class="toolbar-btn" type="button" title="Underline" onclick="document.execCommand('underline')"><u>U</u></button>
                <span style="color: var(--border); margin: 0 4px;">|</span>
                <button class="toolbar-btn" type="button" title="Bullet List">&bull;&equiv;</button>
                <button class="toolbar-btn" type="button" title="Numbered List">1&equiv;</button>
                <button class="toolbar-btn" type="button" title="Link">&#128279;</button>
            </div>
            
            <textarea class="editor-textarea" id="problemDescription" rows="4" required placeholder="Describe the problem...">The front USB ports on WS-05 are not detecting my pen drive. I tried both ports and restarted the computer, but the issue persists.</textarea>
            
            <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.35rem;">
                Include the symptoms and any steps you have already tried.
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid var(--border-light);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                <svg width="16" height="16" fill="none" stroke="var(--primary)" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Assigned to the Lab Assistant first</span>
            </div>

            <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Submit complaint
            </button>
        </div>
    </form>
</section>

<!-- My Tickets List Section (Image 1) -->
<section class="card-panel">
    <div class="card-panel-header">
        <h2 class="card-panel-title">My tickets</h2>
        <span class="card-panel-tag"><?= count($complaints) ?> tickets &middot; Riya Patel</span>
    </div>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket / workstation</th>
                    <th>raised_at &middot; IST</th>
                    <th>resolved_at &middot; IST</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $c): ?>
                    <tr>
                        <td>
                            <div class="table-primary-text"><?= htmlspecialchars($c['title']) ?></div>
                            <div class="table-sub-text">
                                <?= htmlspecialchars($c['code']) ?> &middot;
                                <?= htmlspecialchars($c['workstation']) ?>
                                <?php if ($c['assigned_to']): ?>
                                    <span style="color: var(--text-subtle);">| <?= htmlspecialchars($c['assigned_to']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($c['raised_at']) ?></td>
                        <td><?= $c['resolved_at'] ? htmlspecialchars($c['resolved_at']) : '&mdash; Not yet resolved' ?></td>
                        <td>
                            <span class="status-pill <?= strtolower(str_replace(' ', '-', $c['status'])) ?>">
                                <span class="status-dot"></span>
                                <?= htmlspecialchars($c['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel-footer-note" style="display: flex; align-items: flex-start; gap: 0.5rem;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 1px;">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="16" x2="12" y2="12" stroke-width="2"/>
            <line x1="12" y1="8" x2="12.01" y2="8" stroke-width="2"/>
        </svg>
        <span>The Lab Assistant triages and resolves routine complaints. Network/server faults or requests requiring higher authority are escalated to the Administrator after triage.</span>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
