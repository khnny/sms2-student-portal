<?php
/** Progress update form (included by step.php). Uses $data from GET me/research/progress. */
$open = array_values(array_filter($data['milestones'] ?? [], static fn($m) => !empty($m['can_submit'])));
?>
<section class="portal-card">
    <?= portal_step_head('fa-chart-line', 'Send a progress update', $tone, $step, 'Progress updates') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your progress updates could not be loaded right now. Please refresh the page.') ?>
    <?php elseif (empty($data['available'])): ?>
        <?= portal_note($data['message'], 'fa-lock') ?>
    <?php else: ?>
        <div class="table-responsive mb-3"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Milestone</th><th>Progress</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($data['milestones'] as $m): ?>
                <tr><td><?= e($m['name']) ?></td><td><?= e((string) round((float) $m['progress'])) ?>%</td><td><?= portal_status_badge(!empty($m['pending']) ? 'Waiting for adviser' : $m['status']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php if ($open === []): ?>
            <?= portal_note('No milestone is open for an update right now. Each one opens again after your adviser responds.') ?>
        <?php else: ?>
            <form method="post" action="<?= e($self) ?>" enctype="multipart/form-data" data-sp-upload data-max-mb="<?= e((string) (int) ($data['max_mb'] ?? 10)) ?>">
                <?= portal_csrf_field() ?>
                <input type="hidden" name="submission_token" value="<?= e(bin2hex(random_bytes(32))) ?>">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="milestone_id">Milestone</label>
                        <select class="form-select" id="milestone_id" name="milestone_id" required>
                            <?php foreach ($open as $m): ?><option value="<?= e((string) $m['id']) ?>"><?= e($m['name']) ?> (now <?= e((string) round((float) $m['progress'])) ?>%)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="new_progress">New progress %</label>
                        <input class="form-control" type="number" id="new_progress" name="new_progress" min="0" max="100" step="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="milestone_status">Status</label>
                        <select class="form-select" id="milestone_status" name="milestone_status">
                            <?php foreach ($data['statuses'] as $st): ?><option <?= $st === 'Submitted for Review' ? 'selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3"><label class="form-label" for="update_title">Update title</label><input class="form-control" id="update_title" name="update_title" maxlength="300" placeholder="e.g. Chapter 2 literature review draft"></div>
                <div class="mb-3"><label class="form-label" for="accomplishments">What you finished</label><textarea class="form-control" id="accomplishments" name="accomplishments" rows="3" maxlength="4000" required></textarea></div>
                <div class="mb-3"><label class="form-label" for="problems_blockers">Problems or blockers</label><textarea class="form-control" id="problems_blockers" name="problems_blockers" rows="2" maxlength="4000"></textarea></div>
                <div class="mb-3"><label class="form-label" for="next_planned_activity">Next planned activity</label><textarea class="form-control" id="next_planned_activity" name="next_planned_activity" rows="2" maxlength="4000"></textarea></div>
                <?= portal_file_field('document', 'Document (needed for a chapter sent for review)', '.pdf,.doc,.docx,.jpg,.jpeg,.png', '.pdf, .doc, .docx, .jpg, .png · up to ' . (int) ($data['max_mb'] ?? 10) . ' MB', false) ?>
                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="research.php" class="btn btn-soft">Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane me-1"></i>Send update</button>
                </div>
            </form>
        <?php endif; ?>
        <?php if (!empty($data['history'])): ?>
            <div class="sp-section-h mt-4"><h2>Your recent updates</h2></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Milestone</th><th>Update</th><th>%</th><th>Status</th><th>Sent</th></tr></thead>
                <tbody><?php foreach (array_slice($data['history'], 0, 10) as $h): ?>
                    <tr><td><?= e($h['milestone']) ?></td><td><?= e($h['title']) ?></td><td><?= e((string) round((float) $h['progress'])) ?></td><td><?= portal_status_badge($h['status']) ?></td><td><?= e(portal_date($h['submitted_at'])) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    <?php endif; ?>
</section>
