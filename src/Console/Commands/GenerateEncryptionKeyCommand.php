<?php

namespace Yormy\CiphersweetExtLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Yormy\CiphersweetExtLaravel\Actions\AnonymizeWithoutModel;
use Yormy\CiphersweetExtLaravel\Events\ModelsEncrypted;
use Yormy\CiphersweetExtLaravel\Traits\Anonymizable;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;

/**
 * @psalm-suppress UndefinedThisPropertyFetch
 *
 */
class GenerateEncryptionKeyCommand extends Command
{
    protected $signature = 'db:encrypt-generate-key';

    protected $description = 'Generate new encryption key to be used';

    /**
     * The console components factory.
     *
     * @var \Illuminate\Console\View\Components\Factory
     *
     * @internal This property is not meant to be used or overwritten outside the framework.
     */
    protected $components;

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(Dispatcher $events)
    {
        $this->call('ciphersweet:generate');

        return null;
    }


}
