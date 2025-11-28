<?php
// Minimal session helper used across API endpoints.
// Provides: start_secure_session(), require_login(), require_role(), logout_session()

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Determine secure flag based on request
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        // Use modern cookie params (PHP 7.3+ array form)
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        @session_name('ecgappsess');
        @session_set_cookie_params($cookieParams);
        @session_start();
    }
}

function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        start_secure_session();
    }

    // Non-strict mode: do not enforce authentication. If a user id is provided
    // in the request (GET/POST/JSON body), populate the session user id so
    // existing endpoints that read `$_SESSION['user_id']` continue to work.
    if (!empty($_SESSION['user_id'])) {
        return;
    }

    $userId = null;
    // Prefer request parameters
    if (isset($_REQUEST['user_id'])) {
        $userId = (int)$_REQUEST['user_id'];
    }

    // Try common JSON body shapes if not found
    if (!$userId) {
        $raw = @file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) {
                if (isset($decoded['user_id'])) $userId = (int)$decoded['user_id'];
                elseif (isset($decoded['userId'])) $userId = (int)$decoded['userId'];
                elseif (isset($decoded['user']) && is_array($decoded['user']) && isset($decoded['user']['id'])) $userId = (int)$decoded['user']['id'];
                // allow passing role too
                if (!empty($decoded['role'])) { $_SESSION['role'] = $decoded['role']; }
            }
        }
    }

    if ($userId) {
        $_SESSION['user_id'] = $userId;
        // If role provided via request, set it; otherwise leave as-is (may be null)
        if (isset($_REQUEST['role']) && $_REQUEST['role'] !== '') {
            $_SESSION['role'] = $_REQUEST['role'];
        }
        return;
    }

    // No auth required: set an anonymous user id of 0 so code paths still run.
    $_SESSION['user_id'] = 0;
    // role remains unchanged or null
}

/**
 * Require that the current session user has one of the given roles.
 * @param string|array $roles Single role or array of roles
 */
function require_role($roles): void
{
    // By default do not enforce role checks unless AUTH_REQUIRED is set.
    if (getenv('AUTH_REQUIRED') === '1') {
        if (is_string($roles)) $roles = [$roles];
        if (session_status() === PHP_SESSION_NONE) start_secure_session();
        $role = $_SESSION['role'] ?? null;
        if ($role === null || !in_array($role, $roles, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }
    }
}

function logout_session(): void
{
    if (session_status() === PHP_SESSION_NONE) start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

?>
