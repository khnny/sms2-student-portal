<?php
/** Title approval form, CRAD Form S2 (included by step.php). Uses $data from GET me/research/title. */
$sub = $data['submission'] ?? null;
$opt = $data['options'] ?? [];
$val = static fn(string $k, string $default = ''): string => (string) ($sub[$k] ?? '') !== '' ? (string) $sub[$k] : $default;
$agenda = $val('research_agenda', (string) ($opt['default_agenda'] ?? ''));
$agendaIsOther = $agenda !== '' && !in_array($agenda, $opt['research_agendas'] ?? [], true);
$sdgValue = static fn(array $s): string => 'SDG ' . $s['num'] . ' — ' . $s['title'];
$pickedSdg = $val('primary_sdg');
?>
<section class="portal-card">
    <?= portal_step_head('fa-file-signature', 'Title approval form', $tone, $step, 'Title approval') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your title approval form could not be loaded right now. Please refresh the page.') ?>
    <?php else: ?>
        <dl class="sp-facts">
            <div><dt>Adviser</dt><dd><?= e($data['adviser'] ?: 'Not assigned yet') ?></dd></div>
            <div><dt>Coordinator</dt><dd><?= e($data['coordinator'] ?: 'Not assigned yet') ?></dd></div>
            <?php if ($sub): ?>
                <div><dt>Adviser review</dt><dd><?= portal_status_badge($sub['adviser_status']) ?></dd></div>
                <div><dt>Coordinator review</dt><dd><?= portal_status_badge($sub['coordinator_status']) ?></dd></div>
                <div><dt>CRAD review</dt><dd><?= portal_status_badge($sub['crad_status']) ?></dd></div>
                <div><dt>Sent</dt><dd><?= e(portal_date($sub['sent_at'], 'M j, Y g:i A')) ?></dd></div>
            <?php endif; ?>
        </dl>
        <?php foreach (['adviser_remarks' => 'Adviser', 'coordinator_remarks' => 'Coordinator'] as $k => $who): if (!empty($sub[$k])): ?>
            <?= portal_note($who . ': ' . $sub[$k], 'fa-comment', 'mb-2') ?>
        <?php endif; endforeach; ?>
        <?php if (empty($data['can_submit'])): ?>
            <?php if ($sub): ?><p class="fw-semibold mt-3 mb-2" style="color:var(--ink)"><?= e($sub['title']) ?></p><?php endif; ?>
            <?= portal_note($data['message'] ?: 'The title approval form is not open yet.', $sub ? 'fa-hourglass-half' : 'fa-lock') ?>
        <?php else: ?>
            <?php if (!empty($data['is_resubmit'])): ?><?= portal_note('Your form was returned. Fix it below and send it again.', 'fa-rotate-left', 'mb-3') ?><?php endif; ?>
            <form method="post" action="<?= e($self) ?>" class="mt-2">
                <?= portal_csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="research_title">Proposed research title</label>
                    <textarea class="form-control text-uppercase" id="research_title" name="research_title" rows="2" maxlength="500" required><?= e($val('title')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="department">Department / College</label>
                    <select class="form-select" id="department" name="department" required>
                        <?php foreach ($opt['departments'] ?? [] as $d): ?><option <?= $d === $val('department', (string) ($opt['default_department'] ?? '')) ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-label">Members (from your research group)</div>
                    <ul class="small mb-0"><?php foreach ($data['members'] ?? [] as $m): ?><li><?= e($m['name']) ?> · <?= e($m['section']) ?></li><?php endforeach; ?></ul>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="discipline">Discipline cluster</label>
                    <select class="form-select" id="discipline" name="discipline" required>
                        <?php foreach ($opt['discipline_clusters'] ?? [] as $d): ?><option <?= $d === $val('discipline', (string) ($opt['default_discipline'] ?? '')) ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="research_agenda">Institutional research agenda</label>
                    <select class="form-select" id="research_agenda" name="research_agenda" required data-sp-other="#agendaOther">
                        <?php foreach ($opt['research_agendas'] ?? [] as $d): $isOther = str_starts_with($d, 'Others'); ?>
                            <option value="<?= e($d) ?>" <?= ($isOther ? $agendaIsOther : $d === $agenda) ? 'selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control mt-2" id="agendaOther" name="research_agenda_others" placeholder="Specify the other research agenda" maxlength="255" value="<?= e($agendaIsOther ? $agenda : '') ?>" <?= $agendaIsOther ? '' : 'hidden' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="primary_sdg">Primary Sustainable Development Goal</label>
                    <select class="form-select" id="primary_sdg" name="primary_sdg" required>
                        <?php foreach ($opt['sdgs'] ?? [] as $s2): $v = $sdgValue($s2); ?>
                            <option value="<?= e($v) ?>" <?= ($pickedSdg !== '' ? $v === $pickedSdg : (int) $s2['num'] === (int) ($opt['default_sdg'] ?? 0)) ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="justification">How your study supports this SDG</label>
                    <textarea class="form-control" id="justification" name="justification" rows="4" maxlength="5000" required><?= e($val('justification')) ?></textarea>
                </div>
                <?= portal_note('The form goes to your adviser, then the coordinator and CRAD. You get a notification at each step.') ?>
                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="research.php" class="btn btn-soft">Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i><?= !empty($data['is_resubmit']) ? 'Send again to adviser' : 'Send to adviser' ?></button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>
