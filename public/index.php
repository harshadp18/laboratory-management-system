<?php
$pageTitle = 'Dashboard | Laboratory Management System';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/database.php';
$statistics = ['Laboratories' => 'laboratories', 'Workstations' => 'workstations', 'Pending bookings' => "bookings WHERE status = 'Pending'", 'Open complaints' => "complaints WHERE status <> 'Resolved'"];
$results = [];
$connectionError = null;
try {
    $database = databaseConnection();
    foreach ($statistics as $label => $source) {
        $results[$label] = (int) $database->query("SELECT COUNT(*) FROM {$source}")->fetchColumn();
    }
} catch (Throwable $exception) {
    $connectionError = $exception->getMessage();
}
?>
<section class="hero">
    <h1>Laboratory Management Dashboard</h1>
    <p>Use this interface to demonstrate PostgreSQL tables, relationships, constraints, queries, and views.</p>
</section>
<?php if ($connectionError): ?>
    <section class="notice warning"><h2>Database not connected yet</h2><p><?= htmlspecialchars($connectionError) ?></p></section>
<?php else: ?>
    <section class="cards" aria-label="Database summary">
        <?php foreach ($results as $label => $value): ?>
            <article class="card"><h2><?= htmlspecialchars($label) ?></h2><p><?= $value ?></p></article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<section><h2>Project scope</h2><p>The system manages laboratories, workstations, bookings, complaints, and maintenance work. The application is intentionally small so that the PostgreSQL database remains the central part of the project.</p></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
