<?php
/** Page shell: top bar, side navigation, flash message. */
declare(strict_types=1);

function portal_header(string $title, string $active, array $user): void
{
    $nav = [
        'index' => ['Dashboard', 'fa-gauge-high'],
        'research' => ['My research', 'fa-flask'],
        'defenses' => ['Defense schedule', 'fa-calendar-check'],
        'clearance' => ['Clearance & payment', 'fa-clipboard-check'],
        'announcements' => ['Announcements', 'fa-bullhorn'],
        'notifications' => ['Notifications', 'fa-bell'],
        'profile' => ['My profile', 'fa-id-card'],
    ];
    $flash = portal_flash();
    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim((string) $user['name'])) ?: [], 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(PORTAL_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
<header class="portal-topbar">
    <button class="btn btn-link text-white d-lg-none p-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#portalNav" aria-label="Menu">
        <i class="fa-solid fa-bars fa-lg"></i>
    </button>
    <a class="portal-brand" href="index.php"><img src="assets/img/bcp-crest.png" alt=""> <span>Student Portal</span></a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="d-none d-sm-inline small text-white-50"><?= e($user['student_id'] ?? '') ?></span>
        <span class="portal-avatar" title="<?= e($user['name']) ?>"><?= e($initials ?: 'ST') ?></span>
        <form method="post" action="logout.php" class="m-0">
            <?= portal_csrf_field() ?>
            <button class="btn btn-sm btn-outline-light" type="submit"><i class="fa-solid fa-right-from-bracket me-1"></i>Sign out</button>
        </form>
    </div>
</header>
<div class="portal-shell">
    <nav class="portal-nav offcanvas-lg offcanvas-start" id="portalNav" tabindex="-1" aria-label="Portal">
        <div class="offcanvas-body d-block p-0">
            <ul class="nav flex-column">
                <?php foreach ($nav as $key => [$label, $icon]): ?>
                    <li><a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="<?= e($key) ?>.php"><i class="fa-solid <?= e($icon) ?>"></i><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="portal-nav-heading">Submit in SMS 2</div>
            <ul class="nav flex-column">
                <?php foreach (portal_sms2_actions() as $key => $action): ?>
                    <li><a class="nav-link" href="open.php?to=<?= e($key) ?>"><i class="fa-solid <?= e($action['icon']) ?>"></i><?= e($action['label']) ?> <i class="fa-solid fa-arrow-up-right-from-square ms-auto small opacity-50"></i></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
    <main class="portal-main">
        <h1 class="portal-title"><?= e($title) ?></h1>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function portal_footer(): void
{
    if (!empty($GLOBALS['portal_api_errors'])) {
        echo '<div class="alert alert-warning mt-4 small">Some information could not be loaded from SMS 2 right now. Refresh the page to try again.</div>';
    }
    ?>
    </main>
</div>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}

function portal_status_badge(?string $status): string
{
    $s = strtolower((string) $status);
    $class = 'text-bg-secondary';
    if (preg_match('/approved|accepted|completed|cleared|confirmed|paid|done/', $s)) {
        $class = 'text-bg-success';
    } elseif (preg_match('/reject|returned|revision|failed|cancel/', $s)) {
        $class = 'text-bg-danger';
    } elseif (preg_match('/pending|review|submitted|scheduled|progress|sent|assigned|active/', $s)) {
        $class = 'text-bg-primary';
    }
    return '<span class="badge ' . $class . '">' . e($status !== null && $status !== '' ? $status : 'None') . '</span>';
}

function portal_empty(string $text): string
{
    return '<p class="text-muted mb-0"><i class="fa-regular fa-folder-open me-2"></i>' . e($text) . '</p>';
}
