<?php
/**
 * CRAD (Research) module: every research step the student must finish.
 * Contract: see src/modules.php. CRAD officers, advisers and panels still
 * review everything inside SMS 2; the portal only shows status and sends
 * the student's own actions through the SMS 2 API.
 */
declare(strict_types=1);

return [
    'key' => 'crad',
    'label' => 'Research',
    'icon' => 'fa-flask',
    'enabled' => true,
    'api_prefix' => 'me/research',
    'nav' => [
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
    ],
    // Every CRAD student step is done in the portal (step.php?do=<key>). An action
    // with a 'path' would instead open that SMS 2 page through open.php.
    'actions' => [
        'proposal' => ['label' => 'Research proposal / title', 'icon' => 'fa-file-signature'],
        'group' => ['label' => 'Research group', 'icon' => 'fa-users'],
        'chapters' => ['label' => 'Submit chapters', 'icon' => 'fa-file-arrow-up'],
        'progress' => ['label' => 'Progress updates', 'icon' => 'fa-chart-line'],
        'panel' => ['label' => 'Panel application', 'icon' => 'fa-people-group'],
        'manuscript' => ['label' => 'Final manuscript', 'icon' => 'fa-book'],
        'payment' => ['label' => 'Upload college payment', 'icon' => 'fa-receipt'],
        'clearance' => ['label' => 'Research clearance', 'icon' => 'fa-clipboard-check'],
    ],
    // Research-statement step key => portal action key.
    'step_actions' => [
        'group' => 'group', 'assignment' => 'group', 'title' => 'proposal', 'registry' => 'group',
        'chapters' => 'chapters', 'progress' => 'progress', 'defense' => 'panel',
        'manuscript' => 'manuscript', 'clearance' => 'clearance',
    ],
    'webhooks' => ['research.', 'submission.', 'defense.', 'clearance.', 'manuscript.'],
    'message' => static function (string $type, array $d): ?array {
        $when = static fn($iso) => $iso ? date('M j, Y g:i A', strtotime((string) $iso) ?: time()) : 'a date to be announced';
        switch ($type) {
            case 'research.title_status_changed':
                return ['Title status: ' . ($d['status'] ?? 'updated'), 'Your research title "' . ($d['title'] ?? '') . '" is now ' . ($d['status'] ?? 'updated') . '.'];
            case 'research.adviser_assigned':
                return ['Adviser assigned', ($d['adviser'] ?? 'An adviser') . ' is now the adviser of group ' . ($d['group_number'] ?? '') . '.'];
            case 'submission.reviewed':
                if (($d['source'] ?? '') === 'chapter') {
                    return ['Chapter ' . ($d['chapter'] ?? '') . ' reviewed', 'Chapter ' . ($d['chapter'] ?? '') . ' (version ' . ($d['version'] ?? '') . ') is now ' . ($d['status'] ?? 'reviewed') . '.'];
                }
                return ['New adviser feedback', ($d['from'] ?? 'Your adviser') . ' left feedback on "' . ($d['update_title'] ?? 'your progress update') . '".'];
            case 'defense.scheduled':
                return [($d['defense_type'] ?? 'Defense') . ' scheduled', 'Your ' . ($d['defense_type'] ?? 'defense') . ' is set for ' . $when($d['starts_at'] ?? null) . ' at ' . ($d['venue'] ?? 'a venue to be announced') . '.'];
            case 'defense.rescheduled':
                return [($d['defense_type'] ?? 'Defense') . ' rescheduled', 'Your ' . ($d['defense_type'] ?? 'defense') . ' moved to ' . $when($d['starts_at'] ?? null) . ' at ' . ($d['venue'] ?? 'a venue to be announced') . '.'];
            case 'defense.status_changed':
                return ['Defense status: ' . ($d['status'] ?? 'updated'), 'Your ' . ($d['defense_type'] ?? 'defense') . ' is now ' . ($d['status'] ?? 'updated') . '.'];
            case 'clearance.status_changed':
                $what = ($d['kind'] ?? '') === 'payment' ? 'payment' : 'research clearance';
                return [ucfirst($what) . ': ' . ($d['status'] ?? 'updated'), 'Your ' . $what . ' (' . str_replace('_', ' ', (string) ($d['stage'] ?? '')) . ') is now ' . ($d['status'] ?? 'updated') . '.'];
            case 'manuscript.final_approved':
                return ['Final manuscript approved', 'Your final manuscript was approved' . (!empty($d['approved_by']) ? ' by ' . $d['approved_by'] : '') . '.'];
        }
        return ['Research update', 'There is an update on your research.'];
    },
];
