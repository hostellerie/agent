<?php

/**
 * Navigation provider contract checks.
 */

$root = dirname(__DIR__);
$navigation = file_get_contents($root . '/lib/navigation.php');
$capabilities = file_get_contents($root . '/lib/capabilities.php');
$endpoint = file_get_contents($root . '/public_html/navigation-json.php');

$checks = array(
    'navigation provider catalog' => strpos($navigation, 'AGENT_getNavigationProviderCatalog') !== false,
    'menu resolved tree feature detection' => strpos($navigation, "function_exists('MENU_getResolvedTree')") !== false,
    'menu contract version detection' => strpos($navigation, "function_exists('MENU_getResolvedTreeContractVersion')") !== false,
    'navigation read capability' => strpos($navigation, "'navigation.read'") !== false,
    'navigation tree capability' => strpos($navigation, "'navigation.tree'") !== false,
    'provider contract version preserved' => strpos($navigation, 'MENU_getResolvedTreeContractVersion()') !== false,
    'permission filtered tree consumed' => strpos($navigation, 'MENU_getResolvedTree($name)') !== false,
    'no menu table SQL in navigation adapter' => stripos($navigation, 'DB_query') === false && stripos($navigation, 'menu_elements') === false,
    'capability discovery includes navigation family' => strpos($capabilities, "'provider_family' => 'navigation'") !== false,
    'capability discovery includes navigation representation' => strpos($capabilities, "'navigation' => array('json')") !== false,
    'endpoint prevents shared public caching' => strpos($endpoint, 'Cache-Control: private, no-store') !== false
);

$failed = 0;
foreach ($checks as $label => $ok) {
    if (!$ok) {
        echo 'FAIL: ' . $label . PHP_EOL;
        $failed++;
    }
}

if ($failed > 0) {
    exit(1);
}

echo "Navigation provider contract OK\n";
