<?php
/** Old link, kept so bookmarks keep working: the CRAD pages moved to crad/. */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';
$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
header('Location: ' . portal_url('crad/step.php' . ($query !== '' ? '?' . $query : '')), true, 301);
