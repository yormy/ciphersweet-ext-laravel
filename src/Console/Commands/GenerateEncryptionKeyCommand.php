<?php

declare(strict_types=1);

namespace Yormy\CiphersweetExtLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @psalm-suppress UndefinedThisPropertyFetch
 */
class GenerateEncryptionKeyCommand extends Command
{
    protected $signature = 'db:encrypt-generate-key';

    protected $description = 'Generate new encryption key to be used';

    /**
     * The console components factory.
     *
     * @internal This property is not meant to be used or overwritten outside the framework.
     */
    protected \Illuminate\Console\View\Components\Factory $components;

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(Dispatcher $events)
    {
        $this->call('ciphersweet:generate');

        return null;
    }
}
