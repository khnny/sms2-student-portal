<?php
/**
 * Payments module (not live yet). Tuition and fee balances and payment uploads. Endpoints go under me/payments in SMS 2 API v1.
 * Contract: see src/core/modules.php. To turn it on: add its endpoints and webhook
 * events in SMS 2, fill in nav and actions below, then set enabled to true.
 */
declare(strict_types=1);

return [
    'key' => 'payment',
    'label' => 'Payments',
    'icon' => 'fa-credit-card',
    'enabled' => false,
    'api_prefix' => 'me/payments',
    'nav' => [
        // ['payment', 'Payments', 'fa-credit-card'],
    ],
    'actions' => [],
    'webhooks' => ['payment.'],
    'message' => static fn(string $type, array $d): ?array => ['Payments update', (string) ($d['message'] ?? 'There is an update from Payments.')],
];
