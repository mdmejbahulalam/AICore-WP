<?php

declare(strict_types=1);

namespace AICore\WP\Modules;

use AICore\WP\Core\Container;
use AICore\WP\Core\ModuleInterface;

final class ModuleRegistry
{
    /** @var list<ModuleInterface> */
    private array $modules = [];

    public function __construct()
    {
        $this->modules = [
            new GenericModule('chat', 'AI Chat Center', ['POST /command', 'GET /conversations']),
            new GenericModule('chatbots', 'Chatbot Builder', ['GET /chatbots', 'POST /chatbots/session']),
            new GenericModule('agents', 'Multi-Agent System', ['GET /agents', 'POST /agents/delegate']),
            new GenericModule('workflows', 'Workflow Automation', ['GET /workflows', 'POST /workflows/run']),
            new GenericModule('content', 'Content Studio', ['POST /content/generate', 'POST /content/bulk']),
            new GenericModule('seo', 'SEO Center', ['POST /seo/audit', 'POST /seo/optimize']),
            new GenericModule('mcp', 'MCP Server', ['GET /mcp/tools', 'POST /mcp/execute']),
            new GenericModule('rag', 'Knowledge RAG', ['POST /rag/index', 'POST /rag/search']),
            new GenericModule('memory', 'AI Memory Manager', ['GET /memories', 'POST /memories']),
            new GenericModule('woocommerce', 'WooCommerce AI', ['POST /commerce/insights', 'POST /commerce/descriptions']),
            new GenericModule('images', 'AI Image Studio', ['POST /images/generate', 'POST /images/edit']),
            new GenericModule('voice', 'Voice + Multimodal', ['POST /voice/transcribe', 'POST /voice/speak']),
            new GenericModule('analytics', 'AI Analytics', ['GET /analytics/summary', 'GET /analytics/events']),
            new GenericModule('billing', 'Billing + Usage', ['GET /billing/usage', 'POST /billing/checkout']),
            new GenericModule('integrations', 'Integrations', ['GET /integrations', 'POST /integrations/connect']),
            new GenericModule('setup', 'Setup Wizard', ['GET /setup/status', 'POST /setup/save']),
        ];
    }

    public function registerModules(Container $container): void
    {
        foreach ($this->modules as $module) {
            $module->register($container);
            do_action('aicore_wp_module_registered', $module->id(), $module);
        }
    }

    /** @return list<array<string,mixed>> */
    public function routes(Container $container): array
    {
        return array_merge(...array_map(fn (ModuleInterface $module): array => $module->routes($container), $this->modules));
    }

    /** @return list<array<string,mixed>> */
    public function manifest(): array
    {
        return array_map(fn (ModuleInterface $module): array => ['id' => $module->id(), 'name' => $module->name()], $this->modules);
    }
}
