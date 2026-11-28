<?php
/**
 * Registrar module (not live yet). Enrollment, grades and document requests. Endpoints go under me/registrar in SMS 2 API v1.
 * Contract: see src/core/modules.php. To turn it on: add its endpoints and webhook
 * events in SMS 2, fill in nav and actions below, then set enabled to true.
 */
declare(strict_types=1);

return [
    'key' => 'registrar',
    'label' => 'Registrar',
    'icon' => 'fa-building-columns',
    'enabled' => false,
    'api_prefix' => 'me/registrar',
    'nav' => [
        // ['registrar', 'Registrar', 'fa-building-columns'],
    ],
    'actions' => [],
    'webhooks' => ['registrar.'],
    'message' => static fn(string $type, array $d): ?array => ['Registrar update', (string) ($d['message'] ?? 'There is an update from Registrar.')],
];
