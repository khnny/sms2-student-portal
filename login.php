<?php
/** Start sign-in: send the student to the SMS 2 login (shared account, OTP and Authenticator). */
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

if (portal_logged_in()) {
    portal_redirect(portal_url('index.php'));
}

if (isset($_GET['go'])) {
    $state = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    $_SESSION['oauth_state'] = $state;
    $query = http_build_query([
        'client_id' => PORTAL_CLIENT_ID,
        'redirect_uri' => portal_url('callback.php'),
        'state' => $state,
    ]);
    portal_redirect(PORTAL_SMS2_URL . '/login/sso-authorize.php?' . $query);
}

$flash = portal_flash();
$pageTitle = 'Sign in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(PORTAL_APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/vendor/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-login">
<main class="login-card">
    <img src="assets/img/bcp-crest.png" alt="" class="login-crest">
    <h1>Student Portal</h1>
    <p class="text-muted">Bestlink College of the Philippines</p>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> text-start"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <a class="btn btn-primary btn-lg w-100" href="login.php?go=1">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign in with your SMS 2 account
    </a>
    <p class="small text-muted mt-3 mb-0">You will sign in on the SMS 2 page, then come back here.</p>
</main>
</body>
</html>
