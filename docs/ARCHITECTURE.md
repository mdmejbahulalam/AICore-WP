# AICore WP Architecture

AICore WP is a modular WordPress AI Operating System. The plugin bootstraps through `aicore-wp.php`, creates a service container, registers modules dynamically, exposes REST/MCP endpoints, and renders a React admin console.

## Backend layers

1. **Core container**: lightweight dependency injection and singleton lifecycle management.
2. **Module registry**: independent feature modules for chat, agents, workflows, content, SEO, MCP, RAG, memory, WooCommerce, images, voice, analytics, billing, integrations, and setup.
3. **Database repository**: table-prefixed access with prepared reads and WordPress insert APIs.
4. **AI orchestration**: provider registry, provider adapters, command execution, conversation persistence, token metering, and audit events.
5. **Security**: capability guards, scope checks, rate limiting, audit logging, sanitized REST input, hashed API key support, and preview-first AI execution.
6. **Queue worker**: WP-Cron worker extension point for workflow retries, indexing jobs, background generation, and webhooks.

## Database schema

The migration creates scalable tables for conversations, messages, embeddings, memories, workflows, workflow runs, chatbot sessions/messages, generations, analytics, prompts, API keys, MCP tools, subscriptions, and usage logs. Tables include relationship indexes for conversation/message lookups, vector source lookups, workflow status scans, event analytics, and usage metering.

## MCP server

The MCP implementation is exposed under `/wp-json/aicore/v1/mcp/*`. The tool registry advertises WordPress-safe tools such as `create_post`, `update_post`, `optimize_seo`, `create_product`, and `run_workflow`. Tool execution is protected by API key authentication and permission scopes. Destructive actions should remain draft/preview-first unless an authorized human approves publishing.

## Admin application

The React console uses TypeScript, TailwindCSS, React Query, Zustand, Framer Motion, Recharts, and React Flow. It includes a SaaS dashboard shell, dark mode styling, workflow visualization, usage charts, activity timeline, notifications, and a floating command assistant connected to the REST command endpoint.

## Extension points

- `aicore_wp_module_registered` fires after each module registers.
- Module manifests under `modules/*/module.json` describe permissions and events.
- Providers can implement `ProviderInterface` and register with `ProviderRegistry`.
- Tools can be added to `ToolRegistry` and mapped to scope-protected execution methods.
- Queue work can be attached to the `aicore_wp_queue_tick` cron hook.
