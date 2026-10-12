<?php

declare(strict_types=1);

namespace Plateformweb\ItToolsLaravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Config;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\Transport\TransportInterface;
use Plateformweb\ItToolsLaravel\Transport\LaravelHttpTransport;

final class ItToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/it-tools.php', 'it-tools');

        $this->app->singleton(Config::class, static function (Application $app): Config {
            /** @var array<string, mixed> $settings */
            $settings = $app->make('config')->get('it-tools', []);

            $apiKey = $settings['api_key'] ?? null;
            if (!\is_string($apiKey) || '' === $apiKey) {
                throw new ItToolsException('IT-Tools : definissez IT_TOOLS_API_KEY (config it-tools.api_key).');
            }

            return new Config(
                $apiKey,
                \is_string($settings['api_secret'] ?? null) ? $settings['api_secret'] : null,
                \is_string($settings['base_uri'] ?? null) && '' !== $settings['base_uri'] ? $settings['base_uri'] : Config::DEFAULT_BASE_URI,
                (float) ($settings['timeout'] ?? 30.0),
                (bool) ($settings['verify_peer'] ?? true),
            );
        });

        $this->app->singleton(TransportInterface::class, static fn (Application $app): TransportInterface => new LaravelHttpTransport($app->make(Factory::class)));

        $this->app->singleton(Client::class, static fn (Application $app): Client => new Client($app->make(Config::class), $app->make(TransportInterface::class)));
        $this->app->alias(Client::class, 'it-tools');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/it-tools.php' => $this->app->configPath('it-tools.php')], 'it-tools-config');
        }
    }
}
