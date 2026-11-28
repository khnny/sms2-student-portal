<?php
/** Chapter 1-3 upload form (included by crad/step.php). Uses $chapters from GET me/research/chapters. */
$canAny = false;
foreach (($chapters['chapters'] ?? []) as $c) {
    $canAny = $canAny || !empty($c['can_submit']);
}
?>
<section class="portal-card">
    <div class="d-flex align-items-center gap-3 mb-3">
        <span class="sp-ic <?= e($tone) ?>"><i class="fa-solid fa-file-arrow-up"></i></span>
        <div class="flex-grow-1"><div class="small text-muted fw-semibold text-uppercase"><?= $step ? 'Step ' . e((string) $step['no']) : 'Chapters 1 to 3' ?></div><div class="fw-bold fs-5" style="color:var(--ink)">Upload a chapter</div></div>
        <?= portal_status_badge($step['status'] ?? null) ?>
    </div>
    <?php if (!is_array($chapters)): ?>
        <?= portal_empty('Your chapters could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($chapters['available'])): ?>
        <div class="sp-note"><i class="fa-solid fa-lock mt-1"></i><span><?= e($chapters['message'] ?: 'Chapter submission opens once your research group is officially registered.') ?></span></div>
    <?php elseif (!$canAny): ?>
        <div class="sp-note"><i class="fa-solid fa-circle-info mt-1"></i><span>No chapter can be sent right now. A chapter opens after your adviser approves its progress update, or when the latest version is returned for revision.</span></div>
    <?php else: ?>
        <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-sp-upload data-max-mb="<?= e((string) (int) ($chapters['max_mb'] ?? 10)) ?>">
            <?= portal_csrf_field() ?>
            <input type="hidden" name="submission_token" value="<?= e(bin2hex(random_bytes(32))) ?>">
            <div class="mb-3">
                <label class="form-label" for="chapter_number">Chapter</label>
                <select class="form-select" id="chapter_number" name="chapter_number" required>
                    <?php $picked = false; foreach ($chapters['chapters'] as $c):
                        $sel = !$picked && !empty($c['can_submit']); $picked = $picked || $sel; ?>
                        <option value="<?= e((string) $c['number']) ?>" <?= empty($c['can_submit']) ? 'disabled' : '' ?> <?= $sel ? 'selected' : '' ?>>
                            <?= e($c['label']) ?> · <?= !empty($c['can_submit']) ? 'Version ' . e((string) $c['next_version']) . ($c['latest'] ? ' (revision)' : '') : e($c['reason']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="document">File</label>
                <label class="sp-drop d-block" for="document">
                    <i class="fa-solid fa-cloud-arrow-up d-block"></i>
                    <b style="color:var(--ink)">Choose your PDF or Word file</b><br>
                    <span class="small">.pdf, .doc, .docx · up to <?= e((string) (int) ($chapters['max_mb'] ?? 10)) ?> MB</span><br>
                    <span class="small fw-semibold" data-file-name>No file selected</span>
                </label>
                <input class="visually-hidden" type="file" id="document" name="document" accept=".pdf,.doc,.docx" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="notes">Note to the evaluator (optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000" placeholder="What changed in this version?"></textarea>
            </div>
            <div class="sp-note"><i class="fa-solid fa-circle-info mt-1"></i><span>Your file goes straight to SMS 2 for Grammarian evaluation. You get a notification when it is reviewed.</span></div>
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="crad/index.php" class="btn btn-soft">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Submit chapter</button>
            </div>
        </form>
    <?php endif; ?>
</section>
