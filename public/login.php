<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/auth.php';

startLmsSession();
$pageTitle = 'Sign in | Laboratory Management System';
$db = getDbOrNull();
$email = '';
$errorMessage = '';
$nextPage = trim((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($db) {
    $signedInUser = getAuthenticatedUser($db);
    if ($signedInUser) {
        header('Location: ' . safeNextPage($nextPage, $signedInUser['role']));
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!isValidLmsCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
        $errorMessage = 'This form expired. Refresh the page and try again.';
    } elseif (!$db) {
        $errorMessage = 'The database is unavailable. Check the PostgreSQL connection and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errorMessage = 'Enter a valid email and password.';
    } else {
        $statement = $db->prepare('SELECT user_id, password_hash, role FROM users WHERE LOWER(email) = :email');
        $statement->execute(['email' => $email]);
        $account = $statement->fetch();

        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['user_id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: ' . safeNextPage($nextPage, $account['role']));
            exit;
        }
        $errorMessage = 'Email or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script>
        try {
            const savedTheme = localStorage.getItem('lms_theme');
            document.documentElement.dataset.theme = savedTheme === 'light' || savedTheme === 'dark'
                ? savedTheme
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (error) {
            document.documentElement.dataset.theme = 'light';
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-panel">
        <div class="auth-toolbar">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark theme" aria-pressed="false" title="Switch to dark theme">
                <svg class="theme-icon theme-icon-moon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.5 15.5A8.5 8.5 0 018.5 3.5a8.5 8.5 0 1012 12z"/></svg>
                <svg class="theme-icon theme-icon-sun" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
            </button>
        </div>
        <a class="auth-brand" href="login.php" aria-label="Laboratory LMS sign in">
            <span class="brand-icon" aria-hidden="true">L</span>
            <span>
                <strong>Laboratory LMS</strong>
                <small>Educational laboratories</small>
            </span>
        </a>
        <div class="auth-heading">
            <p class="auth-kicker">Workspace access</p>
            <h1>Sign in</h1>
            <p>Use your registered institutional account.</p>
        </div>

        <?php if ($flashMessage): ?>
            <div class="notice-banner <?= $flashMessage['type'] === 'success' ? 'success' : 'error' ?>" role="status">
                <?= htmlspecialchars($flashMessage['message']) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="notice-banner error" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(lmsCsrfToken()) ?>">
            <input type="hidden" name="next" value="<?= htmlspecialchars($nextPage, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label class="form-label" for="loginEmail">Email address</label>
                <input class="form-control" id="loginEmail" name="email" type="email" autocomplete="username" maxlength="100" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <input class="form-control" id="loginPassword" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
        </form>
        <p class="auth-footnote">Your access is based on the role assigned to your account.</p>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
