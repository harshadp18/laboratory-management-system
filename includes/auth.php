<?php
function startLmsSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    $forwardedProtocol = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $isSecureRequest = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (getenv('APP_ENV') === 'production' && $forwardedProtocol === 'https');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isSecureRequest,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function lmsCsrfToken(): string
{
    startLmsSession();
    $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function isValidLmsCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function userCanAccessPage(string $role, string $page): bool
{
    $permissions = [
        'index.php' => ['Administrator', 'Lab Assistant', 'Faculty', 'Student'],
        'labs.php' => ['Administrator', 'Lab Assistant', 'Faculty', 'Student'],
        'bookings.php' => ['Administrator', 'Lab Assistant', 'Faculty'],
        'complaints.php' => ['Administrator', 'Lab Assistant', 'Student'],
        'maintenance.php' => ['Administrator', 'Lab Assistant'],
        'reports.php' => ['Administrator', 'Lab Assistant'],
    ];
    return isset($permissions[$page]) && in_array($role, $permissions[$page], true);
}

function defaultPageForRole(string $role): string
{
    return match ($role) {
        'Faculty' => 'bookings.php',
        'Student' => 'complaints.php',
        default => 'index.php',
    };
}

function safeNextPage(string $page, string $role): string
{
    $page = basename($page);
    return userCanAccessPage($role, $page) ? $page : defaultPageForRole($role);
}

function getAuthenticatedUser(PDO $db): ?array
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId) {
        return null;
    }

    $statement = $db->prepare('SELECT user_id, email, first_name, last_name, role FROM users WHERE user_id = :user_id');
    $statement->execute(['user_id' => $userId]);
    $user = $statement->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user['user_id'] = (int) $user['user_id'];
    $user['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $user['initials'] = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
    return $user;
}

function requireAuthenticatedUser(PDO $db): array
{
    startLmsSession();
    $user = getAuthenticatedUser($db);
    if ($user) {
        return $user;
    }

    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Sign in to continue.'];
    $next = basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));
    header('Location: login.php?next=' . urlencode($next));
    exit;
}

function enforcePageRole(array $user, string $page): void
{
    if (userCanAccessPage($user['role'], $page)) {
        return;
    }

    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your account does not have access to that page.'];
    header('Location: ' . defaultPageForRole($user['role']));
    exit;
}
