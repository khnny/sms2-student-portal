<?php
/**
 * One CRAD step inside the portal, in the same look as CRAD.
 * Chapters 1-3 and the final manuscript are submitted here through the SMS 2 API.
 * Steps whose forms are still in SMS 2 show their status here and open the
 * SMS 2 form in the same tab, signed in as the same student.
 */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$user = portal_require_login();

$actions = portal_sms2_actions();
$do = (string) ($_GET['do'] ?? '');
if (!isset($actions[$do])) {
    portal_redirect(portal_url('research.php'));
}
$action = $actions[$do];
$self = 'step.php?do=' . rawurlencode($do);

/* ---------------------------------------------------------------- submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($do, ['chapters', 'manuscript'], true)) {
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        portal_flash('That file is too big to upload. Please choose a smaller file.', 'danger');
        portal_redirect(portal_url($self));
    }
    if (!portal_csrf_ok()) {
        portal_flash('Your form expired. Please try again.', 'danger');
        portal_redirect(portal_url($self));
    }
    $field = $do === 'chapters' ? 'document' : 'manuscript';
    $maxMb = $do === 'chapters' ? 10 : 20;
    $file = portal_upload_from_request($field, $maxMb);
    if (is_string($file)) {
        portal_flash($file, 'danger');
        portal_redirect(portal_url($self));
    }
    $form = [$field => $file, 'notes' => mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 2000)];
    if ($do === 'chapters') {
        $form['chapter_number'] = (string) (int) ($_POST['chapter_number'] ?? 0);
        $token = (string) ($_POST['submission_token'] ?? '');
        $form['submission_token'] = preg_match('/^[a-f0-9]{64}$/', $token) ? $token : bin2hex(random_bytes(32));
    }
    $result = portal_api('me/research/' . $do, 'POST', $form);
    if (is_array($result) && !empty($result['ok'])) {
        $what = $do === 'chapters' ? 'Chapter ' . $form['chapter_number'] : 'Final manuscript';
        portal_flash($what . ' version ' . (int) $result['version'] . ' was submitted. You will get a notification once it is reviewed.', 'success');
    } else {
        portal_flash($GLOBALS['portal_api_last_error'] ?? 'Your file could not be submitted right now. Please try again.', 'danger');
    }
    portal_redirect(portal_url($self));
}

/* ------------------------------------------------------------------ data */
$statement = portal_api('me/research/statement');
$step = null;
foreach (($statement['steps'] ?? []) as $s) {
    if (portal_step_for_key((string) $s['key']) === $do) {
        $step = $s;
        break;
    }
}
$chapters = $do === 'chapters' ? portal_api('me/research/chapters') : null;
$manuscript = $do === 'manuscript' ? portal_api('me/research/manuscript') : null;
$state = (string) ($step['state'] ?? 'current');
$tone = ['done' => 'green', 'attention' => 'rose', 'locked' => 'slate', 'waiting' => 'blue', 'current' => 'blue'][$state] ?? 'blue';
$inPortal = empty($action['path']);

portal_header($action['label'], $do, $user);
?>
<p class="text-muted mb-4">
    <?php if ($step): ?>Step <?= e((string) $step['no']) ?> of the CRAD process<?= !empty($step['summary']) ? ' · ' . e($step['summary']) : '' ?><?php else: ?>CRAD research step<?php endif; ?>
</p>
<div class="row g-3">
    <div class="col-lg-7">
        <?php if ($do === 'chapters'): ?>
            <?php require __DIR__ . '/src/step-chapters.php'; ?>
        <?php elseif ($do === 'manuscript'): ?>
            <?php require __DIR__ . '/src/step-manuscript.php'; ?>
        <?php else: ?>
            <section class="portal-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="sp-ic <?= e($tone) ?>"><i class="fa-solid <?= e($action['icon']) ?>"></i></span>
                    <div class="flex-grow-1"><div class="small text-muted fw-semibold text-uppercase"><?= $step ? 'Step ' . e((string) $step['no']) : 'CRAD step' ?></div><div class="fw-bold fs-5" style="color:var(--ink)"><?= e($step['title'] ?? $action['label']) ?></div></div>
                    <?= portal_status_badge($step['status'] ?? null) ?>
                </div>
                <?php if ($step && !empty($step['rows'])): ?>
                    <dl class="sp-facts">
                        <?php foreach ($step['rows'] as $label => $value): if (trim((string) $value) === '') { continue; } ?>
                            <div><dt><?= e((string) $label) ?></dt><dd><?= e((string) $value) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <?php if ($state === 'locked'): ?>
                    <div class="sp-note"><i class="fa-solid fa-lock mt-1"></i><span>This step opens later. <?= e($step['summary'] ?? '') ?></span></div>
                <?php else: ?>
                    <div class="sp-note mb-3"><i class="fa-solid fa-circle-info mt-1"></i><span>This form is still in SMS 2. It opens in this same tab with your same sign-in. Use the back button to come back here.</span></div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="research.php" class="btn btn-soft">Back to Research Workspace</a>
                        <a href="open.php?to=<?= e($do) ?>" class="btn btn-primary"><?= e($step['action']['label'] ?? 'Open ' . strtolower($action['label'])) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <?php if ($step && !empty($step['list']['rows'])): ?>
            <section class="portal-card mb-3">
                <div class="sp-section-h"><h2>Records</h2></div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><?php foreach ($step['list']['columns'] as $c): ?><th><?= e((string) $c) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                        <?php foreach ($step['list']['rows'] as $r): ?>
                            <tr><?php foreach ($r as $i => $cell): ?><td><?= stripos((string) ($step['list']['columns'][$i] ?? ''), 'status') !== false ? portal_status_badge((string) $cell) : e((string) $cell) ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
            </section>
        <?php endif; ?>
        <?php foreach (array_slice($step['remarks'] ?? [], 0, 3) as $r): ?>
            <div class="sp-note mb-2"><i class="fa-regular fa-comment mt-1"></i><span><b><?= e((string) $r['from']) ?>:</b> “<?= e((string) $r['text']) ?>”<?php if (!empty($r['at'])): ?> <span class="text-muted small">· <?= e((string) $r['at']) ?></span><?php endif; ?></span></div>
        <?php endforeach; ?>
        <?php if (!$step && !$inPortal): ?>
            <section class="portal-card"><?= portal_empty('Your research steps could not be loaded right now.') ?></section>
        <?php endif; ?>
    </div>
</div>
<script>
document.querySelectorAll('[data-sp-upload]').forEach(function (form) {
    var input = form.querySelector('input[type=file]');
    var name = form.querySelector('[data-file-name]');
    var max = parseInt(form.getAttribute('data-max-mb'), 10) * 1024 * 1024;
    input.addEventListener('change', function () {
        var f = input.files[0];
        name.textContent = f ? f.name : 'No file selected';
        input.setCustomValidity(f && f.size > max ? 'This file is too big.' : '');
    });
    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending…';
    });
});
</script>
<?php portal_footer();
