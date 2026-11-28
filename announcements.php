<?php
/** Old address of the Announcement Bulletin, which is now the portal home (index.php). */
declare(strict_types=1);
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: index.php' . ($query !== '' ? '?' . $query : ''), true, 301);
exit;
