# Agent for Geeklog — Development Roadmap

## Vision

Agent is the **provider-neutral machine access layer** for Geeklog.

It exposes site content, structured navigation, service capabilities and future authorized actions to AI assistants, LLMs, agents, MCP clients, automation tools and other machine consumers without requiring those consumers to know Geeklog tables, plugin internals or theme HTML.

Agent does **not** own content and does **not** replace Hub.

```text
Hub       = context / relationships
Agent     = machine access layer
Connector = client/provider adapter
```

Agent consumes shared Geeklog contracts and delegates business logic, visibility and permissions to the owning Core feature or plugin.

Agent's internal model must remain stable and provider-neutral. MCP, ChatGPT, REST/OpenAPI and future protocols are adapters over that model and must not define it.

---

## Current implementation status

The code is ahead of the original milestone numbering. The roadmap therefore distinguishes **implemented**, **partial** and **planned** work instead of implying that every numbered milestone is still future work.

### Implemented now

- installable Geeklog plugin foundation;
- `agent.admin` permission and administration UI;
- Configuration Manager integration;
- PHP 5.6-compatible runtime style;
- multisite-safe site namespace and cache-path foundation;
- automated compatibility/build workflows and installable `dist/` archive;
- provider-neutral resource schema v1 through `AGENT_RESOURCE_SCHEMA_VERSION`;
- resource normalization and stable `provider:type:id` identity;
- provider layer using Geeklog interoperability contracts;
- Stories provider;
- Static Pages provider;
- permission-aware item retrieval through `PLG_getItemInfo()`;
- permission-aware provider collections where supported;
- public Markdown resource representation;
- public JSON resource representation;
- public JSON collection representation;
- autonomous machine-discovery output generated from active site context;
- public capability discovery with schema version 1;
- provider capability reporting (`content.read`, `content.collection` where supported);
- HTML machine-discovery integration;
- optional Hub service discovery/invocation through `PLG_invokeService()`;
- Hub capability reporting for compatible read-only services.

### Partial / needs hardening

- `/llms.txt` deployment/routing convention: generator exists, deployment integration must be validated on real sites;
- provider collections: `limit` and `modified-desc` are implemented, broader filter/order contract remains incomplete;
- capability discovery: implemented for current content providers and Hub, but provider families beyond content are not yet generalized;
- Hub integration: service detection/invocation exists, but richer normalized Hub result surfaces remain to be completed;
- multisite hardening: site namespace exists, full multi-site validation matrix remains required;
- cache/performance layer: cache path exists, complete lifecycle/ETag/invalidation/rate-limit work remains;
- search/retrieval aggregation remains incomplete;
- broader plugin coverage remains incomplete.

### Planned next architectural step

Generalize Agent providers beyond editorial content so that plugins can expose **structured capabilities** without pretending to be `PLG_getItemInfo()` content providers.

The first reference case is Menu 1.4.0, which now exposes a versioned, permission-aware `MENU_getResolvedTree()` contract.

---

## Compatibility target

Initial stable target:

- Geeklog **2.1.1 through 2.2.2**;
- PHP **5.6 through 8.3**;
- MySQL / MariaDB supported by the target Geeklog versions;
- mono-site installations;
- multisite installations with shared plugin files and isolated site state;
- no Geeklog Core modification required for 1.0.

Implementation must use the common safe PHP 5.6–8.3 subset and feature-detect newer Geeklog APIs.

---

## Architectural rules

1. **Agent is provider-neutral.** No ChatGPT-, Claude-, Gemini- or MCP-specific assumptions in the core data model.
2. **The owning plugin remains authoritative.** Agent does not duplicate another plugin's tables, ACL, routing or business logic.
3. **Use shared Geeklog contracts first.** Prefer Plugin API, Item Info, services, versioned plugin contracts and capability discovery over direct SQL.
4. **Direct SQL is a compatibility fallback only.** Legacy adapters may be used for Core/old plugins when no suitable API exists, and should be isolated for later removal.
5. **Hub owns relationships.** Agent consumes Hub services for context, related items, dependency information, integrity reports and suggestions.
6. **Connector is an adapter.** Client-specific schemas belong outside Agent core.
7. **Public discovery is separate from authenticated actions.** `llms.txt` and public resources must not imply write authorization.
8. **Permissions are evaluated before exposure.** Draft/private/inaccessible content or navigation must not leak through lists, search, trees, rankings or machine endpoints.
9. **Do not broaden visibility.** If an owning plugin returns a permission-filtered result, Agent may further restrict it but must never reconstruct or enlarge the hidden set from plugin tables.
10. **Multisite context is mandatory.** Configuration, cache, credentials and audit data must remain site-scoped.
11. **One normalized representation, many adapters.** Markdown, JSON, `llms.txt`, MCP and future protocols should reuse stable Agent models.
12. **Resources, Capabilities and Actions are distinct.** Resources are readable objects, Capabilities describe what a provider/context can do, and Actions are operations the current caller is authorized to trigger.
13. **Provider families are allowed.** Not every provider is an editorial content provider. Navigation, relationship and service providers may expose their own versioned representations through Agent.
14. **Protocol adapters must not define Agent core.** MCP, ChatGPT, REST/OpenAPI and future protocol details belong above Agent's normalized models.
15. **Visible configuration must match implemented behavior.** Future features must not appear as active controls before they exist, except read-only diagnostics.
16. **Autonomy is a release requirement.** Agent 1.0 must not require legacy LLM scripts, hostname registries or per-site LLM text files.

---

# 0.1.0 — Installable foundation — IMPLEMENTED

Implemented foundation includes:

- Geeklog autoinstall/uninstall support;
- plugin metadata and `plugin.json`;
- `agent.admin` and Agent Admin group;
- Configuration Manager integration;
- administration/status diagnostics;
- PHP 5.6-compatible implementation style;
- capability detection for Geeklog APIs;
- multisite-safe configuration loading;
- site-scoped namespace/cache-path foundation;
- automated compatibility/build workflows;
- installable archive in `dist/`;
- archive rule excluding packaged entries whose names begin with `.`;
- modern document rendering and `.thtml` administration presentation.

Remaining foundation work belongs to compatibility/security hardening rather than feature creation.

---

# 0.2.0 — Normalized resource model — IMPLEMENTED BASELINE

`lib/resource.php` provides schema version 1 and normalized fields including:

```text
schema_version
provider
id
type
subtype
title
url
canonical_url
excerpt
content
language
created
modified
uid
author
image
category
topic
hits
visibility
capabilities
```

Current guarantees:

- explicit provider ownership;
- stable normalized identity;
- provider data cannot override Agent-owned identity fields;
- canonical URL fallback;
- safe handling of missing optional fields;
- normalized capability lists;
- no raw database row exposure through the normalized model.

Future resource-schema changes should remain additive within schema v1 where practical. Breaking semantic/type changes require a new schema version.

---

# 0.3.0 — Provider layer — IMPLEMENTED FOR CONTENT, EXPANSION ACTIVE

Current content providers:

```text
stories
staticpages
```

Current provider behavior:

- availability detection;
- single-resource retrieval;
- collection retrieval where supported;
- `PLG_getItemInfo()` as permission gate;
- normalized provider capabilities;
- editorial content/excerpt compatibility enrichment only after authorization;
- bounded collection size;
- `modified-desc` sorting.

## Provider families

Agent must now generalize the provider registry so it does not assume every useful plugin contract maps to `PLG_getItemInfo()`.

Target families:

```text
content       editorial/addressable resources
navigation    structured navigation/trees
relationship contextual graph/relationships
service       bounded plugin services/capabilities
```

Each family may use a different owning-plugin contract while sharing common discovery, permission and adapter rules.

### Menu reference integration

Menu must be integrated as a **navigation provider**, not forced into the content model.

Preferred source contract:

```text
MENU_getResolvedTree($name)
MENU_getResolvedTreeContractVersion()
```

Expected Agent capabilities:

```text
navigation.read
navigation.tree
```

Rules:

- feature-detect the Menu functions;
- require a supported resolved-tree contract version;
- consume the tree exactly as filtered by Menu for the current request/user context;
- never query `menu`, `menu_elements` or Menu ACL tables to reconstruct hidden nodes;
- never expose raw Menu group/owner/permission internals;
- preserve Menu's contract version as provider metadata;
- treat future Menu fields additively and ignore unknown fields;
- keep navigation representation distinct from the editorial resource schema unless a clean generic envelope is useful;
- do not infer write authorization from `navigation.read` or `navigation.tree`.

See `docs/provider-integration-guide.md`.

---

# 0.4.0 — Autonomous public discovery — IMPLEMENTED BASELINE

`AGENT_buildLlmsText()` already generates machine-discovery output from the active Geeklog context and enabled providers.

Implemented:

- no hostname switch registry;
- no runtime dependency on legacy per-site LLM text files;
- site description fallback;
- recent resources from enabled providers;
- canonical resource links;
- links to Markdown/JSON representations;
- capability endpoint discovery;
- graceful empty-provider behavior.

Remaining:

- validate canonical `/llms.txt` deployment/routing on supported installations;
- richer curated/featured semantics;
- broader provider families such as navigation;
- cache lifecycle/invalidation.

---

# 0.5.0 — Markdown resources — IMPLEMENTED BASELINE

Agent already exposes clean Markdown resource representations for supported content providers.

Continue validating:

- no theme chrome;
- no inaccessible/private content;
- stable headings and metadata;
- UTF-8 behavior;
- safe caching/public headers.

Navigation providers may use JSON first; a Markdown tree representation should be added only if it improves machine consumption without duplicating HTML navigation.

---

# 0.6.0 — JSON resources and collections — IMPLEMENTED BASELINE

Implemented public surfaces include individual resources and provider collections.

Current collection support includes bounded `limit` and `modified-desc` ordering where the provider supports collections.

Remaining:

- broader collection filters (`since`, `until`, `ids`, taxonomy, author, subtype);
- pagination conventions;
- additional ordering (`created-desc`, `hits-desc`) where meaningful;
- navigation JSON surface for Menu and future structured providers.

---

# 0.7.0 — Capability discovery — IMPLEMENTED BASELINE

Agent already exposes public read-only capability data with:

```text
schema_version
mode
capabilities
representations
providers
integrations
canonical_site
```

Current content capabilities include:

```text
content.read
content.collection
```

Hub capabilities are added when compatible services are detected.

Next capability-discovery work:

- support provider family/type metadata;
- advertise navigation providers such as Menu;
- expose `navigation.read` and `navigation.tree` only when Menu is active and its resolved-tree contract is available;
- preserve owning-provider schema/contract version metadata;
- keep public capability discovery read-only;
- prepare the same capability model for future MCP/OpenAPI/Connector adapters.

---

# 0.8.0 — Hub integration — IMPLEMENTED SERVICE FOUNDATION

Agent already detects and invokes compatible Hub services through normal Geeklog service APIs without reading Hub tables.

Current mapped capabilities:

```text
hub.context.read
hub.related.read
hub.affected.read
hub.integrity.read
hub.suggestions.read
```

Remaining:

- normalize useful Hub service outputs for machine consumers;
- expose richer context bundles where appropriate;
- validate permission/error behavior across supported Geeklog versions.

---

# 0.9.0 — Search and retrieval — PLANNED / PARTIAL

Goal: aggregate useful machine retrieval without raw database access.

Planned:

- plugin/Core search APIs where practical;
- normalized aggregated search results;
- permission enforcement per provider;
- bounded counts;
- stable identities and canonical URLs;
- snippets by default, full content separately.

Potential capabilities:

```text
content.search
content.recent
content.popular
content.featured
```

Semantic/vector search is not required for 1.0.

---

# 0.10.0 — Multisite hardening — FOUNDATION IMPLEMENTED, VALIDATION REQUIRED

Already present:

- active-site Configuration Manager state;
- site namespace derived from site-specific values;
- site-scoped cache path foundation;
- active plugin detection.

Still validate:

- different `$_TABLES` mappings;
- permissions per site;
- cache isolation;
- shared-file staggered upgrades;
- no cross-site credential/audit leakage;
- provider availability differing by site;
- Menu/navigation results differing correctly by site and user context.

The active Geeklog site context is authoritative.

---

# 0.11.0 — Performance, cache and observability — PLANNED / PARTIAL

Current foundation:

- bounded provider collection sizes;
- site-scoped cache path helper;
- public short-lived cache headers on current resource endpoints.

Remaining:

- actual per-site cache lifecycle;
- ETag/Last-Modified where safe;
- invalidation after lifecycle events;
- avoid N+1 provider work;
- request/error logging;
- optional rate-limit hooks;
- safe administrator diagnostics;
- no sensitive details in public errors.

---

# 0.12.0 — Compatibility and security audit — REQUIRED BEFORE 1.0

Test matrix:

```text
Geeklog 2.1.1 + PHP 5.6
Geeklog 2.1.1 + supported intermediate PHP where practical
Geeklog 2.2.2 + PHP 8.1
Geeklog 2.2.2 + PHP 8.3
```

Validate mono-site and multisite scenarios.

Security checks:

- permission enforcement on every provider path;
- no draft/private/inaccessible leakage;
- no hidden navigation leakage;
- no raw ACL/group/owner metadata from Menu;
- no direct arbitrary SQL endpoint;
- no arbitrary PHP/shell/filesystem execution;
- output encoding by format;
- bounded requests;
- no implicit write actions;
- safe errors;
- multisite isolation.

---

# 1.0.0 — Stable autonomous read-only Agent

1.0 is reached when Agent can reliably:

- install on Geeklog 2.1.1–2.2.2;
- run on PHP 5.6–8.3;
- operate mono-site and multisite;
- generate useful machine-discovery output autonomously;
- expose clean Markdown resources;
- expose structured JSON resources and collections;
- expose public capability discovery;
- use native plugin contracts where available;
- consume Hub services without duplicating Hub;
- consume at least one non-content structured provider (Menu is the preferred reference case);
- enforce provider-owned permissions consistently;
- avoid direct plugin SQL where a shared contract exists;
- cache safely per site;
- ship tests and installation/upgrade documentation.

No authenticated write capability is required for 1.0.

---

# Post-1.0 direction

## 1.1 — Broader plugin provider coverage

Target modernized plugins such as:

- Videos;
- Documents;
- Maps;
- MediaGallery;
- Forum where content exposure is useful;
- Store where public product/resource exposure is appropriate.

Prefer improvements in the owning plugin's shared interoperability contract instead of permanent Agent-specific SQL adapters.

Menu is intentionally moved **before** this generic post-1.0 list because it is the reference case for a non-content structured provider and should help validate the provider-family architecture before 1.0.

## 1.2 — Rich Hub/context integration

- richer related-content context;
- affected-resource reporting;
- integrity diagnostics;
- machine-readable editorial suggestions;
- optional context bundles for agents.

## 1.3 — Retrieval improvements

- pagination conventions;
- richer filtering;
- optional chunked long-content representation;
- optional semantic retrieval/vector integration behind a generic contract;
- provenance and version/freshness metadata.

## 2.0 — Authenticated Agent API

Introduce only after the read-only model is proven:

- scoped credentials;
- effective capability filtering;
- audit trail;
- revocation/expiration;
- read vs write distinction;
- risk classes;
- human confirmation guidance for sensitive actions.

Progression should be:

```text
read
-> draft/create safe objects
-> update unpublished objects
-> test actions
-> explicit publish/send actions
```

For Menu, future write capabilities may eventually include narrowly scoped operations such as:

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

These must reuse Menu's own validation/business logic and independently enforce `menu.admin`. Public `navigation.read` must never imply any of these actions.

No generic execution primitives such as SQL, PHP, shell or unrestricted filesystem operations.

Authenticated configuration controls must not appear before the corresponding authenticated action model is implemented.

## Future protocol adapters

Agent's internal model should be reusable by:

- MCP;
- REST/OpenAPI;
- ChatGPT Connector;
- other AI assistants;
- automation platforms;
- trusted custom applications.

Protocol support must remain an adapter over Agent resources/capabilities/actions rather than redefine plugin contracts or Agent's normalized models.

---

## Replacement principle

Agent is **not a migration wrapper around the previous LLM scripts**.

The target state is:

```text
Geeklog site context
        +
Core/plugin providers
        +
shared interoperability contracts
        ↓
      Agent
        ↓
normalized resources / structured providers / capabilities / actions
        ↓
llms.txt / Markdown / JSON / MCP / connectors / future adapters
```

There must be no runtime dependency on legacy LLM scripts, hostname switch registries or legacy per-site LLM text files.

---

## Design rule

> **Plugins expose shared data and capabilities. Hub interprets relationships. Agent exposes machine-readable access. Connectors adapt that access to clients.**

Agent must remain useful without Hub, ChatGPT, MCP or any specific AI provider.
