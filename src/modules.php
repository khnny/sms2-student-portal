<?php
/**
 * Module registry: the one contract every SMS 2 module follows to appear in the portal.
 *
 * The portal is the student's single front door. Each SMS 2 module (CRAD,
 * Registrar, Payment, Accreditation, ...) is one file in src/modules/<key>.php
 * that returns an array with these keys:
 *
 *   key         string   short id, same as the file name, e.g. 'crad'
 *   label       string   name shown to students, e.g. 'Research'
 *   icon        string   Font Awesome class for the sidebar group
 *   enabled     bool     false = hidden everywhere (use for modules not ready yet)
 *   api_prefix  string   where its endpoints live in the SMS 2 API v1, e.g. 'me/research'
 *   nav         array    sidebar entries: [href, label, icon] or ['group' => .., 'icon' => .., 'items' => [...]]
 *   actions     array    student actions: key => [label, icon, path?]
 *                         'path' = SMS 2 page still used for it (opened by open.php);
 *                         no 'path' = the action is done fully inside the portal
 *   webhooks    array    event type prefixes it owns, e.g. ['research.', 'defense.']
 *   message     callable fn(string $type, array $data): ?array{0: title, 1: body}
 *                         turns one of its webhook events into a notification
 *
 * Data always comes from the SMS 2 API with the student's token (portal_api),
 * so the module's office keeps reviewing in SMS 2 and stays the source of truth.
 * To add a module: copy src/modules/registrar.php, fill it in, set enabled to true.
 */
declare(strict_types=1);

/** @return array<string, array> enabled modules, in file-name order (crad first) */
function portal_modules(bool $includeDisabled = false): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        $files = glob(__DIR__ . '/modules/*.php') ?: [];
        sort($files);
        // CRAD (Research) is the main module, so it always comes first.
        usort($files, static fn(string $a, string $b): int => (int) (basename($b) === 'crad.php') <=> (int) (basename($a) === 'crad.php'));
        foreach ($files as $file) {
            $m = require $file;
            if (!is_array($m) || empty($m['key'])) {
                continue;
            }
            $all[(string) $m['key']] = $m + ['label' => $m['key'], 'icon' => 'fa-cube', 'enabled' => false, 'api_prefix' => '', 'nav' => [], 'actions' => [], 'webhooks' => [], 'message' => null];
        }
    }
    return $includeDisabled ? $all : array_filter($all, static fn(array $m): bool => !empty($m['enabled']));
}

function portal_module(string $key): ?array
{
    return portal_modules()[$key] ?? null;
}

/** All student actions of enabled modules, keyed by action key (step.php?do=<key>). */
function portal_module_actions(): array
{
    $actions = [];
    foreach (portal_modules() as $m) {
        foreach ($m['actions'] as $k => $a) {
            $actions[$k] = $a + ['module' => $m['key']];
        }
    }
    return $actions;
}

/** The module that owns a webhook event type, or null. */
function portal_module_for_event(string $type): ?array
{
    foreach (portal_modules() as $m) {
        foreach ($m['webhooks'] as $prefix) {
            if (str_starts_with($type, (string) $prefix)) {
                return $m;
            }
        }
    }
    return null;
}

/** Sidebar entries of every enabled module, in order. */
function portal_module_nav(): array
{
    $nav = [];
    foreach (portal_modules() as $m) {
        foreach ($m['nav'] as $item) {
            $nav[] = $item;
        }
    }
    return $nav;
}
