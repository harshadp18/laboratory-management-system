<?php
$pageTitle = $pageTitle ?? 'Laboratory Management System';
$currentPage = basename($_SERVER['PHP_SELF']);
$navigationItems = [
    'index.php' => 'Dashboard', 'labs.php' => 'Laboratories', 'bookings.php' => 'Bookings',
    'complaints.php' => 'Complaints', 'maintenance.php' => 'Maintenance', 'reports.php' => 'Reports',
];
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
<header class="site-header">
    <div class="brand">Laboratory Management System</div>
    <nav aria-label="Main navigation">
        <?php foreach ($navigationItems as $file => $label): ?>
            <a class="<?= $currentPage === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main class="container">
