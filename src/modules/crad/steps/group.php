<?php
/** Research group form (included by crad/step.php). Uses $data from GET me/research/group. */
$members = $data['members'] ?? [];
$max = (int) ($data['max_members'] ?? 5);
$min = (int) ($data['min_members'] ?? 5);
if ($members === []) {
    $members = [['full_name' => (string) $user['name'], 'student_id' => (string) ($user['student_id'] ?? ''), 'section' => '', 'is_leader' => true]];
}
?>
<section class="portal-card">
    <?= portal_step_head('fa-users', 'Research group', $tone, $step, 'Research group') ?>
    <?php if (!is_array($data)): ?>
        <?= portal_empty('Your research group could not be loaded right now. Please refresh the page.') ?>
    <?php else: ?>
        <dl class="sp-facts">
            <div><dt>Status</dt><dd><?= e($data['status_label']) ?></dd></div>
            <?php if ($data['group_number'] !== ''): ?><div><dt>Group</dt><dd><?= e($data['group_number']) ?></dd></div><?php endif; ?>
            <div><dt>Members needed</dt><dd><?= e($min === $max ? (string) $max : $min . ' to ' . $max) ?></dd></div>
        </dl>
        <?php if ($data['remarks'] !== ''): ?><?= portal_note('Remarks: ' . $data['remarks'], 'fa-comment', 'mb-3') ?><?php endif; ?>
        <?php if (empty($data['can_edit'])): ?>
            <div class="table-responsive mb-3"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Name</th><th>Student no.</th><th>Section</th></tr></thead>
                <tbody><?php foreach ($members as $m): ?><tr><td><?= e($m['full_name']) ?><?= !empty($m['is_leader']) ? ' <span class="badge text-bg-light">Leader</span>' : '' ?></td><td><?= e($m['student_id']) ?></td><td><?= e($m['section']) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
            <?= portal_note('Your group was submitted and is being processed. You will get a notification when it is approved.', 'fa-lock') ?>
        <?php else: ?>
            <form method="post" action="<?= e($self) ?>">
                <?= portal_csrf_field() ?>
                <p class="small text-muted">You are the leader (first row). Add each member's full name as Last, First M.I., student number and section.</p>
                <?php for ($i = 0; $i < $max; $i++): $m = $members[$i] ?? ['full_name' => '', 'student_id' => '', 'section' => '']; ?>
                    <div class="row g-2 mb-2">
                        <div class="col-md-5"><input class="form-control" name="member_name[]" placeholder="<?= $i === 0 ? 'Leader full name' : 'Member ' . ($i + 1) . ' full name' ?>" value="<?= e($m['full_name']) ?>" maxlength="160" <?= $i === 0 ? 'required' : '' ?>></div>
                        <div class="col-md-4"><input class="form-control" name="member_student_id[]" placeholder="Student no." value="<?= e($m['student_id']) ?>" maxlength="40" <?= $i === 0 ? 'required' : '' ?>></div>
                        <div class="col-md-3"><input class="form-control" name="member_section[]" placeholder="Section" value="<?= e($m['section']) ?>" maxlength="60" <?= $i === 0 ? 'required' : '' ?>></div>
                    </div>
                <?php endfor; ?>
                <div class="mb-3 mt-3">
                    <label class="form-label" for="incomplete_reason">Reason, if your group has fewer than <?= e((string) $min) ?> members</label>
                    <textarea class="form-control" id="incomplete_reason" name="incomplete_reason" rows="2" maxlength="1000"><?= e($data['incomplete_reason']) ?></textarea>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-soft" type="submit" name="action" value="draft">Save draft</button>
                    <button class="btn btn-primary" type="submit" name="action" value="submit"><i class="fa-solid fa-paper-plane me-1"></i>Submit group</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>
