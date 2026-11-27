<?php
/** Student profile from SMS 2 (Registrar data). */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/layout.php';
$user = portal_require_login();
$me = portal_api('me') ?? [];
$p = $me['profile'] ?? [];
$rows = [
    'Student number' => $p['student_id'] ?? null, 'Name' => $p['name'] ?? $user['name'], 'Email' => $p['email'] ?? $user['email'],
    'Program' => $p['program'] ?? null, 'Year level' => $p['year_level'] ?? null, 'Section' => $p['section'] ?? null,
    'Semester' => $p['semester'] ?? null, 'School year' => $p['school_year'] ?? null, 'Enrollment status' => $p['enrollment_status'] ?? null,
    'Standing' => $p['standing'] ?? null, 'Mobile' => $p['mobile'] ?? null, 'Address' => $p['address'] ?? null,
    'Guardian' => $p['guardian'] ?? null, 'Guardian contact' => $p['guardian_contact'] ?? null,
];
portal_header('My profile', 'profile', $user);
?>
<section class="portal-card">
    <dl class="row mb-0">
        <?php foreach ($rows as $label => $value): ?>
            <dt class="col-sm-4 fw-normal text-muted"><?= e($label) ?></dt><dd class="col-sm-8"><?= e(($value ?? '') !== '' ? $value : '—') ?></dd>
        <?php endforeach; ?>
    </dl>
    <p class="small text-muted mt-3 mb-0">Records are kept by the Registrar in SMS 2. Ask the Registrar to correct anything here.</p>
</section>
<?php portal_footer();
