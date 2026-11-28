<?php
/**
 * SMS2 Student Portal - shared bootstrap: session, helpers, database, API client.
 *
 * This app never connects to the SMS 2 / CRAD database. Every piece of
 * student data comes from the SMS 2 REST API with the student's own token.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    session_name('PORTALSESSID');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function portal_base_url(): string
{
    if (PORTAL_PUBLIC_URL !== '') {
        return PORTAL_PUBLIC_URL;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
        $host = 'localhost';
    }
    $dir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function portal_url(string $page = ''): string
{
    return portal_base_url() . '/' . ltrim($page, '/');
}

function portal_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function portal_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function portal_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(portal_csrf_token()) . '">';
}

function portal_csrf_ok(): bool
{
    $sent = (string) ($_POST['csrf'] ?? '');
    return $sent !== '' && hash_equals(portal_csrf_token(), $sent);
}

function portal_flash(?string $message = null, string $type = 'info'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function portal_date(?string $iso, string $format = 'M j, Y'): string
{
    if ($iso === null || $iso === '') {
        return '—';
    }
    $ts = strtotime($iso);
    return $ts ? date($format, $ts) : '—';
}

/* ---------------------------------------------------------------- database */

function portal_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . PORTAL_DB_HOST . ';port=' . PORTAL_DB_PORT . ';charset=utf8mb4';
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    try {
        $pdo = new PDO($dsn . ';dbname=' . PORTAL_DB_NAME, PORTAL_DB_USER, PORTAL_DB_PASS, $options);
    } catch (PDOException $e) {
        // XAMPP convenience: create the portal database on first run if the user may.
        $server = new PDO($dsn, PORTAL_DB_USER, PORTAL_DB_PASS, $options);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', PORTAL_DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new PDO($dsn . ';dbname=' . PORTAL_DB_NAME, PORTAL_DB_USER, PORTAL_DB_PASS, $options);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(__DIR__ . '/../database/portal_db.sql'));
    foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $statement) {
        if (stripos($statement, 'CREATE TABLE') !== false) {
            $pdo->exec($statement);
        }
    }
    return $pdo;
}

/* ------------------------------------------------------------- HTTP client */

/** @return array{status: int, body: array|null, error: string} */
function portal_http(string $method, string $url, array $headers = [], ?array $form = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($form !== null) {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($form);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = $raw === false ? curl_error($ch) : '';
    curl_close($ch);
    $body = is_string($raw) ? json_decode($raw, true) : null;
    return ['status' => $status, 'body' => is_array($body) ? $body : null, 'error' => $error];
}

function portal_client_auth_header(): string
{
    return 'Authorization: Basic ' . base64_encode(rawurlencode(PORTAL_CLIENT_ID) . ':' . rawurlencode(PORTAL_CLIENT_SECRET));
}

function portal_store_tokens(array $tokens): void
{
    $_SESSION['tokens'] = [
        'access' => (string) $tokens['access_token'],
        'refresh' => (string) $tokens['refresh_token'],
        'expires_at' => time() + (int) $tokens['expires_in'],
    ];
    if (isset($tokens['user']) && is_array($tokens['user'])) {
        $_SESSION['user'] = $tokens['user'];
    }
}

/** Sign the student in after SMS 2 issued tokens (login form or SSO callback). */
function portal_complete_login(array $tokens): void
{
    session_regenerate_id(true);
    portal_store_tokens($tokens);
    $user = $_SESSION['user'];
    try {
        portal_db()->prepare(
            'INSERT INTO portal_users (user_id, student_id, name, email, last_login_at) VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE student_id = VALUES(student_id), name = VALUES(name), email = VALUES(email), last_login_at = NOW()'
        )->execute([(int) $user['id'], $user['student_id'] ? strtoupper((string) $user['student_id']) : null, (string) $user['name'], (string) $user['email']]);
    } catch (Throwable $e) {
        error_log('Portal user upsert failed: ' . $e->getMessage());
    }
}

/**
 * The visitor's IP, sent to SMS 2 so its login throttle counts each student separately.
 * Behind the hosting proxy the last X-Forwarded-For entry is the one the proxy added.
 */
function portal_client_ip(): string
{
    $forwarded = array_filter(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
    $candidate = $forwarded ? (string) end($forwarded) : '';
    if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
        return $candidate;
    }
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}

function portal_logged_in(): bool
{
    return !empty($_SESSION['tokens']['access']) && !empty($_SESSION['user']['id']);
}

function portal_require_login(): array
{
    if (!portal_logged_in()) {
        portal_redirect(portal_url('login.php'));
    }
    return $_SESSION['user'];
}

function portal_end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/** Refresh the access token when it is about to expire. */
function portal_access_token(): ?string
{
    $tokens = $_SESSION['tokens'] ?? null;
    if (!is_array($tokens)) {
        return null;
    }
    if ((int) $tokens['expires_at'] - 30 > time()) {
        return (string) $tokens['access'];
    }
    $res = portal_http('POST', PORTAL_API_URL . '/auth/token', [portal_client_auth_header()], [
        'grant_type' => 'refresh_token',
        'refresh_token' => (string) $tokens['refresh'],
    ]);
    if ($res['status'] !== 200 || empty($res['body']['access_token'])) {
        return null;
    }
    portal_store_tokens($res['body']);
    return (string) $_SESSION['tokens']['access'];
}

/**
 * Call the SMS 2 API as the signed-in student.
 * On an expired or revoked sign-in the student is sent to log in again.
 *
 * @return mixed the "data" member of the response, or null when unavailable
 */
function portal_api(string $path, string $method = 'GET', ?array $form = null)
{
    $token = portal_access_token();
    if ($token === null) {
        portal_end_session();
        session_start();
        portal_flash('Your session ended. Please sign in again.', 'warning');
        portal_redirect(portal_url('login.php'));
    }
    $res = portal_http($method, PORTAL_API_URL . '/' . ltrim($path, '/'), ['Authorization: Bearer ' . $token], $form);
    if ($res['status'] === 401) {
        portal_end_session();
        session_start();
        portal_flash('Your session ended. Please sign in again.', 'warning');
        portal_redirect(portal_url('login.php'));
    }
    if ($res['status'] !== 200 || !is_array($res['body']) || !array_key_exists('data', $res['body'])) {
        error_log('Portal API ' . $method . ' ' . $path . ' failed: HTTP ' . $res['status'] . ' ' . $res['error']);
        $GLOBALS['portal_api_errors'][] = $path;
        return null;
    }
    return $res['body']['data'];
}

/* ------------------------------------------------------------ notifications */

/** Notifications that arrived by webhook for this student. */
function portal_local_notifications(array $user, int $limit = 20): array
{
    try {
        $stmt = portal_db()->prepare(
            'SELECT id, title, body, event_type, created_at, read_at FROM portal_notifications
             WHERE user_id = :uid OR (student_id IS NOT NULL AND student_id = :sid)
             ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(100, $limit))
        );
        $stmt->execute([':uid' => (int) $user['id'], ':sid' => strtoupper((string) ($user['student_id'] ?? ''))]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Portal notifications unavailable: ' . $e->getMessage());
        return [];
    }
}

/**
 * Links that open an existing SMS 2 student page (actions not yet moved to the API).
 * Keys are fixed: the portal cannot be used to open arbitrary pages.
 */
function portal_sms2_actions(): array
{
    return [
        'proposal' => ['label' => 'Research proposal / title', 'icon' => 'fa-file-signature', 'path' => '/modules/student-portal/pages/research-proposal-submission.php'],
        'group' => ['label' => 'Research group', 'icon' => 'fa-users', 'path' => '/modules/student-portal/pages/research-group.php'],
        'chapters' => ['label' => 'Submit chapters', 'icon' => 'fa-file-arrow-up', 'path' => '/modules/student-portal/pages/submit-chapters.php'],
        'progress' => ['label' => 'Progress updates', 'icon' => 'fa-chart-line', 'path' => '/modules/student-portal/pages/progress-updates.php'],
        'panel' => ['label' => 'Panel application', 'icon' => 'fa-people-group', 'path' => '/modules/student-portal/pages/panel-application.php'],
        'manuscript' => ['label' => 'Final manuscript', 'icon' => 'fa-book', 'path' => '/modules/student-portal/pages/final-manuscript.php'],
        'payment' => ['label' => 'Upload college payment', 'icon' => 'fa-receipt', 'path' => '/modules/student-portal/pages/college-payment.php'],
        'clearance' => ['label' => 'Research clearance', 'icon' => 'fa-clipboard-check', 'path' => '/modules/student-portal/pages/research-clearance.php'],
    ];
}
