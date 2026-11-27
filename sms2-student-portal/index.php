<?php
/** Dashboard: research status, next defense, clearance, latest notifications and announcements. */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$user = portal_require_login();

$research = portal_api('me/research') ?? [];
$defenses = portal_api('me/defenses') ?? [];
$clearance = portal_api('me/clearance') ?? ['clearances' => [], 'payments' => []];
$announcements = array_slice(portal_api('announcements') ?? [], 0, 3);
$notifications = array_slice(portal_local_notifications($user, 5), 0, 5);

$upcoming = null;
foreach (array_reverse($defenses) as $d) {
    if (!empty($d['starts_at']) && strtotime($d['starts_at']) >= strtotime('today')) {
        $upcoming = $d;
        break;
    }
}
$progress = (float) ($research['plan']['progress'] ?? 0);
$latestClearance = $clearance['clearances'][0] ?? null;
$latestPayment = $clearance['payments'][0] ?? null;
$firstName = explode(' ', trim((string) $user['name']))[0] ?? '';

portal_header('Dashboard', 'index', $user);
?>
<p class="text-muted mb-4">Welcome back, <?= e($firstName) ?>. Here is where your research stands.</p>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <section class="portal-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h2 class="h6 text-uppercase text-muted mb-0">Research status</h2>
                <?= portal_status_badge($research['status'] ?? 'Not Started') ?>
            </div>
            <p class="fs-5 fw-semibold mb-1"><?= e($research['title'] ?? 'No title submitted yet') ?></p>
            <p class="text-muted small mb-3">
                <?php if (!empty($research['group'])): ?>
                    Group <?= e($research['group']['group_number']) ?> · Adviser: <?= e($research['group']['adviser'] ?: 'For assignment') ?>
                <?php else: ?>
                    Submit your research title to start. <a href="open.php?to=proposal">Open title submission</a>
                <?php endif; ?>
            </p>
            <div class="d-flex justify-content-between small mb-1"><span>Overall progress</span><span><?= e((string) round($progress)) ?>%</span></div>
            <div class="progress" role="progressbar" aria-valuenow="<?= e((string) $progress) ?>" aria-valuemin="0" aria-valuemax="100" style="height:10px">
                <div class="progress-bar" style="width: <?= e((string) max(0, min(100, $progress))) ?>%"></div>
            </div>
            <?php if (!empty($research['plan']['current_stage'])): ?>
                <p class="small text-muted mt-2 mb-0">Current stage: <?= e($research['plan']['current_stage']) ?></p>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Next defense</h2>
            <?php if ($upcoming): ?>
                <p class="fs-5 fw-semibold mb-1"><?= e($upcoming['type']) ?></p>
                <p class="mb-1"><i class="fa-regular fa-calendar me-2"></i><?= e(portal_date($upcoming['starts_at'], 'D, M j, Y g:i A')) ?></p>
                <p class="mb-2"><i class="fa-solid fa-location-dot me-2"></i><?= e($upcoming['venue'] ?: 'Venue to be announced') ?></p>
                <a href="defenses.php" class="small">See panel and details</a>
            <?php else: ?>
                <?= portal_empty('No upcoming defense yet.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Clearance and payment</h2>
            <div class="d-flex justify-content-between mb-2"><span>Research clearance</span><?= portal_status_badge($latestClearance['status'] ?? 'Not started') ?></div>
            <div class="d-flex justify-content-between mb-3"><span>College payment</span><?= portal_status_badge($latestPayment['status'] ?? 'Not uploaded') ?></div>
            <a href="clearance.php" class="small">View details</a>
        </section>
    </div>
    <div class="col-md-6">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Latest updates</h2>
            <?php if ($notifications): ?>
                <ul class="list-unstyled mb-2 portal-feed">
                    <?php foreach ($notifications as $n): ?>
                        <li><strong><?= e($n['title']) ?></strong><br><span class="small text-muted"><?= e($n['body']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <a href="notifications.php" class="small">All notifications</a>
            <?php else: ?>
                <?= portal_empty('No updates yet. CRAD updates appear here as they happen.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="portal-card mb-3">
    <h2 class="h6 text-uppercase text-muted">Quick actions</h2>
    <div class="portal-actions">
        <?php foreach (portal_sms2_actions() as $key => $action): ?>
            <a class="portal-action" href="open.php?to=<?= e($key) ?>"><i class="fa-solid <?= e($action['icon']) ?>"></i><span><?= e($action['label']) ?></span></a>
        <?php endforeach; ?>
    </div>
    <p class="small text-muted mt-2 mb-0">These open in SMS 2 with your same sign-in.</p>
</section>

<section class="portal-card">
    <div class="d-flex justify-content-between"><h2 class="h6 text-uppercase text-muted">Announcements</h2><a href="announcements.php" class="small">See all</a></div>
    <?php if ($announcements): ?>
        <?php foreach ($announcements as $a): ?>
            <article class="border-top pt-2 mt-2">
                <div class="fw-semibold"><?= e($a['title']) ?></div>
                <div class="small text-muted"><?= e($a['category']) ?> · <?= e($a['posted_at']) ?></div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <?= portal_empty('No announcements right now.') ?>
    <?php endif; ?>
</section>
<?php portal_footer();
