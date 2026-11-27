<?php
/** My research: title approval, group, members, milestones, submissions and feedback. */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$user = portal_require_login();

$research = portal_api('me/research') ?? [];
$members = portal_api('me/research/members') ?? [];
$timeline = portal_api('me/research/timeline') ?? [];
$submissions = portal_api('me/research/submissions') ?? ['chapters' => [], 'progress_updates' => []];
$feedback = portal_api('me/research/feedback') ?? [];
$t = $research['title_approval'] ?? null;
$g = $research['group'] ?? null;

portal_header('My research', 'research', $user);
?>
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Title approval</h2>
            <?php if ($t): ?>
                <p class="fw-semibold mb-2"><?= e($t['proposed_title']) ?></p>
                <table class="table table-sm mb-2">
                    <tr><th class="fw-normal text-muted">Adviser</th><td><?= portal_status_badge($t['adviser_status']) ?></td></tr>
                    <tr><th class="fw-normal text-muted">Research coordinator</th><td><?= portal_status_badge($t['coordinator_status']) ?></td></tr>
                    <tr><th class="fw-normal text-muted">CRAD</th><td><?= portal_status_badge($t['crad_status']) ?></td></tr>
                </table>
                <?php foreach (['adviser_remarks' => 'Adviser remarks', 'coordinator_remarks' => 'Coordinator remarks'] as $k => $label): ?>
                    <?php if (!empty($t[$k])): ?><p class="small mb-1"><strong><?= e($label) ?>:</strong> <?= e($t[$k]) ?></p><?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?= portal_empty('No title submitted yet.') ?>
                <a class="btn btn-sm btn-primary mt-2" href="open.php?to=proposal">Submit your research title</a>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Research group</h2>
            <?php if ($g): ?>
                <dl class="row small mb-2">
                    <dt class="col-5 fw-normal text-muted">Group number</dt><dd class="col-7"><?= e($g['group_number']) ?></dd>
                    <dt class="col-5 fw-normal text-muted">Adviser</dt><dd class="col-7"><?= e($g['adviser'] ?: 'For assignment') ?></dd>
                    <dt class="col-5 fw-normal text-muted">Coordinator</dt><dd class="col-7"><?= e($g['coordinator'] ?: '—') ?></dd>
                    <dt class="col-5 fw-normal text-muted">Department</dt><dd class="col-7"><?= e($g['college_dept']) ?></dd>
                    <dt class="col-5 fw-normal text-muted">School year</dt><dd class="col-7"><?= e($g['academic_year']) ?></dd>
                </dl>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($members as $m): ?>
                        <li class="small"><i class="fa-regular fa-user me-2"></i><?= e($m['name']) ?> <span class="text-muted">(<?= e($m['student_id']) ?>)</span><?= $m['is_leader'] ? ' <span class="badge text-bg-light">Leader</span>' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= portal_empty('No research group yet.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="portal-card mb-3">
    <h2 class="h6 text-uppercase text-muted">Milestones</h2>
    <?php if ($timeline): ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>Milestone</th><th>Target</th><th style="width:160px">Progress</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($timeline as $m): ?>
                <tr>
                    <td><?= e((string) $m['order']) ?></td>
                    <td><?= e($m['name']) ?><?php if (!empty($m['adviser_remarks'])): ?><div class="small text-muted"><?= e($m['adviser_remarks']) ?></div><?php endif; ?></td>
                    <td><?= e(portal_date($m['target_date'])) ?></td>
                    <td><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= e((string) max(0, min(100, (float) $m['progress']))) ?>%"></div></div></td>
                    <td><?= portal_status_badge($m['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php else: ?>
        <?= portal_empty('Milestones appear once your research plan is set up.') ?>
    <?php endif; ?>
</section>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="portal-card h-100">
            <div class="d-flex justify-content-between"><h2 class="h6 text-uppercase text-muted">Chapter submissions</h2><a class="small" href="open.php?to=chapters">Submit a chapter</a></div>
            <?php if ($submissions['chapters']): ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Chapter</th><th>Version</th><th>Submitted</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($submissions['chapters'] as $c): ?>
                        <tr><td><?= e((string) $c['chapter']) ?></td><td>v<?= e((string) $c['version']) ?></td><td><?= e(portal_date($c['submitted_at'])) ?></td><td><?= portal_status_badge($c['status']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <?= portal_empty('No chapters submitted yet.') ?>
            <?php endif; ?>
            <?php if ($submissions['progress_updates']): ?>
                <h3 class="h6 mt-3">Progress updates</h3>
                <ul class="list-unstyled small mb-0">
                    <?php foreach (array_slice($submissions['progress_updates'], 0, 8) as $u): ?>
                        <li class="mb-1"><?= e($u['title']) ?> · <?= e(portal_date($u['submitted_at'])) ?> <?= portal_status_badge($u['milestone_status']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="portal-card h-100">
            <h2 class="h6 text-uppercase text-muted">Feedback</h2>
            <?php if ($feedback): ?>
                <?php foreach (array_slice($feedback, 0, 10) as $f): ?>
                    <article class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between small"><strong><?= e($f['from'] ?: 'Reviewer') ?> · <?= e($f['about']) ?></strong><span class="text-muted"><?= e(portal_date($f['created_at'])) ?></span></div>
                        <div class="small"><?= nl2br(e($f['text'] ?? '')) ?></div>
                        <?php if (!empty($f['result'])): ?><div class="mt-1"><?= portal_status_badge($f['result']) ?></div><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <?= portal_empty('No feedback yet.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php portal_footer();
