<?php
/** Research clearance and college payment status. */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$user = portal_require_login();
$data = portal_api('me/clearance') ?? ['clearances' => [], 'payments' => []];
$stage = static fn($s) => ucwords(str_replace('_', ' ', (string) $s));
portal_header('Clearance & payment', 'clearance', $user);
?>
<div class="row g-3">
    <div class="col-lg-6">
        <section class="portal-card h-100">
            <div class="d-flex justify-content-between"><h2 class="h6 text-uppercase text-muted">College payment</h2><a class="small" href="open.php?to=payment">Upload payment</a></div>
            <?php if ($data['payments']): ?>
                <table class="table table-sm mb-0"><thead><tr><th>Stage</th><th>OR number</th><th>Uploaded</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($data['payments'] as $p): ?>
                    <tr><td><?= e($stage($p['stage'])) ?></td><td><?= e($p['or_number'] ?: '—') ?></td><td><?= e(portal_date($p['uploaded_at'])) ?></td><td><?= portal_status_badge($p['status']) ?></td></tr>
                    <?php if (!empty($p['remarks'])): ?><tr><td colspan="4" class="small text-muted"><?= e($p['remarks']) ?></td></tr><?php endif; ?>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: ?>
                <?= portal_empty('No payment uploaded yet.') ?>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="portal-card h-100">
            <div class="d-flex justify-content-between"><h2 class="h6 text-uppercase text-muted">Research clearance</h2><a class="small" href="open.php?to=clearance">Open clearance form</a></div>
            <?php if ($data['clearances']): ?>
                <?php foreach ($data['clearances'] as $c): ?>
                    <div class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between"><strong><?= e($stage($c['stage'])) ?></strong><?= portal_status_badge($c['status']) ?></div>
                        <ul class="list-unstyled small mb-0 mt-1">
                            <li><?= $c['adviser_signed'] ? '✓' : '○' ?> Adviser signature</li>
                            <li><?= $c['crad_signed'] ? '✓' : '○' ?> CRAD signature</li>
                            <li><?= $c['mis_verified'] ? '✓' : '○' ?> MIS verified</li>
                            <li><?= $c['aa_verified'] ? '✓' : '○' ?> Academic Affairs verified</li>
                        </ul>
                        <?php if (!empty($c['crad_remarks'])): ?><p class="small text-muted mb-0 mt-1"><?= e($c['crad_remarks']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?= portal_empty('No clearance started yet.') ?>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php portal_footer();
