<?php

namespace Yormy\CiphersweetExtLaravel;

use Illuminate\Support\ServiceProvider;
use Yormy\CiphersweetExtLaravel\Console\Commands\AnonymizeCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\DecryptDbCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\DecryptRecordCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\EncryptDbCommand;
use Yormy\CiphersweetExtLaravel\Console\Commands\GenerateEncryptionKeyCommand;
use Yormy\CiphersweetExtLaravel\ServiceProviders\EventServiceProvider;

class CiphersweetExtServiceProvider extends ServiceProvider
{
    const CONFIG_FILE = __DIR__.'/../config/ciphersweet-ext.php';

    /**
     * @psalm-suppress MissingReturnType
     */
    public function boot()
    {
        $this->publish();

        $this->registerCommands();
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function register()
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
                DecryptRecordCommand::class
            ]);
        }
    }
}
