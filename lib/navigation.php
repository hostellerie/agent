<?php

/**
 * Structured navigation providers for Geeklog Agent.
 *
 * Navigation providers are intentionally separate from editorial content
 * providers. The owning plugin remains authoritative for visibility and
 * permission filtering. Agent MUST NOT rebuild hidden navigation from plugin
 * tables or expose raw ACL metadata.
 *
 * @package Agent
 */

function AGENT_getNavigationProviderCatalog()
{
    return array(
        'menu' => array(
            'label' => 'Menu',
            'family' => 'navigation',
            'type' => 'navigation',
            'default_name' => 'navigation'
        )
    );
}

function AGENT_navigationProviderAvailable($provider)
{
    global $_PLUGINS;

    $provider = strtolower(trim((string) $provider));
    if (!AGENT_isEnabled() || $provider !== 'menu') {
        return false;
    }

    if (!is_array($_PLUGINS) || !in_array('menu', $_PLUGINS, true)) {
        return false;
    }

    return function_exists('MENU_getResolvedTree')
        && function_exists('MENU_getResolvedTreeContractVersion');
}

function AGENT_getNavigationCapabilities($provider)
{
    if (!AGENT_navigationProviderAvailable($provider)) {
        return array();
    }

    return array('navigation.read', 'navigation.tree');
}

/**
 * Return a permission-aware navigation representation from the owning plugin.
 *
 * Menu already filters the tree for the current Geeklog visitor. Agent keeps
 * that result as-is, may reduce it later, but must never broaden it.
 *
 * @param string $provider Provider name (currently menu)
 * @param string $name Navigation/menu name
 * @return array|false
 */
function AGENT_getNavigationTree($provider, $name = '')
{
    $provider = strtolower(trim((string) $provider));
    if (!AGENT_navigationProviderAvailable($provider)) {
        return false;
    }

    $catalog = AGENT_getNavigationProviderCatalog();
    $definition = $catalog[$provider];
    $name = trim((string) $name);
    if ($name === '') {
        $name = $definition['default_name'];
    }

    if ($provider !== 'menu') {
        return false;
    }

    $nodes = MENU_getResolvedTree($name);
    if (!is_array($nodes)) {
        return false;
    }

    return array(
        'schema_version' => '1',
        'provider' => 'menu',
        'provider_family' => 'navigation',
        'type' => 'navigation',
        'name' => $name,
        'provider_contract_version' => (int) MENU_getResolvedTreeContractVersion(),
        'capabilities' => AGENT_getNavigationCapabilities('menu'),
        'nodes' => $nodes
    );
}

function AGENT_buildNavigationJson($provider, $name = '')
{
    if (!function_exists('AGENT_jsonEncode')) {
        return '';
    }

    $tree = AGENT_getNavigationTree($provider, $name);
    if (!is_array($tree)) {
        return '';
    }

    return AGENT_jsonEncode($tree);
}

function AGENT_getNavigationProviderStatus()
{
    global $_PLUGINS;

    $status = array();
    foreach (AGENT_getNavigationProviderCatalog() as $provider => $definition) {
        $installed = is_array($_PLUGINS) && in_array($provider, $_PLUGINS, true);
        $status[$provider] = array(
            'installed' => $installed,
            'available' => AGENT_navigationProviderAvailable($provider),
            'provider_family' => $definition['family'],
            'type' => $definition['type'],
            'capabilities' => AGENT_getNavigationCapabilities($provider)
        );
    }

    return $status;
}
