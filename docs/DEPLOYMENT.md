# Deployment Guide

## Requirements

- WordPress 6.5+
- PHP 8.2+
- MySQL 8 or compatible MariaDB
- Node.js 20+ for building admin assets
- Composer for production autoload generation

## Build and package

Deploy from a production zip, not from an unbuilt source checkout: WordPress cannot resolve the compiled admin
assets unless `assets/admin/dist/.vite/manifest.json` exists.

```bash
npm run release
```

This runs `npm ci`, `npm run build`, `composer install --no-dev --optimize-autoloader`, `npm run verify:release-assets`
and `npm run package`, producing `build/aicore-wp.zip`. The CI workflow (`.github/workflows/release-package.yml`) runs
the same steps and asserts the manifest is inside the final zip.

## Configure providers

Set provider credentials in WordPress options or through the setup wizard:

- OpenAI
- Claude
- Gemini
- DeepSeek
- OpenRouter
- Groq
- Ollama local endpoint

Vector adapters are designed for Pinecone, Chroma, Qdrant, and Weaviate. Store secrets encrypted in production and restrict access to administrators.

## Security checklist

- Rotate MCP API keys regularly.
- Grant the minimum required scopes to external agents.
- Keep preview-before-publish enabled for AI tools.
- Review audit logs for MCP, workflow, and content actions.
- Use HTTPS for all REST, webhook, and future WebSocket traffic.
- Configure provider-side spend limits and model allowlists.

## SaaS commercialization

The billing module provides the data model for token credits, usage logs, subscriptions, and license activation. Stripe and Paddle adapters should be connected with signed webhooks and idempotency keys before enabling paid plans.
