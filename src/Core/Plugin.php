<?php

declare(strict_types=1);

namespace AICore\WP\Core;

use AICore\WP\Admin\AdminApp;
use AICore\WP\AI\Orchestrator;
use AICore\WP\AI\ProviderRegistry;
use AICore\WP\Api\RestRouter;
use AICore\WP\Database\Repository;
use AICore\WP\Modules\ModuleRegistry;
use AICore\WP\Queue\QueueWorker;
use AICore\WP\Security\AuditLogger;
use AICore\WP\Security\PermissionGuard;
use AICore\WP\Security\RateLimiter;

final class Plugin
{
    private static ?self $instance = null;

    private function __construct(private readonly string $file, private readonly Container $container)
    {
    }

    public static function boot(string $file): self
    {
        if (self::$instance === null) {
            self::$instance = new self($file, new Container());
        }
        return self::$instance;
    }

    public function register(): void
    {
        $this->bindServices();
        $this->container->get(ModuleRegistry::class)->registerModules($this->container);

        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_menu', [$this->container->get(AdminApp::class), 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this->container->get(AdminApp::class), 'enqueue']);
        add_action('rest_api_init', [$this->container->get(RestRouter::class), 'register']);
        add_action('aicore_wp_queue_tick', [$this->container->get(QueueWorker::class), 'run']);

        if (! wp_next_scheduled('aicore_wp_queue_tick')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'aicore_minute', 'aicore_wp_queue_tick');
        }
        add_filter('cron_schedules', static function (array $schedules): array {
            $schedules['aicore_minute'] = ['interval' => 60, 'display' => __('Every minute', 'aicore-wp')];
            return $schedules;
        });
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('aicore-wp', false, dirname(plugin_basename($this->file)) . '/languages');
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function bindServices(): void
    {
        $this->container->singleton(Repository::class, fn (): Repository => new Repository($GLOBALS['wpdb']));
        $this->container->singleton(AuditLogger::class, fn (Container $c): AuditLogger => new AuditLogger($c->get(Repository::class)));
        $this->container->singleton(RateLimiter::class, fn (): RateLimiter => new RateLimiter());
        $this->container->singleton(PermissionGuard::class, fn (Container $c): PermissionGuard => new PermissionGuard($c->get(AuditLogger::class)));
        $this->container->singleton(ProviderRegistry::class, fn (): ProviderRegistry => new ProviderRegistry());
        $this->container->singleton(Orchestrator::class, fn (Container $c): Orchestrator => new Orchestrator($c->get(ProviderRegistry::class), $c->get(Repository::class), $c->get(AuditLogger::class)));
        $this->container->singleton(ModuleRegistry::class, fn (): ModuleRegistry => new ModuleRegistry());
        $this->container->singleton(RestRouter::class, fn (Container $c): RestRouter => new RestRouter($c));
        $this->container->singleton(AdminApp::class, fn (): AdminApp => new AdminApp());
        $this->container->singleton(QueueWorker::class, fn (Container $c): QueueWorker => new QueueWorker($c->get(Repository::class), $c->get(AuditLogger::class)));
    }
}
