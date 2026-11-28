<?php
/** Final manuscript upload form (included by crad/step.php). Uses $manuscript from GET me/research/manuscript. */
?>
<section class="portal-card mb-3">
    <div class="d-flex align-items-center gap-3 mb-3">
        <span class="sp-ic <?= e($tone) ?>"><i class="fa-solid fa-book"></i></span>
        <div class="flex-grow-1"><div class="small text-muted fw-semibold text-uppercase"><?= $step ? 'Step ' . e((string) $step['no']) : 'Final manuscript' ?></div><div class="fw-bold fs-5" style="color:var(--ink)">Submit the final manuscript</div></div>
        <?= portal_status_badge($step['status'] ?? null) ?>
    </div>
    <?php if (!is_array($manuscript)): ?>
        <?= portal_empty('Your manuscript status could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($manuscript['can_submit'])): ?>
        <div class="sp-note"><i class="fa-solid fa-lock mt-1"></i><span><?= e($manuscript['message'] ?: 'Not yet eligible.') ?></span></div>
    <?php else: ?>
        <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-sp-upload data-max-mb="<?= e((string) (int) ($manuscript['max_mb'] ?? 20)) ?>">
            <?= portal_csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="manuscript">Full Chapter 1-5 manuscript</label>
                <label class="sp-drop d-block" for="manuscript">
                    <i class="fa-solid fa-cloud-arrow-up d-block"></i>
                    <b style="color:var(--ink)">Choose your PDF or Word file</b><br>
                    <span class="small">.pdf, .doc, .docx · up to <?= e((string) (int) ($manuscript['max_mb'] ?? 20)) ?> MB</span><br>
                    <span class="small fw-semibold" data-file-name>No file selected</span>
                </label>
                <input class="visually-hidden" type="file" id="manuscript" name="manuscript" accept=".pdf,.doc,.docx" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="notes">Submission notes (optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000"></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="crad/index.php" class="btn btn-soft">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Submit manuscript</button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php if (!empty($manuscript['submissions'])): ?>
    <section class="portal-card">
        <div class="sp-section-h"><h2>Versions sent</h2></div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Version</th><th>File</th><th>Status</th><th>Sent</th></tr></thead>
            <tbody><?php foreach ($manuscript['submissions'] as $m): ?>
                <tr><td>v<?= e((string) $m['version']) ?></td><td class="text-break"><?= e($m['file_name']) ?></td><td><?= portal_status_badge($m['status']) ?></td><td><?= e(portal_date($m['submitted_at'])) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
<?php endif; ?>
