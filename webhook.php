<?php
/**
 * Webhook receiver: SMS 2 / CRAD pushes research events here.
 *
 * Checks: POST only, valid HMAC-SHA256 signature, timestamp within 5 minutes,
 * and each event id is processed once (a resend returns 200 without changes).
 * Each event becomes a notification for the students it names.
 */
declare(strict_types=1);
require_once __DIR__ . '/src/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function webhook_reply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    webhook_reply(405, ['error' => 'method_not_allowed']);
}
if (PORTAL_WEBHOOK_SECRET === '') {
    webhook_reply(503, ['error' => 'webhook_secret_not_configured']);
}

$body = (string) file_get_contents('php://input', false, null, 0, 1048576);
$timestamp = (int) ($_SERVER['HTTP_X_SMS2_TIMESTAMP'] ?? 0);
$signature = (string) ($_SERVER['HTTP_X_SMS2_SIGNATURE'] ?? '');
$expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, PORTAL_WEBHOOK_SECRET);

if ($timestamp <= 0 || abs(time() - $timestamp) > 300) {
    webhook_reply(401, ['error' => 'stale_or_missing_timestamp']);
}
if (!hash_equals($expected, $signature)) {
    webhook_reply(401, ['error' => 'bad_signature']);
}

$event = json_decode($body, true);
if (!is_array($event) || empty($event['id']) || empty($event['type']) || !preg_match('/^[0-9a-f-]{36}$/', (string) $event['id'])) {
    webhook_reply(400, ['error' => 'bad_payload']);
}

$db = portal_db();
$insert = $db->prepare('INSERT IGNORE INTO portal_webhook_events (event_id, event_type, payload) VALUES (?, ?, ?)');
$insert->execute([(string) $event['id'], (string) $event['type'], $body]);
if ($insert->rowCount() === 0) {
    webhook_reply(200, ['ok' => true, 'duplicate' => true]);
}

// The module that owns this event type (src/modules/*.php) writes the notification text.
$module = portal_module_for_event((string) $event['type']);
$message = $module && is_callable($module['message'])
    ? ($module['message'])((string) $event['type'], is_array($event['data'] ?? null) ? $event['data'] : [])
    : null;
[$title, $text] = $message ?? ['SMS 2 update', 'There is an update on your account.'];

$studentIds = array_map('strtoupper', array_filter(array_map('strval', $event['subjects']['student_ids'] ?? []), 'strlen'));
$userIds = array_filter(array_map('intval', $event['subjects']['user_ids'] ?? []));
if ($userIds) {
    $in = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $db->prepare("SELECT user_id, student_id FROM portal_users WHERE user_id IN ($in)");
    $stmt->execute(array_values($userIds));
    foreach ($stmt->fetchAll() as $row) {
        if ($row['student_id']) {
            $studentIds[] = strtoupper((string) $row['student_id']);
            $userIds = array_diff($userIds, [(int) $row['user_id']]);
        }
    }
}
$note = $db->prepare('INSERT INTO portal_notifications (event_id, user_id, student_id, event_type, title, body) VALUES (?, ?, ?, ?, ?, ?)');
foreach (array_unique($studentIds) as $sid) {
    $note->execute([(string) $event['id'], null, $sid, (string) $event['type'], $title, $text]);
}
// User ids are only a fallback when an event names no student numbers.
foreach ($studentIds ? [] : array_unique($userIds) as $uid) {
    $note->execute([(string) $event['id'], $uid, null, (string) $event['type'], $title, $text]);
}

webhook_reply(200, ['ok' => true]);
