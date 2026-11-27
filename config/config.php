<?php
/**
 * SMS2 Student Portal - configuration.
 *
 * Copy config/local.example.php to config/local.php and fill it in, or set
 * the same names as environment variables (HostForge > Environment Variables).
 * Never commit config/local.php: it holds the client and webhook secrets.
 */
declare(strict_types=1);

$portalLocal = __DIR__ . '/local.php';
if (is_readable($portalLocal)) {
    require_once $portalLocal;
}

function portal_env(string $key, string $default = ''): string
{
    if (defined($key)) {
        return (string) constant($key);
    }
    foreach ([getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null] as $value) {
        if (is_string($value) && $value !== '') {
            return $value;
        }
    }
    return $default;
}

// Where SMS 2 (CRAD) runs, e.g. http://localhost/sms2-capstone-v3 or https://sms2.example.com
defined('PORTAL_SMS2_URL') || define('PORTAL_SMS2_URL', rtrim(portal_env('PORTAL_SMS2_URL', 'http://localhost/sms2-capstone-v3'), '/'));
// API base. index.php form works with or without mod_rewrite.
defined('PORTAL_API_URL') || define('PORTAL_API_URL', rtrim(portal_env('PORTAL_API_URL', PORTAL_SMS2_URL . '/api/v1/index.php'), '/'));
// Values from: php tools/register-api-client.php student-portal ... (run in the SMS 2 folder)
defined('PORTAL_CLIENT_ID') || define('PORTAL_CLIENT_ID', portal_env('PORTAL_CLIENT_ID', 'student-portal'));
defined('PORTAL_CLIENT_SECRET') || define('PORTAL_CLIENT_SECRET', portal_env('PORTAL_CLIENT_SECRET'));
defined('PORTAL_WEBHOOK_SECRET') || define('PORTAL_WEBHOOK_SECRET', portal_env('PORTAL_WEBHOOK_SECRET'));
// Public URL of this portal, e.g. http://localhost/sms2-student-portal (auto-detected when empty)
defined('PORTAL_PUBLIC_URL') || define('PORTAL_PUBLIC_URL', rtrim(portal_env('PORTAL_PUBLIC_URL'), '/'));

// Portal's own small database (sessions stay in PHP; this stores webhook events and notifications).
defined('PORTAL_DB_HOST') || define('PORTAL_DB_HOST', portal_env('PORTAL_DB_HOST', 'localhost'));
defined('PORTAL_DB_PORT') || define('PORTAL_DB_PORT', portal_env('PORTAL_DB_PORT', '3306'));
defined('PORTAL_DB_NAME') || define('PORTAL_DB_NAME', portal_env('PORTAL_DB_NAME', 'sms2_portal'));
defined('PORTAL_DB_USER') || define('PORTAL_DB_USER', portal_env('PORTAL_DB_USER', 'root'));
defined('PORTAL_DB_PASS') || define('PORTAL_DB_PASS', portal_env('PORTAL_DB_PASS', ''));

defined('PORTAL_APP_NAME') || define('PORTAL_APP_NAME', 'BCP Student Portal');
date_default_timezone_set(portal_env('PORTAL_TIMEZONE', 'Asia/Manila'));
