<?php
/** Published announcements for students. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';
require_once __DIR__ . '/src/core/layout.php';
$user = portal_require_login();
$items = portal_api('announcements') ?? [];
portal_header('Announcements', 'announcements', $user);
?>
<?php if (!$items): ?><section class="portal-card"><?= portal_empty('No announcements right now.') ?></section><?php endif; ?>
<?php foreach ($items as $a): ?>
    <article class="portal-card mb-3">
        <div class="d-flex justify-content-between"><h2 class="h5 mb-1"><?= e($a['title']) ?></h2><span class="badge text-bg-light"><?= e($a['category']) ?></span></div>
        <p class="small text-muted mb-2"><?= e($a['posted_by']) ?> · <?= e($a['posted_at']) ?></p>
        <div><?= nl2br(e($a['body'])) ?></div>
    </article>
<?php endforeach; ?>
<?php portal_footer();
