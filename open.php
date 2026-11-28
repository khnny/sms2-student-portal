<?php
/**
 * Open an SMS 2 student page (an action not yet moved to the portal) as the
 * same student, without a second login. Only fixed action keys are allowed.
 */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';
portal_require_login();

$actions = portal_sms2_actions();
$key = (string) ($_GET['to'] ?? '');
if (!isset($actions[$key])) {
    portal_redirect(portal_url('index.php'));
}
if (empty($actions[$key]['path'])) {
    // This action is done fully inside the portal now.
    portal_redirect(portal_url('crad/step.php?do=' . rawurlencode($key)));
}
$ticket = portal_api('auth/ticket', 'POST', ['path' => $actions[$key]['path']]);
if (!is_array($ticket) || empty($ticket['url'])) {
    portal_flash('That page could not be opened right now. Please try again.', 'danger');
    portal_redirect(portal_url('index.php'));
}
portal_redirect((string) $ticket['url']);
