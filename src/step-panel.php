<?php
/** Defense application (CRAD calls the table "panel requests") (included by step.php). Uses $data from GET me/research/panel. */
$app = $data['application'] ?? null;
?>
<section class="portal-card">
    <?= portal_step_head('fa-people-group', 'Defense application', $tone, $step, 'Defense') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your defense application could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($data['available']) || !$app): ?>
        <?= portal_note($data['message'] ?? 'Not available yet.', 'fa-lock') ?>
    <?php else: ?>
        <dl class="sp-facts">
            <div><dt>Reference</dt><dd><?= e($app['ref']) ?></dd></div>
            <div><dt>Status</dt><dd><?= portal_status_badge($app['status_label']) ?></dd></div>
        </dl>
        <?php if ($app['review_remarks'] !== ''): ?><?= portal_note('CRAD remarks: ' . $app['review_remarks'], 'fa-comment', 'mb-3') ?><?php endif; ?>
        <?php if (empty($app['editable'])): ?>
            <dl class="sp-facts">
                <div><dt>Research title</dt><dd><?= e($app['research_title']) ?></dd></div>
                <div><dt>Adviser</dt><dd><?= e($app['adviser_name']) ?></dd></div>
            </dl>
            <?= portal_note('Your application was sent. CRAD will review it and set your defense schedule.', 'fa-hourglass-half') ?>
        <?php else: ?>
            <h3 class="h6 fw-bold mt-2">1. Details</h3>
            <form method="post" action="<?= e($self) ?>" class="mb-4">
                <?= portal_csrf_field() ?><input type="hidden" name="action" value="save_details">
                <div class="mb-2"><label class="form-label" for="research_title">Research title</label><input class="form-control" id="research_title" name="research_title" maxlength="255" value="<?= e($app['research_title']) ?>" required></div>
                <div class="mb-2"><label class="form-label" for="adviser_name">Adviser</label><input class="form-control" id="adviser_name" name="adviser_name" maxlength="160" value="<?= e($app['adviser_name']) ?>" required></div>
                <div class="mb-2"><label class="form-label" for="members_text">Members (one name per line)</label><textarea class="form-control" id="members_text" name="members_text" rows="4" maxlength="2000" required><?= e($app['members_text']) ?></textarea></div>
                <div class="text-end"><button class="btn btn-soft" type="submit">Save details</button></div>
            </form>
            <h3 class="h6 fw-bold">2. Documents</h3>
            <?php foreach ($app['documents'] as $doc): ?>
                <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" class="border rounded-3 p-3 mb-2" data-sp-upload data-max-mb="<?= e((string) (int) ($data['max_mb'] ?? 20)) ?>">
                    <?= portal_csrf_field() ?><input type="hidden" name="action" value="upload_document"><input type="hidden" name="doc_type" value="<?= e($doc['type']) ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2"><b style="color:var(--ink)"><?= e($doc['label']) ?></b><?= $doc['uploaded'] ? '<span class="badge text-bg-success">Uploaded</span>' : '<span class="badge text-bg-secondary">Missing</span>' ?></div>
                    <?php if ($doc['uploaded']): ?><div class="small text-muted mb-2"><?= e($doc['uploaded']['file_name']) ?> · <?= e(portal_date($doc['uploaded']['uploaded_at'])) ?></div><?php endif; ?>
                    <div class="d-flex gap-2"><input class="form-control" type="file" name="document" accept=".pdf,.png,.jpg,.jpeg" required><button class="btn btn-soft" type="submit"><?= $doc['uploaded'] ? 'Replace' : 'Upload' ?></button></div>
                </form>
            <?php endforeach; ?>
            <h3 class="h6 fw-bold mt-4">3. Submit</h3>
            <form method="post" action="<?= e($self) ?>">
                <?= portal_csrf_field() ?><input type="hidden" name="action" value="submit">
                <?= portal_note('Submit once the details are saved and both documents are uploaded. CRAD reviews it, assigns your panel and sets your defense schedule.') ?>
                <div class="text-end mt-3"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Submit defense application</button></div>
            </form>
        <?php endif; ?>
        <?php if (!empty($app['history'])): ?>
            <div class="sp-section-h mt-4"><h2>History</h2></div>
            <ul class="list-unstyled small mb-0"><?php foreach ($app['history'] as $h): ?>
                <li class="mb-1"><?= portal_status_badge($h['status']) ?> <?= e(portal_date($h['at'], 'M j, Y g:i A')) ?><?= $h['by'] !== '' ? ' · ' . e($h['by']) : '' ?><?= $h['remarks'] !== '' ? ' · ' . e($h['remarks']) : '' ?></li>
            <?php endforeach; ?></ul>
        <?php endif; ?>
    <?php endif; ?>
</section>
