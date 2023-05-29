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


/**
 * @psalm-suppress UndefinedThisPropertyFetch
 *
 */
class AnonymizeCommand extends Command
{
    protected $signature = 'db:anonymize
                                {--model=* : Class names of the models to be anonymized}
                                {--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}
                                {--pretend : Display the number of anonymized records found instead of actioning on them}';

    protected $description = 'Anonymize models';

    private float $startTime;

    /**
     * The console components factory.
     *
     * @var \Illuminate\Console\View\Components\Factory
     *
     * @internal This property is not meant to be used or overwritten outside the framework.
     */
    protected $components;

    protected function configEnvironments(): array
    {
        return (array)config('anonymizer.environments');
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(Dispatcher $events)
    {
        $this->startTime = microtime(true);

        if (! in_array(config('app.env'), $this->configEnvironments())) {
            $this->error('It is forbidden to run anonymizer on '. (string)config('app.env').' environment');

            return 0;
        }

        $models = $this->getModels();

        if ($models->isEmpty()) {
            $this->components->info('No anonymizable models found.');

            return null;
        }

        if ($this->option('pretend')) {
            /**
             * @psalm-suppress MixedArgument
             */
            $models->each(fn ($model) => $this->pretendToAnonymize($model));
            $this->pretendToTruncate();

            return null;
        }

        $anonymizing = [];

        $events->listen(ModelsAnonymized::class, function (ModelsAnonymized $event) use (&$anonymizing) {
            /**
             * @var string[] $anonymizing
             */
            if (! in_array($event->model, $anonymizing)) {
                $anonymizing[] = $event->model;
            }

            $this->components->twoColumnDetail($event->model, "{$event->count} records ({$event->durationInSeconds} seconds)");
        });

        /**
         * @psalm-suppress MixedArgument
         */
        $models->each(fn ($model) => $this->anonymizeModel($model));

        $this->truncateTables();

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

    protected function anonymizeModel(string $model): void
    {
        try {
            $instance = $this->getClass($model);
        } catch (\Throwable) {
            return; // if the class cannot be created (ie abstract class) just skip it
        }

        $chunkSize = $this->option('chunk');

        /**
         * @psalm-suppress MixedMethodCall
         */
        $total = $this->isAnonymizable($model)
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
                return $this->isAnonymizable($model);
            })->filter(function ($model) {
                return class_exists($model);
            })->values();
    }

    protected function includeModel(string $model): bool
    {
        /**
         * @var string[] $ignorePaths
         */
        $ignorePaths = config('anonymizer.ignore');

        if (Str::startsWith($model, $ignorePaths)) {
            return false;
        }

        return true;
    }

    protected function getDefaultPath(): string
    {
        return app_path('');
    }

    protected function isAnonymizable(string $model): bool
    {
        $uses = class_uses_recursive($model);

        return in_array(Anonymizable::class, $uses);
    }

    protected function pretendToAnonymize(string $model): void
    {
        try {
            $instance = $this->getClass($model);
        } catch (\Throwable) {
            return; // if the class cannot be created (ie abstract class) just skip it
        }

        if (method_exists($instance, 'anonymizable')) {
            /**
             * @psalm-suppress MixedMethodCall
             */
            $count = (int)$instance->anonymizable()->count();
        } else {
            /**
             * @psalm-suppress MixedMethodCall
             */
            $count = $instance->count();
        }

        if ($count === 0) {
            $this->components->twoColumnDetail($model, "no action");
        } else {
            $this->components->twoColumnDetail($model, "{$count} records will be anonymized");
        }
    }

    protected function pretendToTruncate(): void
    {
        /**
         * @var string[] $truncateTables
         */
        $truncateTables = (array)config('anonymizer.truncate');

        if (empty($truncateTables)) {
            $this->components->info('No tables will be truncated');

            return;
        }

        foreach ($truncateTables as $truncateTable) {
            $this->components->twoColumnDetail($truncateTable, 'will be truncated');
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
