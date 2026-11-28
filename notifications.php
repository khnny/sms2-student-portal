<?php
/** Notifications: live CRAD updates received by webhook, plus the SMS 2 notification list. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';
require_once __DIR__ . '/src/core/layout.php';
$user = portal_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && portal_csrf_ok()) {
    portal_db()->prepare('UPDATE portal_notifications SET read_at = NOW() WHERE read_at IS NULL AND (user_id = ? OR student_id = ?)')
        ->execute([(int) $user['id'], strtoupper((string) ($user['student_id'] ?? ''))]);
    portal_redirect(portal_url('notifications.php'));
}
$local = portal_local_notifications($user, 50);
$sms2 = portal_api('me/notifications') ?? [];
portal_header('Notifications', 'notifications', $user);
?>
<div class="row g-3">
    <div class="col-lg-7">
        <section class="portal-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 text-uppercase text-muted mb-0">Research updates</h2>
                <?php if ($local): ?>
                    <form method="post" class="m-0"><?= portal_csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Mark all read</button></form>
                <?php endif; ?>
            </div>
            <?php if ($local): ?>
                <ul class="list-unstyled mb-0 portal-feed">
                    <?php foreach ($local as $n): ?>
                        <li class="<?= $n['read_at'] ? '' : 'unread' ?>">
                            <div class="d-flex justify-content-between"><strong><?= e($n['title']) ?></strong><span class="small text-muted"><?= e(portal_date($n['created_at'], 'M j, g:i A')) ?></span></div>
                            <span class="small"><?= e($n['body']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= portal_empty('No research updates yet. They arrive here when CRAD updates your title, schedule, submissions or clearance.') ?>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="portal-card">
            <h2 class="h6 text-uppercase text-muted">From SMS 2</h2>
            <?php if ($sms2): ?>
                <ul class="list-unstyled mb-0 portal-feed">
                    <?php foreach ($sms2 as $n): ?>
                        <li><strong><?= e($n['title']) ?></strong> <span class="small text-muted"><?= e($n['time']) ?></span><br><span class="small"><?= nl2br(e($n['body'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= portal_empty('Nothing here.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php portal_footer();
