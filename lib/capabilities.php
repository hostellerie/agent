<?php

/**
 * Public capability discovery for Geeklog Agent.
 *
 * This surface intentionally describes only read-only capabilities that are
 * actually available in the current site context. It does not expose runtime
 * implementation details, credentials or future write actions.
 *
 * @package Agent
 */

function AGENT_getPublicCapabilitiesData()
{
    global $_CONF;

    if (!AGENT_isEnabled()) {
        return array();
    }

    $providers = array();
    $siteCapabilities = array();

    foreach (AGENT_getEnabledProviders() as $provider) {
        if (!AGENT_providerAvailable($provider)) {
            continue;
        }

        $catalog = AGENT_getProviderCatalog();
        $definition = isset($catalog[$provider]) ? $catalog[$provider] : array();
        $capabilities = AGENT_getProviderCapabilities($provider);

        foreach ($capabilities as $capability) {
            if (!in_array($capability, $siteCapabilities, true)) {
                $siteCapabilities[] = $capability;
            }
        }

        $providers[$provider] = array(
            'provider_family' => 'content',
            'type' => isset($definition['resource_type']) ? $definition['resource_type'] : '',
            'label' => isset($definition['label']) ? $definition['label'] : $provider,
            'capabilities' => array_values($capabilities),
            'representations' => array('markdown', 'json')
        );
    }

    if (function_exists('AGENT_getNavigationProviderCatalog')) {
        foreach (AGENT_getNavigationProviderCatalog() as $provider => $definition) {
            if (!AGENT_navigationProviderAvailable($provider)) {
                continue;
            }

            $capabilities = AGENT_getNavigationCapabilities($provider);
            foreach ($capabilities as $capability) {
                if (!in_array($capability, $siteCapabilities, true)) {
                    $siteCapabilities[] = $capability;
                }
            }

            $providers[$provider] = array(
                'provider_family' => 'navigation',
                'type' => isset($definition['type']) ? $definition['type'] : 'navigation',
                'label' => isset($definition['label']) ? $definition['label'] : $provider,
                'capabilities' => array_values($capabilities),
                'representations' => array('json')
            );
        }
    }

    $hubCapabilities = function_exists('AGENT_getHubCapabilities')
        ? AGENT_getHubCapabilities()
        : array();
    foreach ($hubCapabilities as $capability) {
        if (!in_array($capability, $siteCapabilities, true)) {
            $siteCapabilities[] = $capability;
        }
    }

    sort($siteCapabilities);

    $data = array(
        'schema_version' => '1',
        'mode' => 'public-read-only',
        'capabilities' => $siteCapabilities,
        'representations' => array(
            'discovery' => 'llms.txt',
            'resource' => array('markdown', 'json'),
            'collection' => array('json'),
            'navigation' => array('json')
        ),
        'providers' => $providers
    );

    if (!empty($hubCapabilities)) {
        sort($hubCapabilities);
        $data['integrations'] = array(
            'hub' => array(
                'capabilities' => array_values($hubCapabilities)
            )
        );
    }

    if (!empty($_CONF['site_url'])) {
        $data['canonical_site'] = rtrim((string) $_CONF['site_url'], '/') . '/';
    }

    return $data;
}

function AGENT_buildCapabilitiesJson()
{
    if (!function_exists('AGENT_jsonEncode')) {
        return '';
    }

    $data = AGENT_getPublicCapabilitiesData();
    if (empty($data)) {
        return '';
    }

    return AGENT_jsonEncode($data);
}
