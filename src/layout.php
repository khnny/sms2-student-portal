<?php
/** Page shell: top bar, side navigation, flash message. */
declare(strict_types=1);

function portal_header(string $title, string $active, array $user): void
{
    // Sidebar, grouped like the CRAD (SMS 2) student sidebar.
    $nav = [
        ['index', 'Dashboard', 'fa-gauge'],
        ['research', 'Research Workspace', 'fa-flask'],
        ['group' => 'Research Steps', 'icon' => 'fa-list-check', 'items' => [
            ['step.php?do=group', 'Research group', 'fa-user-group'],
            ['step.php?do=proposal', 'Title approval', 'fa-file-signature'],
            ['step.php?do=chapters', 'Submit chapters', 'fa-file-arrow-up'],
            ['step.php?do=progress', 'Progress updates', 'fa-chart-line'],
            ['step.php?do=manuscript', 'Final manuscript', 'fa-book'],
        ]],
        ['group' => 'Panel & Defense', 'icon' => 'fa-chalkboard-user', 'items' => [
            ['step.php?do=panel', 'Panel application', 'fa-people-group'],
            ['defenses', 'Defense schedule', 'fa-calendar-check'],
        ]],
        ['group' => 'Financial', 'icon' => 'fa-wallet', 'items' => [
            ['step.php?do=payment', 'Upload college payment', 'fa-receipt'],
            ['clearance', 'Clearance & payment', 'fa-clipboard-check'],
        ]],
        ['announcements', 'Announcement Bulletin', 'fa-bullhorn'],
        ['notifications', 'Notifications', 'fa-bell'],
        ['profile', 'My profile', 'fa-id-card'],
    ];
    $isActive = static function (string $key) use ($active): bool {
        return $key === $active || $key === 'step.php?do=' . $active;
    };
    $href = static fn(string $key): string => str_contains($key, '.php') ? $key : $key . '.php';
    $flash = portal_flash();
    $initials = '';
    foreach (array_slice(preg_split('/\s+/', trim((string) $user['name'])) ?: [], 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    $unread = portal_unread_count($user);
    $crumbs = $active === 'index' ? [] : [$title];
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(PORTAL_APP_NAME) ?></title>
    <script>try{var t=localStorage.getItem('portal-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/portal.css?v=3">
</head>
<body class="sms-app">
<header class="sp-topbar">
    <button class="sp-icon-btn sp-menu" type="button" data-sp-menu aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <a class="sp-brand" href="index.php"><img src="assets/img/bcp-crest.png" alt=""><span>BCP</span><small>Student Portal</small></a>
    <div class="sp-top-right">
        <span class="sp-clock d-none d-md-inline" data-sp-clock><?= e(date('h:i:s A')) ?></span>
        <button class="sp-icon-btn sp-theme" type="button" data-sp-theme aria-label="Dark mode"><i class="fa-regular fa-moon"></i></button>
        <a class="sp-icon-btn sp-bell" href="notifications.php" aria-label="Notifications"><i class="fa-regular fa-bell"></i><?php if ($unread > 0): ?><span class="sp-badge"><?= e((string) min(99, $unread)) ?></span><?php endif; ?></a>
        <div class="dropdown">
            <button class="sp-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="sp-avatar"><?= e($initials ?: 'ST') ?></span><span class="d-none d-sm-inline"><?= e($user['name']) ?></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end sp-user-menu">
                <div class="px-3 py-2"><div class="fw-semibold"><?= e($user['name']) ?></div><div class="small text-muted"><?= e($user['student_id'] ?? '') ?></div></div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="profile.php"><i class="fa-regular fa-id-card me-2"></i>My profile</a>
                <a class="dropdown-item" href="notifications.php"><i class="fa-regular fa-bell me-2"></i>Notifications</a>
                <div class="dropdown-divider"></div>
                <form method="post" action="logout.php" class="m-0">
                    <?= portal_csrf_field() ?>
                    <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
<div class="sp-backdrop" data-sp-menu></div>
<nav class="sp-sidebar" id="portalNav" aria-label="Portal">
    <ul class="sp-nav">
        <?php foreach ($nav as $i => $item): ?>
            <?php if (isset($item['group'])):
                $open = false;
                foreach ($item['items'] as [$k]) { $open = $open || $isActive($k); } ?>
                <li class="sp-group<?= $open ? ' open' : '' ?>">
                    <button type="button" class="sp-link" data-sp-group aria-expanded="<?= $open ? 'true' : 'false' ?>"><i class="fa-solid <?= e($item['icon']) ?>"></i><span><?= e($item['group']) ?></span><i class="fa-solid fa-chevron-down sp-chev"></i></button>
                    <ul>
                        <?php foreach ($item['items'] as [$key, $label, $icon]): ?>
                            <li><a class="sp-link sp-sub<?= $isActive($key) ? ' active' : '' ?>" href="<?= e($href($key)) ?>"><i class="fa-solid <?= e($icon) ?>"></i><span><?= e($label) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php else: [$key, $label, $icon] = $item; ?>
                <li><a class="sp-link<?= $isActive($key) ? ' active' : '' ?>" href="<?= e($href($key)) ?>"><i class="fa-solid <?= e($icon) ?>"></i><span><?= e($label) ?></span></a></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
<div class="sp-content">
    <main class="sp-main">
        <nav class="sp-crumbs" aria-label="breadcrumb"><a href="index.php"><i class="fa-solid fa-house"></i></a><span>/</span><a href="index.php">Student Portal</a><span>/</span><b><?= e($active === 'index' ? 'Dashboard' : $title) ?></b></nav>
        <?php if ($active !== 'research'): ?><h1 class="sp-title"><?= e($title) ?></h1><?php endif; ?>
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
    <footer class="sp-footer"><span>&copy; <?= e(date('Y')) ?> Bestlink College of the Philippines. All rights reserved.</span><span>BCP Student Portal</span></footer>
</div>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/portal.js?v=1" defer></script>
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
    $label = $status !== null && $status !== '' ? (string) $status : 'None';
    if (preg_match('/^[a-z0-9_]+$/', $label)) {
        $label = ucfirst(str_replace('_', ' ', $label)); // clearance_done -> Clearance done
    }
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

function portal_empty(string $text): string
{
    return '<p class="text-muted mb-0"><i class="fa-regular fa-folder-open me-2"></i>' . e($text) . '</p>';
}
