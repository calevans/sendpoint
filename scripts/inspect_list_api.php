<?php
declare(strict_types=1);

// Simple script to inspect the API list response from CLI by bootstrapping the app
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../bootstrap.php';

use EICC\ZillowScraper\Http\Request;

$limit = (int)($argv[1] ?? 12);
$offset = (int)($argv[2] ?? 0);
$inspectId = isset($argv[3]) ? (int)$argv[3] : null;

// Build a Request similar to how the router would
$queryParams = ['limit' => $limit, 'offset' => $offset];
$request = new Request('GET', '/api/properties', [], [], $queryParams, []);

/** @var \EICC\ZillowScraper\Controllers\PropertyController $pc */
$pc = $container->get('property_controller');
$response = $pc->listProperties($request);

// Print entire response
echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;

// If inspectId provided, print the matching item
if ($inspectId) {
    $items = $response['data'] ?? [];
    $found = null;
    foreach ($items as $item) {
        if (isset($item['id']) && (int)$item['id'] === $inspectId) {
            $found = $item;
            break;
        }
    }

    echo "\n--- Item for property id {$inspectId} ---\n";
    if ($found) {
        echo json_encode($found, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Property id {$inspectId} not present on this page.\n";
    }
}
