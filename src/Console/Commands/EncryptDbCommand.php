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
class EncryptDbCommand extends Command
{
    protected $signature = 'db:encrypt
                                {--model=* : Class names of the models to be anonymized}
                                {--key : Encryption key}
                                {--pretend : Display the number of encrypted records found instead of actioning on them}';

    protected $description = 'Ecnrypt all models';

    private float $startTime;

    /**
     * The console components factory.
     *
     * @var \Illuminate\Console\View\Components\Factory
     *
     * @internal This property is not meant to be used or overwritten outside the framework.
     */
    protected $components;

//    protected function configEnvironments(): array
//    {
//        return (array)config('ciphersweet.environments');
//    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(Dispatcher $events)
    {
        $this->startTime = microtime(true);

        $models = $this->getModels();

        if ($models->isEmpty()) {
            $this->components->info('No encryptable models found.');

            return null;
        }

        if ($this->option('pretend')) {
            /**
             * @psalm-suppress MixedArgument
             */
            $models->each(fn ($model) => $this->pretendToEncrypt($model));
            return null;
        }

        $encrypting = [];
        $events->listen(ModelsEncrypted::class, function (ModelsEncrypted $event) use (&$encrypting) {
            /**
             * @var string[] $encrypting
             */
            if (! in_array($event->model, $encrypting)) {
                $encrypting[] = $event->model;
            }

            $this->components->twoColumnDetail($event->model, "{$event->count} records ({$event->durationInSeconds} seconds)");
        });

        /**
         * @psalm-suppress MixedArgument
         */
        $models->each(fn ($model) => $this->encryptModel($model));

        $events->forget(ModelsEncrypted::class);

        $durationInMinutes = round((microtime(true) - $this->startTime)/60, 1);
        $this->components->twoColumnDetail('TOTAL DURATION', "{$durationInMinutes} minutes");

        return null;
    }


    protected function encryptModel(string $model): void
    {
        $startTime = microtime(true);

        if (empty($encryptionKey = $this->option('key'))) {
            $this->components->info('No encryption key set use: ciphersweet:generate to generate a key and then specify with --key= ');
            // die();
        }
        $encryptionKey = '80b095075313bd1419e635574701196c1eff68bd50e3f2f4c82825bca1629f22';

        $model = $this->convertClass($model);

        try {
            $instance = $this->getClass($model);
        } catch (\Throwable) {
            return; // if the class cannot be created (ie abstract class) just skip it
        }

        $this->callSilent('ciphersweet:encrypt', [
            'model' => $model,
            'newKey' => $encryptionKey
        ]);

        $durationInSeconds = round(microtime(true) - $startTime, 0);

        event(new ModelsEncrypted($model, $instance->count(), $durationInSeconds));
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     * @psalm-suppress MixedArgument
     */
    protected function getModels(): Collection
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models)->filter(fn ($model) => class_exists($model))->values();
        }

        //return collect((new Finder)->in($this->getDefaultPath())->files()->name('*.php'));

        return collect((new Finder)->in($this->getDefaultPath())->files()->name('*.php'))
            ->map(function ($model) {
                $namespace = $this->laravel->getNamespace();

                return $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
                );
            })->filter(function ($model) {
                return $this->includeModel($model);
            })
            ->filter(function ($model) {
                return $this->isEncryptable($model);
            })->filter(function ($model) {
                return $this->classExists($model);
            })->values();
    }

    protected function convertClass(string $model): string
    {
        return str_replace('App\\\\var\www\html\packages\php\bedrock-users\src', 'Mexion\BedrockUsers', $model);
    }

    protected function classExists(string $model)
    {
        $model = $this->convertClass($model);
        //dd($model);
        //$model = "Mexion\BedrockUsers\Models\User";
        return class_exists($model);
        return true;
        //;
    }

    protected function includeModel(string $model): bool
    {
        return true;
//        /**
//         * @var string[] $ignorePaths
//         */
//        $ignorePaths = config('anonymizer.ignore');
//
//        if (Str::startsWith($model, $ignorePaths)) {
//            return false;
//        }
//
//        return true;
    }

    protected function getDefaultPath(): string
    {
        return "/var/www/html/app/local/site/main/vendor/mexion/bedrock-users/src/Models";
        return app_path('');
    }

    protected function isEncryptable(string $model): bool
    {
        //echo "**". $model. PHP_EOL;
        //return true;
        $model = $this->convertClass($model);
        $uses = class_uses_recursive($model);

        return in_array(UsesCipherSweet::class, $uses);
    }

    protected function pretendToEncrypt(string $model): void
    {
        $model = $this->convertClass($model);

        try {
            $instance = $this->getClass($model);
        } catch (\Throwable) {
            return; // if the class cannot be created (ie abstract class) just skip it
        }

        /**
         * @psalm-suppress MixedMethodCall
         */
        $count = $instance->count();

        if ($count === 0) {
            $this->components->twoColumnDetail($model, "no action");
        } else {
            $this->components->twoColumnDetail($model, "{$count} records will be encrypted");
        }
    }

    /**
     * @psalm-suppress InvalidStringClass
     */
    private function getClass(string $name): Model
    {
        /**
         * @var Model
         */
        return new $name();
    }

}
