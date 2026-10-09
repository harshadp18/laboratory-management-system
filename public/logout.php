<?php
require_once __DIR__ . '/../includes/auth.php';

startLmsSession();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isValidLmsCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Invalid sign-out request.');
}

$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $cookie['path'],
    'domain' => $cookie['domain'],
    'secure' => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'] ?? 'Lax',
]);
session_destroy();
header('Location: login.php');
exit;
