<?php
/**
 * Student Portal login page.
 *
 * The student types their SMS 2 email or student number and password here.
 * The portal never stores passwords: it sends them server-to-server to the
 * SMS 2 API (grant_type=password, with the client secret), and SMS 2 checks
 * them with its own lockout and Authenticator rules. Students with an
 * Authenticator then enter their 6-digit code (or an emailed code) here too.
 */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';

if (portal_logged_in()) {
    portal_redirect(portal_url('index.php'));
}

const PORTAL_MFA_TTL = 600;

/** @return array{token: string, at: int, username: string, method: string, sent_to?: string}|null */
function portal_login_mfa(): ?array
{
    $mfa = $_SESSION['login_mfa'] ?? null;
    if (!is_array($mfa) || empty($mfa['token']) || (time() - (int) ($mfa['at'] ?? 0)) > PORTAL_MFA_TTL) {
        unset($_SESSION['login_mfa']);
        return null;
    }
    return $mfa;
}

function portal_login_back(): never
{
    portal_redirect(portal_url('login.php'));
}

/** Read an SMS 2 API error message, or a safe default. */
function portal_login_error(array $res, string $fallback): string
{
    if ($res['status'] === 0) {
        return 'Sign-in is unavailable right now. Please try again in a few minutes.';
    }
    $message = (string) ($res['body']['error']['message'] ?? '');
    return $message !== '' ? $message : $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_csrf_ok()) {
        portal_flash('Your session expired. Please try again.', 'warning');
        portal_login_back();
    }
    $action = (string) ($_POST['action'] ?? 'password');

    if ($action === 'cancel') {
        unset($_SESSION['login_mfa']);
        portal_login_back();
    }

    if ($action === 'password') {
        unset($_SESSION['login_mfa']);
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['login_username'] = substr($username, 0, 190);
        if ($username === '' || $password === '') {
            portal_flash('Please enter your email or student number and your password.', 'warning');
            portal_login_back();
        }
        $res = portal_http('POST', PORTAL_API_URL . '/auth/token', [portal_client_auth_header()], [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_ip' => portal_client_ip(),
        ]);
        if ($res['status'] === 200 && !empty($res['body']['mfa_required']) && !empty($res['body']['mfa_token'])) {
            $_SESSION['login_mfa'] = ['token' => (string) $res['body']['mfa_token'], 'at' => time(), 'username' => $username, 'method' => 'authenticator'];
            portal_login_back();
        }
        if ($res['status'] === 200 && !empty($res['body']['access_token'])) {
            unset($_SESSION['login_username']);
            portal_complete_login($res['body']);
            portal_redirect(portal_url('index.php'));
        }
        if ($res['status'] !== 400 && $res['status'] !== 423) {
            error_log('Portal password sign-in failed: HTTP ' . $res['status'] . ' ' . $res['error'] . ' ' . (string) ($res['body']['error']['code'] ?? ''));
        }
        $code = (string) ($res['body']['error']['code'] ?? '');
        portal_flash(portal_login_error($res, 'Sign-in could not be completed. Please try again.'), in_array($code, ['locked', 'password_change_required', 'maintenance'], true) ? 'warning' : 'danger');
        if ($code === 'password_change_required') {
            $_SESSION['login_change_password'] = 1;
        }
        portal_login_back();
    }

    $mfa = portal_login_mfa();
    if ($mfa === null) {
        portal_flash('Your sign-in expired. Please enter your password again.', 'warning');
        portal_login_back();
    }

    if ($action === 'email') {
        $res = portal_http('POST', PORTAL_API_URL . '/auth/mfa/email', [portal_client_auth_header()], [
            'mfa_token' => $mfa['token'],
            'client_ip' => portal_client_ip(),
        ]);
        if ($res['status'] === 200 && isset($res['body']['data'])) {
            $_SESSION['login_mfa']['method'] = 'email';
            $_SESSION['login_mfa']['sent_to'] = (string) ($res['body']['data']['sent_to'] ?? '');
            portal_flash('We emailed a 6-digit code to ' . ($_SESSION['login_mfa']['sent_to'] ?: 'your email') . '.', 'info');
        } else {
            portal_flash(portal_login_error($res, 'We could not email a code right now.'), 'warning');
        }
        portal_login_back();
    }

    if ($action === 'use_authenticator') {
        $_SESSION['login_mfa']['method'] = 'authenticator';
        portal_login_back();
    }

    if ($action === 'code') {
        $res = portal_http('POST', PORTAL_API_URL . '/auth/token', [portal_client_auth_header()], [
            'grant_type' => 'mfa_code',
            'mfa_token' => $mfa['token'],
            'method' => $mfa['method'] === 'email' ? 'email' : 'authenticator',
            'code' => preg_replace('/\s+/', '', (string) ($_POST['code'] ?? '')),
            'client_ip' => portal_client_ip(),
        ]);
        if ($res['status'] === 200 && !empty($res['body']['access_token'])) {
            unset($_SESSION['login_mfa'], $_SESSION['login_username']);
            portal_complete_login($res['body']);
            portal_redirect(portal_url('index.php'));
        }
        $code = (string) ($res['body']['error']['code'] ?? '');
        if ($code !== 'invalid_code') {
            unset($_SESSION['login_mfa']);
        }
        if ($code === 'password_change_required') {
            $_SESSION['login_change_password'] = 1;
        }
        portal_flash(portal_login_error($res, 'Sign-in could not be completed. Please try again.'), $code === 'invalid_code' ? 'danger' : 'warning');
        portal_login_back();
    }

    portal_login_back();
}

$mfa = portal_login_mfa();
$flash = portal_flash();
$lastUsername = (string) ($_SESSION['login_username'] ?? '');
$showChangePassword = !empty($_SESSION['login_change_password']);
unset($_SESSION['login_change_password']);
$forgotUrl = PORTAL_SMS2_URL . '/login/forgot-password.php';
$pageTitle = $mfa ? 'Verify sign-in' : 'Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(PORTAL_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-login">
<main class="login-card">
    <img src="assets/img/bcp-crest.png" alt="" class="login-crest">
    <h1>Student Portal</h1>
    <p class="text-muted mb-4">Bestlink College of the Philippines</p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> text-start small" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($showChangePassword): ?>
        <a class="btn btn-outline-primary w-100 mb-3" href="<?= e(PORTAL_SMS2_URL . '/login/login.php') ?>">
            <i class="fa-solid fa-key me-2"></i>Set a new password on SMS 2
        </a>
    <?php endif; ?>

    <?php if ($mfa): ?>
        <form method="POST" class="text-start" novalidate>
            <?= portal_csrf_field() ?>
            <input type="hidden" name="action" value="code">
            <div class="login-step mb-3">
                <i class="fa-solid <?= $mfa['method'] === 'email' ? 'fa-envelope' : 'fa-shield-halved' ?>"></i>
                <div>
                    <strong>Two-step verification</strong>
                    <div class="small text-muted">
                        <?= $mfa['method'] === 'email'
                            ? 'Enter the 6-digit code we emailed' . (!empty($mfa['sent_to']) ? ' to ' . e($mfa['sent_to']) : '') . '.'
                            : 'Enter the 6-digit code from your Authenticator app.' ?>
                    </div>
                </div>
            </div>
            <label class="form-label small fw-semibold" for="code">Verification code</label>
            <input class="form-control form-control-lg login-code mb-3" id="code" name="code" type="text" inputmode="numeric"
                   autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" placeholder="123456" required autofocus>
            <button class="btn btn-primary btn-lg w-100" type="submit">
                <i class="fa-solid fa-check me-2"></i>Verify and sign in
            </button>
        </form>
        <div class="d-flex justify-content-between align-items-center mt-3 small">
            <form method="POST" class="m-0">
                <?= portal_csrf_field() ?>
                <?php if ($mfa['method'] === 'email'): ?>
                    <input type="hidden" name="action" value="use_authenticator">
                    <button class="btn btn-link btn-sm p-0" type="submit">Use Authenticator app</button>
                <?php else: ?>
                    <input type="hidden" name="action" value="email">
                    <button class="btn btn-link btn-sm p-0" type="submit">Email me a code instead</button>
                <?php endif; ?>
            </form>
            <form method="POST" class="m-0">
                <?= portal_csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <button class="btn btn-link btn-sm p-0 text-muted" type="submit">Back</button>
            </form>
        </div>
    <?php else: ?>
        <form method="POST" class="text-start" novalidate>
            <?= portal_csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <label class="form-label small fw-semibold" for="username">Email or student number</label>
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input class="form-control" id="username" name="username" type="text" autocomplete="username"
                       value="<?= e($lastUsername) ?>" placeholder="e.g. s23xxxxxxx" required <?= $lastUsername === '' ? 'autofocus' : '' ?>>
            </div>
            <div class="d-flex justify-content-between align-items-baseline">
                <label class="form-label small fw-semibold" for="password">Password</label>
                <a class="small" href="<?= e($forgotUrl) ?>">Forgot password?</a>
            </div>
            <div class="input-group mb-4">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input class="form-control" id="password" name="password" type="password" autocomplete="current-password"
                       required <?= $lastUsername !== '' ? 'autofocus' : '' ?>>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <button class="btn btn-primary btn-lg w-100" type="submit">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign in
            </button>
        </form>
        <p class="small text-muted mt-3 mb-0">Use the same account and password as SMS 2.</p>
    <?php endif; ?>
</main>
<script>
(function () {
    var btn = document.getElementById('togglePassword');
    var input = document.getElementById('password');
    if (!btn || !input) { return; }
    btn.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.firstElementChild.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    });
})();
</script>
</body>
</html>
