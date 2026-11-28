<?php
/**
 * One CRAD step inside the portal, in the same look as CRAD.
 * Every student step (group, title, chapters, progress, panel, manuscript,
 * payment, clearance) is done here through the SMS 2 API; CRAD reviews it in
 * SMS 2. The form for each step is src/modules/crad/steps/<key>.php. An action that still has
 * an SMS 2 'path' (src/modules/crad/module.php) shows its status and opens SMS 2 instead.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/core/bootstrap.php';
require_once __DIR__ . '/../src/core/layout.php';
$user = portal_require_login();

$actions = portal_sms2_actions();
$do = (string) ($_GET['do'] ?? '');
if (!isset($actions[$do])) {
    portal_redirect(portal_url('crad/index.php'));
}
$action = $actions[$do];
$self = 'crad/step.php?do=' . rawurlencode($do);

/* ---------------------------------------------------------------- submit */
// Each CRAD step has one SMS 2 API endpoint: GET for its state, POST for the student's action.
$endpoints = [
    'group' => 'me/research/group', 'proposal' => 'me/research/title', 'chapters' => 'me/research/chapters',
    'progress' => 'me/research/progress', 'panel' => 'me/research/panel', 'manuscript' => 'me/research/manuscript',
    'payment' => 'me/research/payment', 'clearance' => 'me/research/clearance-form',
];
$images = ['png', 'jpg', 'jpeg'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($endpoints[$do])) {
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        portal_flash('That file is too big to upload. Please choose a smaller file.', 'danger');
        portal_redirect(portal_url($self));
    }
    if (!portal_csrf_ok()) {
        portal_flash('Your form expired. Please try again.', 'danger');
        portal_redirect(portal_url($self));
    }
    $in = static fn(string $k, int $max = 2000): string => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $token = static function (): string {
        $t = (string) ($_POST['submission_token'] ?? '');
        return preg_match('/^[a-f0-9]{64}$/', $t) ? $t : bin2hex(random_bytes(32));
    };
    $form = [];
    $file = null;
    switch ($do) {
        case 'chapters':
        case 'manuscript':
            $field = $do === 'chapters' ? 'document' : 'manuscript';
            $file = [$field, portal_upload_from_request($field, $do === 'chapters' ? 10 : 20)];
            $form = ['notes' => $in('notes')];
            if ($do === 'chapters') {
                $form['chapter_number'] = (string) (int) ($_POST['chapter_number'] ?? 0);
                $form['submission_token'] = $token();
            }
            break;
        case 'group':
            $names = (array) ($_POST['member_name'] ?? []);
            $ids = (array) ($_POST['member_student_id'] ?? []);
            $sections = (array) ($_POST['member_section'] ?? []);
            $form['members'] = [];
            foreach (array_keys($names) as $i) {
                if (trim((string) ($names[$i] ?? '')) === '' && trim((string) ($ids[$i] ?? '')) === '') {
                    continue;
                }
                $form['members'][] = ['full_name' => mb_substr(trim((string) $names[$i]), 0, 160), 'student_id' => mb_substr(trim((string) ($ids[$i] ?? '')), 0, 40), 'section' => mb_substr(trim((string) ($sections[$i] ?? '')), 0, 60)];
            }
            $form['incomplete_reason'] = $in('incomplete_reason', 1000);
            $form['action'] = ($_POST['action'] ?? '') === 'draft' ? 'draft' : 'submit';
            break;
        case 'proposal':
            foreach (['research_title' => 500, 'department' => 120, 'discipline' => 200, 'research_agenda' => 255, 'research_agenda_others' => 255, 'primary_sdg' => 120, 'justification' => 5000] as $k => $max) {
                $form[$k] = $in($k, $max);
            }
            break;
        case 'progress':
            foreach (['milestone_id' => 12, 'new_progress' => 6, 'milestone_status' => 40, 'update_title' => 300, 'accomplishments' => 4000, 'problems_blockers' => 4000, 'next_planned_activity' => 4000] as $k => $max) {
                $form[$k] = $in($k, $max);
            }
            $form['submission_token'] = $token();
            $doc = portal_optional_upload('document', 10, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
            $file = $doc === null ? null : ['document', $doc];
            break;
        case 'panel':
            $form['action'] = in_array($_POST['action'] ?? '', ['save_details', 'upload_document', 'submit'], true) ? (string) $_POST['action'] : '';
            if ($form['action'] === 'save_details') {
                $form += ['research_title' => $in('research_title', 255), 'adviser_name' => $in('adviser_name', 160), 'members_text' => $in('members_text', 2000)];
            } elseif ($form['action'] === 'upload_document') {
                $form['doc_type'] = $in('doc_type', 40);
                $file = ['document', portal_upload_from_request('document', 20, ['pdf', 'png', 'jpg', 'jpeg'])];
            }
            break;
        case 'payment':
            $form = ['stage' => $in('stage', 20), 'or_number' => $in('or_number', 60)];
            $file = ['payment_file', portal_upload_from_request('payment_file', 10, $images)];
            break;
        case 'clearance':
            $form = ['id' => (string) (int) ($_POST['id'] ?? 0)];
            $file = ['clearance_file', portal_upload_from_request('clearance_file', 10, $images)];
            break;
    }
    if ($file !== null) {
        if (is_string($file[1])) {
            portal_flash($file[1], 'danger');
            portal_redirect(portal_url($self));
        }
        $form[$file[0]] = $file[1];
    }
    $result = portal_api($endpoints[$do], 'POST', $form);
    if (is_array($result) && !empty($result['ok'])) {
        if ($do === 'chapters' || $do === 'manuscript') {
            $what = $do === 'chapters' ? 'Chapter ' . $form['chapter_number'] : 'Final manuscript';
            portal_flash($what . ' version ' . (int) $result['version'] . ' was submitted. You will get a notification once it is reviewed.', 'success');
        } else {
            portal_flash((string) ($result['message'] ?? 'Saved.'), 'success');
        }
    } else {
        portal_flash($GLOBALS['portal_api_last_error'] ?? 'That could not be sent right now. Please try again.', 'danger');
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
$data = isset($endpoints[$do]) && empty($action['path']) ? portal_api($endpoints[$do]) : null;
$chapters = $manuscript = $data;
$state = (string) ($step['state'] ?? 'current');
$tone = ['done' => 'green', 'attention' => 'rose', 'locked' => 'slate', 'waiting' => 'blue', 'current' => 'blue'][$state] ?? 'blue';
$inPortal = empty($action['path']);

portal_header($action['label'], $self, $user);
?>
<p class="text-muted mb-4">
    <?php if ($step): ?>Step <?= e((string) $step['no']) ?> of the CRAD process<?= !empty($step['summary']) ? ' · ' . e($step['summary']) : '' ?><?php else: ?>CRAD research step<?php endif; ?>
</p>
<div class="row g-3">
    <div class="col-lg-7">
        <?php if ($inPortal && is_file(__DIR__ . '/../src/modules/crad/steps/' . $do . '.php')): ?>
            <?php require __DIR__ . '/../src/modules/crad/steps/' . $do . '.php'; ?>
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
                        <a href="crad/index.php" class="btn btn-soft">Back to Research Workspace</a>
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
    if (!input) { return; }
    input.addEventListener('change', function () {
        var f = input.files[0];
        if (name) { name.textContent = f ? f.name : 'No file selected'; }
        input.setCustomValidity(f && f.size > max ? 'This file is too big.' : '');
    });
    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending…';
    });
});
document.querySelectorAll('[data-sp-other]').forEach(function (sel) {
    var other = document.querySelector(sel.getAttribute('data-sp-other'));
    var sync = function () { var on = sel.value.indexOf('Others') === 0; other.hidden = !on; other.required = on; };
    sel.addEventListener('change', sync); sync();
});
document.querySelectorAll('[data-sp-print]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var frame = document.querySelector('[data-sp-print-frame="' + btn.getAttribute('data-sp-print') + '"]');
        if (frame && frame.contentWindow) { frame.contentWindow.focus(); frame.contentWindow.print(); }
    });
});
</script>
<?php portal_footer();
