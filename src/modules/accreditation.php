<?php
/**
 * Accreditation module (not live yet). Requirements students submit for accreditation. Endpoints go under me/accreditation in SMS 2 API v1.
 * Contract: see src/modules.php. To turn it on: add its endpoints and webhook
 * events in SMS 2, fill in nav and actions below, then set enabled to true.
 */
declare(strict_types=1);

return [
    'key' => 'accreditation',
    'label' => 'Accreditation',
    'icon' => 'fa-award',
    'enabled' => false,
    'api_prefix' => 'me/accreditation',
    'nav' => [
        // ['accreditation', 'Accreditation', 'fa-award'],
    ],
    'actions' => [],
    'webhooks' => ['accreditation.'],
    'message' => static fn(string $type, array $d): ?array => ['Accreditation update', (string) ($d['message'] ?? 'There is an update from Accreditation.')],
];
