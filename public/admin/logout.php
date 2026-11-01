
<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        is_string($token) &&
        hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();

        header('Location: login.php');
        exit;
    }

    http_response_code(403);
    exit('Invalid logout request. Return to the dashboard and try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logout | Tribe Green</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="mx-auto bg-white border rounded-3 p-4" style="max-width: 420px;">
        <h1 class="h4 mb-3">Log out?</h1>
        <p class="text-muted">Are you sure you want to end your admin session?</p>
        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= adminEscape($_SESSION['admin_csrf']) ?>">
            <button type="submit" class="btn btn-danger">Yes, log out</button>
            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
