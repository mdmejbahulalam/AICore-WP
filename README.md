# AICore WP

AICore WP is an enterprise-grade AI Operating System for WordPress. It provides a modular backend, REST/MCP API surface, React admin dashboard, AI orchestration layer, RAG-ready memory system, workflow automation foundation, chatbot platform scaffolding, WooCommerce AI suite, analytics, billing, security, and setup documentation.

## Highlights

- Modular WordPress plugin architecture with dependency injection and dynamic module registration.
- AI command center with provider registry for OpenAI, Claude, Gemini, DeepSeek, OpenRouter, Groq, and Ollama-compatible endpoints.
- MCP-compatible tool discovery and secure execution endpoints for external AI agents.
- Scalable MySQL migration covering conversations, messages, embeddings, memories, workflows, chatbots, generations, analytics, prompts, API keys, MCP tools, subscriptions, and usage logs.
- Premium React/TypeScript admin console using TailwindCSS, React Query, Zustand, Framer Motion, Recharts, and React Flow.
- Security-first controls: WordPress capabilities, REST nonce support, rate limiting, API key hashing, audit logs, sanitized inputs, and preview-first tools.

## Development

```bash
composer install
npm install
npm run build
php -l aicore-wp.php && find src modules templates tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Production

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) for build, provider configuration, security, and SaaS commercialization guidance.
