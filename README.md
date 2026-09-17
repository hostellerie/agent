# Agent for Geeklog

Agent is the provider-neutral machine access layer for Geeklog.

It exposes Geeklog content, structured capabilities and future authorized actions to machine consumers without making Agent a second content database and without coupling Geeklog plugins to ChatGPT, Claude, Gemini, MCP or another provider/protocol.

## Architecture

```text
Plugin owner = data, permissions and business logic
Hub          = context / relationships
Agent        = machine access layer
Connector    = client / provider adapter
```

## Current implementation

The `develop-1.0` branch has progressed well beyond the initial installable 0.1.0 skeleton.

Implemented foundations now include:

- Geeklog autoinstall metadata;
- `agent.admin` permission and `Agent Admin` group;
- Configuration Manager integration;
- runtime feature detection for Geeklog APIs;
- active-site-derived namespace/cache-path helpers;
- administration/status diagnostics;
- static `plugin.json` metadata;
- PHP 5.6-compatible source policy with compatibility checks through PHP 8.3;
- provider-neutral normalized resource schema v1;
- Stories and Static Pages providers;
- permission-aware retrieval through `PLG_getItemInfo()`;
- Markdown resource output;
- JSON resource and collection output;
- autonomous machine-discovery generation;
- public capability discovery;
- HTML machine-discovery integration;
- optional Hub service discovery/invocation through `PLG_invokeService()`.

The next architectural step is to generalize providers beyond editorial content. Menu 1.4.0 is the reference case for a structured **navigation provider** using its versioned, permission-aware `MENU_getResolvedTree()` contract.

## Provider families

Agent should support more than content providers:

```text
content       addressable editorial/content resources
navigation    structured permission-filtered navigation
relationship contextual graph/relationship information
service       bounded plugin services/capabilities
```

The owning plugin remains authoritative for visibility, permissions and business rules. Agent may further restrict results but must never reconstruct hidden data from plugin tables.

See [docs/provider-integration-guide.md](docs/provider-integration-guide.md) for integration rules and the Menu reference design.

## Compatibility target

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.3
- mono-site and multisite
- shared plugin files with site-scoped persisted state
- no Geeklog Core modification required for the read-only 1.0 target

## Configuration ownership

Agent stores only Agent behavior/editorial settings in Geeklog Configuration Manager. Site content, navigation, topics, URLs, hits, dates and relationships remain owned by Geeklog Core or the relevant plugin.

Agent derives site identity from the already-selected Geeklog runtime context (`$_CONF`, table prefix). It does not maintain a hostname registry or inspect sibling sites.

See [ROADMAP.md](ROADMAP.md) for the implementation status and remaining work.

## Design principle

> Plugins expose shared data and capabilities. Hub interprets relationships. Agent exposes machine-readable access. Connectors adapt that access to clients.
