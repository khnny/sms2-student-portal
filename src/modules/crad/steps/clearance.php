<?php
/** Research Services Clearance (included by crad/step.php). Uses $data from GET me/research/clearance-form. */
$crestCss = PORTAL_SMS2_URL . '/modules/crad/assets/css/research-clearance.css';
?>
<section class="portal-card">
    <?= portal_step_head('fa-clipboard-check', 'Research services clearance', $tone, $step, 'Clearance') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your clearance could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($data['available'])): ?>
        <?= portal_note($data['message'], 'fa-lock') ?>
    <?php else: ?>
        <?= portal_note('Print the form, have it signed, then upload a clear picture of the signed form. CRAD approves it in SMS 2.', 'fa-circle-info', 'mb-3') ?>
        <?php foreach ($data['stages'] as $c): ?>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><b style="color:var(--ink)"><?= e($c['label']) ?></b><?= portal_status_badge($c['status_label']) ?></div>
                <?php if ($c['crad_remarks'] !== ''): ?><?= portal_note('CRAD: ' . $c['crad_remarks'], 'fa-comment', 'mb-2') ?><?php endif; ?>
                <?php if ($c['locked_reason'] !== ''): ?>
                    <?= portal_note($c['locked_reason'], 'fa-lock') ?>
                <?php else: ?>
                    <?php if ($c['form_html'] !== ''): ?>
                        <details class="mb-2">
                            <summary class="small fw-semibold">Show the printable form</summary>
                            <!-- CRAD renders the form; it runs with no scripts in its own frame. -->
                            <iframe class="w-100 border rounded mt-2" style="height:520px;background:#fff" sandbox="allow-same-origin allow-modals" data-sp-print-frame="<?= e((string) $c['id']) ?>"
                                    srcdoc="<?= e('<!DOCTYPE html><html><head><meta charset="utf-8"><link rel="stylesheet" href="' . e($crestCss) . '"></head><body>' . $c['form_html'] . '</body></html>') ?>"></iframe>
                            <div class="text-end mt-2"><button type="button" class="btn btn-soft btn-sm" data-sp-print="<?= e((string) $c['id']) ?>"><i class="fa-solid fa-print me-1"></i>Print form</button></div>
                        </details>
                    <?php endif; ?>
                    <?php if ($c['file_name'] !== ''): ?><div class="small text-muted mb-2">Uploaded: <?= e($c['file_name']) ?></div><?php endif; ?>
                    <?php if (!empty($c['can_upload']) && $c['id'] > 0): ?>
                        <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-sp-upload data-max-mb="<?= e((string) (int) $data['max_mb']) ?>">
                            <?= portal_csrf_field() ?><input type="hidden" name="id" value="<?= e((string) $c['id']) ?>">
                            <?= portal_file_field('clearance_file', 'Picture of the signed form', '.png,.jpg,.jpeg', 'PNG or JPG · up to ' . (int) $data['max_mb'] . ' MB', true, 'clearance_file_' . $c['id']) ?>
                            <div class="text-end"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload me-1"></i>Upload signed form</button></div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
