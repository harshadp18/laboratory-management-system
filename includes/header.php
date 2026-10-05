<?php
require_once __DIR__ . '/data.php';

$pageTitle = $pageTitle ?? 'Laboratory Management System';
$currentPage = basename($_SERVER['PHP_SELF']);

// Active user context (supports custom role switch or page default)
$activeRole = $_GET['role'] ?? null;
if (!$activeRole) {
    if ($currentPage === 'bookings.php') {
        $activeRole = 'faculty';
    } elseif ($currentPage === 'complaints.php') {
        $activeRole = 'student';
    } else {
        $activeRole = 'assistant';
    }
}

$userData = [
    'assistant' => ['name' => 'Aditi Shah', 'role' => 'Lab Assistant', 'initials' => 'AS'],
    'faculty'   => ['name' => 'Prof. Neha Rao', 'role' => 'Faculty', 'initials' => 'NR'],
    'student'   => ['name' => 'Riya Patel', 'role' => 'Student', 'initials' => 'RP'],
    'admin'     => ['name' => 'System Admin', 'role' => 'Administrator', 'initials' => 'SA'],
];
$currentUser = $userData[$activeRole] ?? $userData['assistant'];

$navigationItems = [
    'index.php' => [
        'label' => 'Dashboard',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>'
    ],
    'labs.php' => [
        'label' => 'Inventory',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'
    ],
    'bookings.php' => [
        'label' => 'Bookings',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'
    ],
    'complaints.php' => [
        'label' => 'Complaints',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'
    ],
    'maintenance.php' => [
        'label' => 'Maintenance',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
    ],
    'reports.php' => [
        'label' => 'Reports',
        'icon' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
    ],
];

$breadcrumbTitle = $navigationItems[$currentPage]['label'] ?? 'Dashboard';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <!-- Left Sidebar -->
    <aside class="app-sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            <div class="brand-info">
                <span class="brand-title">Laboratory LMS</span>
                <span class="brand-sub">Educational laboratories</span>
            </div>
        </div>

        <div class="sidebar-section-label">Workspace</div>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <?php foreach ($navigationItems as $file => $item): ?>
                <a class="nav-link <?= $currentPage === $file ? 'active' : '' ?>" href="<?= $file ?><?= $activeRole ? '?role=' . htmlspecialchars($activeRole) : '' ?>">
                    <?= $item['icon'] ?>
                    <span class="nav-label"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="college-card">
                <strong>Vidyalankar Institute of Technology</strong>
                Academic year 2026–27
            </div>
            <button class="sidebar-collapse-btn" id="collapseSidebarBtn" type="button" aria-label="Collapse sidebar">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <span>Collapse sidebar</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Shell -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="breadcrumbs">
                <a href="index.php">Workspace</a>
                <span>&rsaquo;</span>
                <span class="current"><?= htmlspecialchars($breadcrumbTitle) ?></span>
            </div>

            <div class="topbar-right">
                <div class="demo-pill">
                    <span class="dot"></span>
                    <span><?= isDbConnected() ? 'PostgreSQL Connected' : 'Demo data' ?></span>
                </div>

                <div class="user-profile-btn" id="userProfileBtn">
                    <div class="user-avatar"><?= $currentUser['initials'] ?></div>
                    <div class="user-meta">
                        <span class="user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                        <span class="user-role-label"><?= htmlspecialchars($currentUser['role']) ?></span>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-subtle);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>

                    <!-- Interactive role switcher dropdown -->
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <div class="dropdown-user-header">
                            <div class="dropdown-user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <span class="dropdown-user-role-badge">&bull; <?= htmlspecialchars($currentUser['role']) ?></span>
                        </div>
                        <div class="dropdown-role-label">Switch Persona</div>
                        <div class="dropdown-role-list">
                            <a class="role-option-item <?= $activeRole === 'assistant' ? 'active' : '' ?>" href="?role=assistant">
                                <span>Aditi Shah (Assistant)</span>
                                <?= $activeRole === 'assistant' ? '&check;' : '' ?>
                            </a>
                            <a class="role-option-item <?= $activeRole === 'faculty' ? 'active' : '' ?>" href="?role=faculty">
                                <span>Prof. Neha Rao (Faculty)</span>
                                <?= $activeRole === 'faculty' ? '&check;' : '' ?>
                            </a>
                            <a class="role-option-item <?= $activeRole === 'student' ? 'active' : '' ?>" href="?role=student">
                                <span>Riya Patel (Student)</span>
                                <?= $activeRole === 'student' ? '&check;' : '' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Page Container -->
        <main class="page-container">
