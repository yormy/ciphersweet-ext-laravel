<?php

declare(strict_types=1);

namespace Yormy\CiphersweetExtLaravel;

use Illuminate\Support\ServiceProvider;
use Yormy\CiphersweetExtLaravel\Console\Commands\DecryptDbCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\DecryptRecordCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\EncryptDbCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\GenerateEncryptionKeyCommand;
use Yormy\CiphersweetExtLaravel\ServiceProviders\EventServiceProvider;

class CiphersweetExtServiceProvider extends ServiceProvider
{
    public const CONFIG_FILE = __DIR__.'/../config/ciphersweet-ext.php';

    /**
     * @psalm-suppress MissingReturnType
     */
    public function boot(): void
    {
        $this->publish();

        $this->registerCommands();
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function register(): void
    {
        $this->mergeConfigFrom(static::CONFIG_FILE, 'ciphersweet-ext');

        $this->app->register(EventServiceProvider::class);
    }

    private function publish(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_FILE => config_path('ciphersweet-ext.php'),
            ], 'config');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EncryptDbCommand::class,
                GenerateEncryptionKeyCommand::class,
                DecryptDbCommand::class,
                DecryptRecordCommand::class,
            ]);
        }
    }
}
