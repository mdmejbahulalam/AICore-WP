# AICore WP — Build Roadmap

Baseline (today): foundation + provider fixes. Working: provider layer (`/command`), MCP `create_post`/`update_post`,
permission/rate-limit/audit classes, 15 DB tables, React admin shell. Everything else is a manifest-only placeholder.

Rules for every phase: one branch per phase off `main`-equivalent integration branch, one PR per phase, PHPUnit tests
for new PHP, `npm run build` + `php -l` green, manual verification on the local test site
(`http://localhost/aicore-wp/`), no real secrets committed.

## Phase 0 — Consolidate (foundation hardening)
- Create `integration` branch: foundation (#1/#5) + provider fixes + release tooling (#2) + enqueue notice (#3).
  Drop #4's duplicate `aicore-wp.php`/`package.json` (foundation already has a Vite + TS setup).
- Resolve conflicts (`AdminApp.php`, `composer.json`, `package.json`, README, DEPLOYMENT).
- Move env/setup docs from `claude/cool-pasteur-cdQQV` into `docs/dev/`, fix them for XAMPP (port 3307, no Docker); remove committed `.env`.
- Add PHPUnit (composer dev-dep) + a GitHub Actions CI (lint, tests, build).
- Fix known issues: public `/mcp/tools`, `embed()` stub flagged, `.gitignore` for `dist/`, `node_modules/`, lockfile policy.
- **Done when:** one branch builds, tests run in CI, plugin activates cleanly on the test site.

## Phase 1 — Core platform
- Settings screen: provider API keys (encrypted at rest), default provider/model, per-provider max tokens, "Test connection".
- Real module system: enable/disable per module, module settings, dependency order, per-module classes replacing `GenericModule`.
- Usage logging into `usage_logs` (tokens, provider, model, cost estimate); central `AI` service used by all modules.
- Streaming responses (SSE) and consistent error UI in React.
- **Done when:** an admin can configure OpenRouter/Claude/etc. in the UI and every call is logged with usage.

## Phase 2 — Chat + Memory
- Chat module: persisted conversations/messages, history list, rename/delete, streaming UI, model picker.
- Memory module: CRUD of user/site memories injected into prompts; real embeddings via provider `embeddings` endpoints (replace fake `embed()`).
- **Done when:** multi-turn chat survives reload; memories change answers.

## Phase 3 — Content, SEO, Images
- Content: generate/rewrite/expand/translate; "create draft post" action; Gutenberg sidebar panel.
- SEO: real `POST /seo/audit` (title/meta length, headings, links, alt text, schema hints) + AI `POST /seo/optimize`; write to own meta or Yoast/Rank Math.
- Images: generation via OpenAI-compatible images API, save to Media Library with alt text.
- **Done when:** from a post editor you can generate content, get an SEO score with fixes, and attach a generated image.

## Phase 4 — RAG + Chatbots
- RAG: ingest posts/pages (chunking → embeddings → `ai_embeddings`), cosine search, answers with citations, re-index on post save.
- Chatbots: bot CRUD (persona, model, knowledge sources), frontend widget (shortcode + block), sessions, rate limits, optional lead capture.
- **Done when:** a visitor chatbot answers from site content with sources.

## Phase 5 — Agents, Workflows, MCP
- Tool registry with JSON-schema'd tools; agent loop with tool calling and confirmation for destructive actions.
- Workflows: visual builder (reactflow already installed), triggers (post publish, schedule, webhook), runs via queue/WP-Cron, run history.
- MCP: spec-compliant server (JSON-RPC `tools/list`, `tools/call`), API-key management UI, scopes enforced per tool.
- **Done when:** an external MCP client lists/calls tools; a workflow runs on a trigger.

## Phase 6 — WooCommerce, Integrations, Voice
- WooCommerce (install on test site): product description/SEO generation (single + bulk), `create_product` tool, order/customer insights.
- Integrations: outbound webhooks + connector registry (Slack/email first).
- Voice: speech-to-text input and text-to-speech output in chat.

## Phase 7 — Analytics, Billing, API, Security, Setup
- Analytics: real dashboards from `usage_logs`/`analytics_events` (replace demo data).
- Billing: plans, quotas, usage-based limits enforced in the AI service (payment gateway optional/last).
- API: public REST API with key auth + docs. Security: role/capability matrix, PII redaction option, audit log viewer.
- Setup: first-run wizard (provider key, modules, sample content).

## Phase 8 — Release
- Packaging + release workflow, i18n, uninstall cleanup, WP.org guideline review, docs, version 1.0.

## Decisions needed from you
1. Confirm priority order of Phases 2–6 (default above).
2. Billing: quotas/plans only, or real payments (Stripe/WooCommerce)?
3. Which providers to support first in the UI (default: OpenRouter, OpenAI, Claude)?
