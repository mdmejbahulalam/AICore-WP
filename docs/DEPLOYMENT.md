# Deployment Guide

## Requirements

- WordPress 6.5+
- PHP 8.2+
- MySQL 8 or compatible MariaDB
- Node.js 20+ for building admin assets
- Composer for production autoload generation

## Build

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

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
