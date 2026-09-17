<?php

/**
 * Public read-only navigation JSON endpoint for Geeklog Agent.
 *
 * Query parameters:
 * - provider: navigation provider name (currently menu)
 * - name: provider-owned navigation/menu name (defaults to navigation)
 *
 * @package Agent
 */

require_once '../lib-common.php';

if (!function_exists('AGENT_buildNavigationJson')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=UTF-8');
    echo "{\"error\":\"Agent navigation is unavailable.\"}\n";
    exit;
}

$provider = isset($_GET['provider']) ? COM_applyFilter($_GET['provider']) : 'menu';
$name = isset($_GET['name']) ? COM_applyFilter($_GET['name']) : 'navigation';

$output = AGENT_buildNavigationJson($provider, $name);
if ($output === '') {
    http_response_code(404);
    header('Content-Type: application/json; charset=UTF-8');
    echo "{\"error\":\"Navigation not available.\"}\n";
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo $output;
