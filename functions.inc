<?php

/**
 * Agent for Geeklog plugin API callbacks and foundation helpers.
 *
 * Agent deliberately contains no content-provider SQL and no external
 * provider/client dependency. Content access goes through Geeklog contracts
 * and is normalized before any output adapter consumes it.
 *
 * @package Agent
 */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file can not be used on its own.');
}

global $_CONF, $_AGENT_CONF;

$agent_path = $_CONF['path'] . 'plugins/agent/';
require_once $agent_path . 'config.php';
require_once $agent_path . 'lib/text.php';
require_once $agent_path . 'lib/compat.php';
require_once $agent_path . 'lib/resource.php';
require_once $agent_path . 'lib/providers.php';
require_once $agent_path . 'lib/discovery.php';
require_once $agent_path . 'lib/markdown.php';
require_once $agent_path . 'lib/json.php';
require_once $agent_path . 'lib/navigation.php';
require_once $agent_path . 'lib/hub.php';
require_once $agent_path . 'lib/capabilities.php';
require_once $agent_path . 'lib/html-discovery.php';

$agent_langfile = $agent_path . 'language/' . $_CONF['language'] . '.php';
if (file_exists($agent_langfile)) {
    require_once $agent_langfile;
} else {
    require_once $agent_path . 'language/english.php';
}

require_once $_CONF['path_system'] . 'classes/config.class.php';
$agent_config = config::get_instance();
$_AGENT_CONF = $agent_config->get_config('agent');

function plugin_cclabel_agent()
{
    global $_CONF, $LANG_AGENT;

    if (!SEC_hasRights('agent.admin')) {
        return array();
    }

    return array(
        $LANG_AGENT['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/agent/index.php',
        ''
    );
}

function plugin_getadminoption_agent()
{
    global $_CONF, $LANG_AGENT;

    if (!SEC_hasRights('agent.admin')) {
        return null;
    }

    return array(
        $LANG_AGENT['plugin_name'],
        $_CONF['site_admin_url'] . '/plugins/agent/index.php',
        0
    );
}

function plugin_getheadercode_agent()
{
    if (!function_exists('AGENT_buildHtmlDiscoveryHead')) {
        return '';
    }

    return AGENT_buildHtmlDiscoveryHead();
}

function plugin_chkVersion_agent()
{
    return AGENT_VERSION;
}

function plugin_autouninstall_agent()
{
    return array(
        'tables'     => array(),
        'groups'     => array('Agent Admin'),
        'features'   => array('agent.admin'),
        'php_blocks' => array()
    );
}

function plugin_upgrade_agent()
{
    global $_CONF, $_AGENT_CONF;

    require_once $_CONF['path'] . 'plugins/agent/autoinstall.php';

    if (!plugin_compatible_with_this_version_agent('agent')) {
        return 3002;
    }

    require_once $_CONF['path'] . 'plugins/agent/install_defaults.php';
    if (!AGENT_syncConfig()) {
        return false;
    }

    $_AGENT_CONF = config::get_instance()->get_config('agent');

    return true;
}

function AGENT_getRuntimeCapabilities()
{
    $capabilities = array();

    $capabilities['geeklog_version'] = defined('VERSION') ? VERSION : 'unknown';
    $capabilities['php_version'] = PHP_VERSION;
    $capabilities['plg_getiteminfo'] = function_exists('PLG_getItemInfo');
    $capabilities['plg_invoke_service'] = function_exists('PLG_invokeService');
    $capabilities['plg_ws_enabled'] = function_exists('PLG_wsEnabled');
    $capabilities['plg_item_saved'] = function_exists('PLG_itemSaved');
    $capabilities['plg_item_deleted'] = function_exists('PLG_itemDeleted');
    $capabilities['configuration_manager'] = class_exists('config');
    $capabilities['normalized_resource_model'] = function_exists('AGENT_normalizeResource');
    $capabilities['provider_layer'] = function_exists('AGENT_getProviderCatalog');
    $capabilities['navigation_provider_layer'] = function_exists('AGENT_getNavigationProviderCatalog');
    $capabilities['public_llms_discovery'] = function_exists('AGENT_buildLlmsText');
    $capabilities['public_markdown_resources'] = function_exists('AGENT_buildResourceMarkdown');
    $capabilities['public_json_resources'] = function_exists('AGENT_buildResourceJson');
    $capabilities['public_json_collections'] = function_exists('AGENT_buildCollectionJson');
    $capabilities['public_json_navigation'] = function_exists('AGENT_buildNavigationJson');
    $capabilities['public_capability_discovery'] = function_exists('AGENT_buildCapabilitiesJson');
    $capabilities['html_machine_discovery'] = function_exists('AGENT_buildHtmlDiscoveryHead');
    $capabilities['hub_integration'] = function_exists('AGENT_getHubCapabilities');
    $capabilities['hub_installed'] = function_exists('AGENT_hubInstalled') ? AGENT_hubInstalled() : false;

    return $capabilities;
}

function AGENT_getSiteNamespace()
{
    global $_CONF, $_DB_table_prefix;

    $parts = array(
        isset($_CONF['path_data']) ? $_CONF['path_data'] : '',
        isset($_CONF['site_url']) ? $_CONF['site_url'] : '',
        isset($_DB_table_prefix) ? $_DB_table_prefix : ''
    );

    return 'agent-' . substr(hash('sha256', implode('|', $parts)), 0, 20);
}

function AGENT_getCachePath()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], '/\\') : '';
    if ($base === '') {
        return '';
    }

    return $base . DIRECTORY_SEPARATOR . 'agent' . DIRECTORY_SEPARATOR
        . 'cache' . DIRECTORY_SEPARATOR . AGENT_getSiteNamespace();
}

function AGENT_getConfig($key, $default)
{
    global $_AGENT_CONF;

    if (is_array($_AGENT_CONF) && array_key_exists($key, $_AGENT_CONF)) {
        return $_AGENT_CONF[$key];
    }

    return $default;
}

function AGENT_isEnabled()
{
    return (int) AGENT_getConfig('enabled', 1) === 1;
}
