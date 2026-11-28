<?php
/** My research: the CRAD Research Hub. Every CRAD step on one page, from SMS 2. */
declare(strict_types=1);
require_once __DIR__ . '/../src/core/bootstrap.php';
require_once __DIR__ . '/../src/core/layout.php';
require_once __DIR__ . '/../src/modules/crad/research-hub.php';
$user = portal_require_login();

$statement = portal_api('me/research/statement');

// The summary hero (progress, next step) lives on the Dashboard, so this page shows only the steps.
// Step buttons open the matching action page inside the portal (same tab, same look).
$actionHref = static fn(array $step): ?string => 'crad/step.php?do=' . rawurlencode(portal_step_for_key((string) $step['key']));

portal_header('Research Workspace', 'crad/index.php', $user);
?>
<link rel="stylesheet" href="assets/css/crad/research-statement.css?v=1">
<link rel="stylesheet" href="assets/css/crad/research-hub.css?v=3">
<?php if (is_array($statement) && !empty($statement['steps'])): ?>
    <?= smsRenderResearchHub($statement, '', $actionHref, false) ?>
<?php else: ?>
    <?= portal_empty('Your research steps could not be loaded right now. Please refresh the page or try again later.') ?>
<?php endif; ?>
<script src="assets/js/crad/research-hub.js?v=2" defer></script>
<?php portal_footer();
