<?php
/** Defense schedule with venue and panel. */
declare(strict_types=1);
require_once __DIR__ . '/../src/core/bootstrap.php';
require_once __DIR__ . '/../src/core/layout.php';
$user = portal_require_login();
$defenses = portal_api('me/defenses') ?? [];
portal_header('Defense schedule', 'crad/defenses.php', $user);
?>
<?php if (!$defenses): ?>
    <section class="portal-card"><?= portal_empty('No defense has been scheduled for your group yet.') ?>
        <a class="btn btn-sm btn-primary mt-3" href="crad/step.php?do=panel">Apply for your defense</a></section>
<?php endif; ?>
<?php foreach ($defenses as $d): ?>
    <section class="portal-card mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 class="h5 mb-1"><?= e($d['type']) ?></h2>
                <p class="text-muted mb-2"><?= e($d['research_title']) ?></p>
            </div>
            <?= portal_status_badge($d['status']) ?>
        </div>
        <div class="row small">
            <div class="col-md-4 mb-2"><i class="fa-regular fa-calendar me-2"></i><?= e(portal_date($d['starts_at'], 'D, M j, Y')) ?></div>
            <div class="col-md-4 mb-2"><i class="fa-regular fa-clock me-2"></i><?= e(portal_date($d['starts_at'], 'g:i A')) ?> – <?= e(portal_date($d['ends_at'], 'g:i A')) ?></div>
            <div class="col-md-4 mb-2"><i class="fa-solid fa-location-dot me-2"></i><?= e($d['venue'] ?: 'To be announced') ?></div>
        </div>
        <h3 class="h6 mt-2">Panel</h3>
        <?php if ($d['panel']): ?>
            <ul class="list-unstyled small mb-0">
                <?php foreach ($d['panel'] as $p): ?>
                    <li><i class="fa-regular fa-user me-2"></i><?= e($p['name']) ?><?= $p['name'] === $d['panel_chair'] ? ' <span class="badge text-bg-light">Chair</span>' : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?= portal_empty('Panel not yet assigned.') ?>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
<?php portal_footer();
