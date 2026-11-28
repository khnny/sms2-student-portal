<?php
/** Sign out of the portal and revoke the refresh token at SMS 2. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !portal_csrf_ok()) {
    portal_redirect(portal_url('index.php'));
}
$refresh = (string) ($_SESSION['tokens']['refresh'] ?? '');
if ($refresh !== '') {
    portal_http('POST', PORTAL_API_URL . '/auth/revoke', [portal_client_auth_header()], ['refresh_token' => $refresh]);
}
portal_end_session();
session_start();
portal_flash('You are signed out of the Student Portal.', 'success');
portal_redirect(portal_url('login.php'));
