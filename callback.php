<?php
/** SMS 2 sends the student back here with a one-time code; swap it server-to-server for tokens. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';

$expected = (string) ($_SESSION['oauth_state'] ?? '');
unset($_SESSION['oauth_state']);
$state = (string) ($_GET['state'] ?? '');

if ($expected === '' || !hash_equals($expected, $state)) {
    portal_flash('The sign-in link expired or did not start here. Please try again.', 'warning');
    portal_redirect(portal_url('login.php'));
}
if (($_GET['error'] ?? '') === 'access_denied') {
    portal_flash('This portal is for student accounts only. Staff and faculty should use SMS 2 directly.', 'danger');
    portal_redirect(portal_url('login.php'));
}
$code = (string) ($_GET['code'] ?? '');
if ($code === '') {
    portal_flash('Sign-in did not complete. Please try again.', 'warning');
    portal_redirect(portal_url('login.php'));
}

$res = portal_http('POST', PORTAL_API_URL . '/auth/token', [portal_client_auth_header()], [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => portal_url('callback.php'),
]);
if ($res['status'] !== 200 || empty($res['body']['access_token'])) {
    error_log('Portal token exchange failed: HTTP ' . $res['status'] . ' ' . $res['error'] . ' ' . json_encode($res['body']));
    portal_flash('Sign-in could not be completed. Please try again.', 'danger');
    portal_redirect(portal_url('login.php'));
}

portal_complete_login($res['body']);
portal_redirect(portal_url('index.php'));
