<?php
/** Dashboard: research banner, next step, clearance, quick actions, updates and announcements. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';
require_once __DIR__ . '/src/core/layout.php';
$user = portal_require_login();

$research = portal_api('me/research') ?? [];
$statement = portal_api('me/research/statement');
$defenses = portal_api('me/defenses') ?? [];
$clearance = portal_api('me/clearance') ?? ['clearances' => [], 'payments' => []];
$announcements = array_slice(portal_api('announcements') ?? [], 0, 3);
$notifications = array_slice(portal_local_notifications($user, 5), 0, 4);

$upcoming = null;
foreach (array_reverse($defenses) as $d) {
    if (!empty($d['starts_at']) && strtotime($d['starts_at']) >= strtotime('today')) {
        $upcoming = $d;
        break;
    }
}
$summary = is_array($statement) ? ($statement['summary'] ?? []) : [];
$student = is_array($statement) ? ($statement['student'] ?? []) : [];
$progress = (int) round((float) ($summary['progress'] ?? $research['plan']['progress'] ?? 0));
$nextStep = null;
foreach (($statement['steps'] ?? []) as $step) {
    if ($step['key'] === ($summary['next_step'] ?? null)) {
        $nextStep = $step;
    }
}
$latestClearance = $clearance['clearances'][0] ?? null;
$latestPayment = $clearance['payments'][0] ?? null;
$firstName = explode(' ', trim((string) $user['name']))[0] ?? '';
$tags = array_filter([
    $summary['group_number'] ?? ($research['group']['group_number'] ?? ''),
    trim(($student['program'] ?? '') . ' ' . ($student['section'] ?? '')),
    !empty($student['school_year']) ? 'AY ' . $student['school_year'] : '',
]);
$adviser = $summary['adviser'] ?? ($research['group']['adviser'] ?? '');
$colors = ['proposal' => 'green', 'group' => 'teal', 'chapters' => 'blue', 'progress' => 'violet', 'panel' => 'amber', 'manuscript' => 'slate', 'payment' => 'rose', 'clearance' => 'green'];

portal_header('Dashboard', 'index', $user);
?>
<section class="sp-hero">
    <img class="sp-wm" src="assets/img/bcp-crest.png" alt="">
    <div class="d-flex gap-4 align-items-start">
        <div class="flex-grow-1 min-w-0">
            <div class="sp-hero-eyebrow"><?= e(strtoupper(date('l, M j, Y'))) ?> · Student Portal</div>
            <h2>Hi, <?= e($firstName ?: 'Student') ?>! 👋</h2>
            <div class="sp-hero-sub"><?= e($summary['research_title'] ?? $research['title'] ?? '') ?: 'No research title yet' ?></div>
            <div class="sp-tags"><?php foreach ($tags as $t): ?><span class="sp-tag"><?= e($t) ?></span><?php endforeach; ?></div>
        </div>
        <div class="sp-ring" style="--p: <?= e((string) max(0, min(100, $progress))) ?>"><div><b><?= e((string) $progress) ?>%</b><small>overall</small></div></div>
    </div>
    <div class="row g-3 align-items-stretch">
        <div class="col-lg-7">
            <div class="sp-kpis">
                <a class="sp-kpi" href="crad/index.php"><span class="sp-ic green"><i class="fa-solid fa-check-double"></i></span><span><small>Steps done</small><b><?= e((string) ($summary['steps_done'] ?? 0)) ?> of <?= e((string) ($summary['steps_total'] ?? 9)) ?></b></span></a>
                <a class="sp-kpi" href="crad/defenses.php"><span class="sp-ic violet"><i class="fa-solid fa-person-chalkboard"></i></span><span><small>Next defense</small><b><?= e($upcoming ? portal_date($upcoming['starts_at'], 'M j, g:i A') : 'Not scheduled') ?></b></span></a>
                <a class="sp-kpi" href="crad/clearance.php"><span class="sp-ic rose"><i class="fa-solid fa-receipt"></i></span><span><small>College payment</small><b><?= e($latestPayment['status'] ?? 'Not uploaded') ?></b></span></a>
                <a class="sp-kpi" href="crad/index.php"><span class="sp-ic amber"><i class="fa-solid fa-user-tie"></i></span><span><small>Adviser</small><b><?= e($adviser ?: 'For assignment') ?></b></span></a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sp-next h-100">
                <div class="sp-next-k">Your next step</div>
                <h3><?= e($nextStep['title'] ?? 'All steps are complete') ?></h3>
                <p><?= e($nextStep['status'] ?? 'Great work. Nothing to do right now.') ?></p>
                <?php if ($nextStep && !empty($nextStep['action'])): ?>
                    <a class="btn btn-primary" href="crad/step.php?do=<?= e(portal_step_for_key($nextStep['key'])) ?>"><?= e($nextStep['action']['label']) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="portal-card h-100">
            <div class="sp-section-h"><h2>Quick actions</h2><span class="small text-muted">Done here in the portal</span></div>
            <div class="portal-actions">
                <?php foreach (portal_sms2_actions() as $key => $action): ?>
                    <a class="portal-action" href="crad/step.php?do=<?= e($key) ?>"><span class="sp-ic <?= e($colors[$key] ?? 'blue') ?>"><i class="fa-solid <?= e($action['icon']) ?>"></i></span><span><?= e($action['label']) ?></span></a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="portal-card mb-3">
            <div class="sp-section-h"><h2>Clearance and payment</h2><a href="crad/clearance.php" class="small">View details</a></div>
            <div class="sp-status"><span class="sp-ic green"><i class="fa-solid fa-clipboard-check"></i></span><span>Research clearance</span><?= portal_status_badge($latestClearance['status'] ?? 'Not started') ?></div>
            <div class="sp-status"><span class="sp-ic rose"><i class="fa-solid fa-receipt"></i></span><span>College payment</span><?= portal_status_badge($latestPayment['status'] ?? 'Not uploaded') ?></div>
        </section>
        <section class="portal-card">
            <div class="sp-section-h"><h2>Latest updates</h2><a href="notifications.php" class="small">All</a></div>
            <?php if ($notifications): ?>
                <ul class="list-unstyled mb-0 portal-feed">
                    <?php foreach ($notifications as $n): ?>
                        <li class="<?= $n['read_at'] ? '' : 'unread' ?>"><strong><?= e($n['title']) ?></strong><br><span class="small text-muted"><?= e($n['body']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= portal_empty('No updates yet. CRAD updates appear here as they happen.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="portal-card">
    <div class="sp-section-h"><h2>Announcements</h2><span><span class="sp-live me-2">Live</span><a href="announcements.php" class="small">See all</a></span></div>
    <?php if ($announcements): ?>
        <?php foreach ($announcements as $a): ?>
            <article class="border-top pt-2 mt-2">
                <div class="fw-semibold" style="color:var(--ink)"><?= e($a['title']) ?></div>
                <div class="small text-muted"><?= e($a['category']) ?> · <?= e($a['posted_at']) ?></div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <?= portal_empty('No announcements right now.') ?>
    <?php endif; ?>
</section>
<?php portal_footer();
