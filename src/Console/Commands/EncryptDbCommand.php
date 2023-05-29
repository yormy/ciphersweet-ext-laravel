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
use Yormy\CiphersweetExtLaravel\Events\ModelsAnonymized;
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
//        return (array)config('anonymizer.environments');
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


        echo "go encyrpt";


        // hp artisan ciphersweet:encrypt "Mexion\\BedrockUsers\\Models\\Member" 1db641ec8fb33bb5167c117ad289eb27f5745427d60e37ad1f116710759c67

//        $anonymizing = [];
//
//        $events->listen(ModelsAnonymized::class, function (ModelsAnonymized $event) use (&$anonymizing) {
//            /**
//             * @var string[] $anonymizing
//             */
//            if (! in_array($event->model, $anonymizing)) {
//                $anonymizing[] = $event->model;
//            }
//
//            $this->components->twoColumnDetail($event->model, "{$event->count} records ({$event->durationInSeconds} seconds)");
//        });

        /**
         * @psalm-suppress MixedArgument
         */
        $models->each(fn ($model) => $this->encryptModel($model));

        dd();


        AnonymizeWithoutModel::exec();

        $events->forget(ModelsAnonymized::class);

        $durationInMinutes = round((microtime(true) - $this->startTime)/60, 1);
        $this->components->twoColumnDetail('TOTAL DURATION', "{$durationInMinutes} minutes");

        return null;
    }

    protected function truncateTables(): void
    {
        /**
         * @var string[] $truncateTables
         */
        $truncateTables = config('anonymizer.truncate');

        foreach ($truncateTables as $truncateTable) {
            DB::table($truncateTable)->truncate();
            $this->components->twoColumnDetail($truncateTable, 'truncated');
        }
    }

    protected function encryptModel(string $model): void
    {
        // hp artisan ciphersweet:encrypt "Mexion\\BedrockUsers\\Models\\Member" 1db641ec8fb33bb5167c117ad289eb27f5745427d60e37ad1f116710759c67

        $encryptWith = '1b3195421c884e0b5414e55ead5b25001ad157b9fba137595646a1f781255e22';
        $model = $this->convertClass($model);
        $this->call('ciphersweet:encrypt', [
            'model' => 'Mexion\\BedrockUsers\\Models\\Member',
            'newKey' => $encryptWith
        ]);
dd('done');
        try {
            $instance = $this->getClass($model);
        } catch (\Throwable) {
            return; // if the class cannot be created (ie abstract class) just skip it
        }

        $chunkSize = $this->option('chunk');

        /**
         * @psalm-suppress MixedMethodCall
         */
        $total = $this->isEncryptable($model)
            ? (int)$instance->anonymizeAll($chunkSize)
            : 0;

        if ($total === 0) {
            $this->components->twoColumnDetail($model, '0 records');
        }
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