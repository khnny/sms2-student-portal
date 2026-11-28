<?php
/** College payment upload (included by crad/step.php). Uses $data from GET me/research/payment. */
?>
<section class="portal-card">
    <?= portal_step_head('fa-receipt', 'Upload college payment', $tone, $step, 'Payment') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your payments could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($data['available'])): ?>
        <?= portal_note($data['message'], 'fa-lock') ?>
    <?php else: ?>
        <?php foreach ($data['stages'] as $p): ?>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><b style="color:var(--ink)"><?= e($p['label']) ?></b><?= portal_status_badge($p['status_label']) ?></div>
                <dl class="sp-facts mb-2">
                    <div><dt>O.R. / reference no.</dt><dd><?= e($p['or_number'] ?: '—') ?></dd></div>
                    <?php if ($p['file_name'] !== ''): ?><div><dt>Picture</dt><dd><?= e($p['file_name']) ?></dd></div><?php endif; ?>
                    <?php if ($p['remarks'] !== ''): ?><div><dt>Remarks</dt><dd><?= e($p['remarks']) ?></dd></div><?php endif; ?>
                </dl>
                <?php if ($p['locked_reason'] !== ''): ?>
                    <?= portal_note($p['locked_reason'], 'fa-lock') ?>
                <?php elseif (!empty($p['can_upload'])): ?>
                    <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-sp-upload data-max-mb="<?= e((string) (int) $data['max_mb']) ?>">
                        <?= portal_csrf_field() ?><input type="hidden" name="stage" value="<?= e($p['stage']) ?>">
                        <?= portal_file_field('payment_file', 'Picture of the receipt', '.png,.jpg,.jpeg', 'PNG or JPG · up to ' . (int) $data['max_mb'] . ' MB', true, 'payment_file_' . $p['stage']) ?>
                        <div class="mb-2"><label class="form-label" for="or_<?= e($p['stage']) ?>">O.R. / reference no. (optional, read from the picture if blank)</label><input class="form-control" id="or_<?= e($p['stage']) ?>" name="or_number" maxlength="60" value="<?= e($p['or_number']) ?>"></div>
                        <div class="text-end"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload me-1"></i>Upload payment</button></div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?= portal_note('Admin checks the payment in SMS 2. Once approved, its O.R. number appears on your research clearance.') ?>
    <?php endif; ?>
</section>
