<?php
/** My research: the CRAD Research Hub. Every CRAD step on one page, from SMS 2. */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
require_once __DIR__ . '/src/research-hub.php';
$user = portal_require_login();

$statement = portal_api('me/research/statement');

// SMS 2 pages open in a new tab through open.php (same student, no second login).
$openKeys = [];
foreach (portal_sms2_actions() as $key => $action) {
    $openKeys[$action['path']] = $key;
}
$actionHref = static function (array $step) use ($openKeys): ?string {
    $path = (string) ($step['action']['path'] ?? '');
    return isset($openKeys[$path]) ? 'open.php?to=' . rawurlencode($openKeys[$path]) : null;
};

portal_header('My research', 'research', $user);
?>
<link rel="stylesheet" href="assets/css/research-statement.css?v=1">
<link rel="stylesheet" href="assets/css/research-hub.css?v=2">
<?php if (is_array($statement) && !empty($statement['steps'])): ?>
    <?= smsRenderResearchHub($statement, '', $actionHref) ?>
    <p class="small text-muted mt-2"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Uploads and forms open in SMS 2 in a new tab. Come back here and refresh to see the new status.</p>
<?php else: ?>
    <?= portal_empty('Your research steps could not be loaded right now. Please refresh the page or try again later.') ?>
<?php endif; ?>
<script src="assets/js/research-hub.js?v=2" defer></script>
<?php portal_footer();
