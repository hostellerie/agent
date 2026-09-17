# Agent Provider Integration Guide

This guide explains how Geeklog plugins should expose data and capabilities to Agent without coupling Agent to plugin tables or internal implementation details.

## Core rule

The owning plugin remains authoritative for:

- visibility;
- permissions;
- routing;
- validation;
- business rules;
- contract versioning.

Agent consumes the plugin's public/shared contract and may further restrict exposure, but it must never reconstruct hidden information from plugin SQL.

## Provider families

Agent should not assume every provider is editorial content.

Recommended families:

```text
content       addressable editorial/content resources
navigation    permission-filtered navigation structures
relationship contextual relationships and graph data
service       bounded plugin services and diagnostics
```

### Content providers

Prefer standard Geeklog interoperability contracts such as:

```text
PLG_getItemInfo()
plugin_getiteminfo_PLUGIN()
plugin_idtourl_PLUGIN()
plugin_dopluginsearch_PLUGIN()
```

The provider remains responsible for access checks. Agent should normalize only successful, authorized results.

### Navigation providers

Navigation providers expose structure rather than editorial content. They should not be forced into `PLG_getItemInfo()` when that would distort their model.

The first reference implementation is Menu.

## Menu integration

Menu 1.4.0 exposes a versioned, presentation-neutral and permission-aware tree through:

```php
MENU_getResolvedTree($name);
MENU_getResolvedTreeContractVersion();
```

Agent should feature-detect both functions.

Recommended availability check:

```php
function AGENT_menuNavigationAvailable()
{
    return function_exists('MENU_getResolvedTree')
        && function_exists('MENU_getResolvedTreeContractVersion')
        && (int) MENU_getResolvedTreeContractVersion() >= 1;
}
```

Agent should then request only the resolved tree needed for the current context, for example:

```php
$tree = MENU_getResolvedTree('navigation');
```

The returned tree is already filtered by Menu according to the active Geeklog request/user context.

### Security boundary

Agent must not:

- read Menu tables to discover hidden nodes;
- reconstruct elements excluded by `MENU_getResolvedTree()`;
- expose raw Menu group IDs, owner IDs or permission masks;
- infer write permission from tree visibility;
- expose arbitrary PHP callback execution from legacy Menu elements;
- cache one user's resolved tree and serve it to another permission context.

Agent may further restrict or omit nodes, but it must never broaden the result supplied by Menu.

### Capability names

Recommended read-only capabilities:

```text
navigation.read
navigation.tree
```

These capabilities mean only that the current context can retrieve a resolved navigation structure.

They do not imply:

```text
menu.create
menu.update
menu.delete
menu.element.move
```

Write actions belong to a future authenticated Agent action model.

### Representation envelope

A navigation result should keep its owning contract visible. A conceptual JSON envelope is:

```json
{
  "schema_version": "1",
  "provider": "menu",
  "provider_family": "navigation",
  "type": "navigation",
  "name": "navigation",
  "provider_contract_version": 1,
  "capabilities": [
    "navigation.read",
    "navigation.tree"
  ],
  "nodes": []
}
```

`provider_contract_version` is Menu's resolved-tree contract version, not Agent's global resource schema version.

Agent should preserve this distinction so consumers can reason about both layers independently.

### Handling future Menu fields

Within Menu resolved-tree contract v1:

- existing field meanings/types should remain stable;
- new fields may be added;
- Agent must ignore unknown additive fields safely;
- Agent must not hard-fail because Menu added an optional field.

Only a Menu contract-version change that Agent does not support should require a compatibility decision.

## Capability discovery

Agent's public capability endpoint should advertise a navigation provider only when all of the following are true:

1. Agent is enabled;
2. Menu is active in the current site;
3. the resolved-tree functions exist;
4. the contract version is supported;
5. the current context can obtain a meaningful resolved tree, or the provider intentionally advertises an empty but valid tree capability.

Recommended provider metadata:

```text
provider: menu
provider_family: navigation
type: navigation
provider_contract_version: 1
capabilities:
  - navigation.read
  - navigation.tree
representations:
  - json
```

A Markdown representation is optional and should be added only if it is useful for machine consumption.

## Caching

Permission-aware provider output must be cached only when the cache key contains enough context to prevent leakage.

For Menu this may include, depending on the final cache design:

- active site namespace;
- menu name;
- language;
- anonymous/authenticated state;
- relevant permission/group context;
- presentation-independent provider contract version.

Until Agent has a proven permission-aware cache key for navigation trees, it is safer not to share cached Menu trees across user contexts.

## Future write actions

When Agent eventually supports authenticated actions, Menu mutations should be narrow semantic actions such as:

```text
menu.create
menu.update
menu.element.create
menu.element.update
menu.element.move
menu.element.delete
menu.activate
menu.deactivate
```

Requirements:

- Menu remains the implementation owner;
- actions reuse Menu validation/business logic;
- `menu.admin` is checked independently at action time;
- hierarchy-cycle and destination validation remain authoritative in Menu;
- cache invalidation remains owned by Menu;
- mutations are auditable;
- no generic SQL/PHP/filesystem execution endpoint is introduced.

## General plugin checklist

Before adding a plugin provider to Agent, verify:

- the owning plugin exposes a documented shared contract;
- the contract has explicit permission behavior;
- Agent does not need direct SQL to use it;
- a contract/schema version can be detected when needed;
- capabilities describe implemented behavior only;
- public read capabilities are distinct from authenticated actions;
- multisite state remains scoped to the active site;
- provider output can be tested for leakage of drafts/private/inaccessible data;
- unknown additive fields can be ignored safely;
- protocol adapters consume Agent's provider model rather than calling the plugin directly.
