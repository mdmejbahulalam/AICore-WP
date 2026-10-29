<?php

declare(strict_types=1);

use AICore\WP\Modules\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    private ModuleRegistry $registry;

    protected function setUp(): void
    {
        WpStub::reset();
        $this->registry = new ModuleRegistry(dirname(__DIR__) . '/modules');
    }

    /** @return array<string,array<string,mixed>> */
    private function manifestById(): array
    {
        return array_column($this->registry->manifest(), null, 'id');
    }

    public function testDiscoversAllModuleManifests(): void
    {
        $manifest = $this->manifestById();

        self::assertCount(18, $manifest);
        self::assertSame('SEO Center', $manifest['seo']['name']);
        self::assertTrue($manifest['seo']['enabled']);
        self::assertSame(['chat'], $manifest['chatbots']['requires']);
        self::assertContains('chatbots', $manifest['chat']['required_by']);
    }

    public function testCannotDisableAModuleThatOthersNeed(): void
    {
        $result = $this->registry->setEnabled('chat', false);

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('aicore_module_required_by', $result->get_error_code());
        self::assertTrue($this->registry->isEnabled('chat'));
    }

    public function testDisableDependentsFirstThenParentAndReEnableInOrder(): void
    {
        foreach (['chatbots', 'agents'] as $dependent) {
            self::assertTrue($this->registry->setEnabled($dependent, false));
        }
        self::assertTrue($this->registry->setEnabled('chat', false));
        self::assertFalse($this->registry->isEnabled('chat'));

        $blocked = $this->registry->setEnabled('chatbots', true);
        self::assertInstanceOf(WP_Error::class, $blocked);
        self::assertSame('aicore_module_requires', $blocked->get_error_code());

        self::assertTrue($this->registry->setEnabled('chat', true));
        self::assertTrue($this->registry->setEnabled('chatbots', true));
    }

    public function testUnknownModuleIsRejected(): void
    {
        $result = $this->registry->setEnabled('nope', true);

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(404, $result->get_error_data()['status']);
        self::assertFalse($this->registry->isEnabled('nope'));
    }

    public function testDisabledModulesContributeNoRoutes(): void
    {
        $container = new AICore\WP\Core\Container();
        $paths = static fn (array $routes): array => array_column($routes, 'path');

        self::assertContains('/seo/manifest', $paths($this->registry->routes($container)));

        $this->registry->setEnabled('seo', false);
        self::assertNotContains('/seo/manifest', $paths($this->registry->routes($container)));
    }
}
