<?php

namespace Yormy\CiphersweetExtLaravel\Traits;

use InvalidArgumentException;
use Yormy\CiphersweetExtLaravel\Events\ModelsAnonymized;
use Yormy\CiphersweetExtLaravel\Services\AnonymizeService;

trait Anonymizable
{
    public function anonymizeAll(int $chunkSize = 1000): int
    {
        $total = 0;
        $startTime = microtime(true);

        if (method_exists($this, 'anonymizable')) {
            $item = $this->anonymizable();
        } else {
            $item = $this;
        }

        $item->chunkById($chunkSize, function ($models) use (&$total, $startTime) {
            $models->each->anonymize();

            $total += $models->count();

            $durationInSeconds = round(microtime(true) - $startTime, 0);
            event(new ModelsAnonymized(static::class, $total, $durationInSeconds));
        });

        return $total;
    }

    /**
     * Anonymize the model in the database.
     */
    public function anonymize(): bool|null
    {
        foreach ($this->anonymizable as $columnName => $config) {
            if (!array_key_exists($columnName, $this->attributes)) {
                throw new InvalidArgumentException("The column {$columnName} does not exist");
            }

            $jsonFaker = $config['jsonfaker'] ?? null;
            if ($jsonFaker) {
                $fieldValue = $this->getJsonFakerValue($jsonFaker, $this, $columnName);
            } else {
                $fieldValue = $this->getFakerValue($config, $this);
            }

            $this[$columnName] = $fieldValue;
        }

        return $this->saveQuietly();
    }

    protected function getFakerValue(array $config, $currentModel): string
    {
        return AnonymizeService::get($config, $currentModel);
    }

    protected function getJsonFakerValue(array $jsonFaker, $currentModel, string $columnName): array
    {
        $fieldValue = $currentModel[$columnName];
        foreach ($jsonFaker as $fieldName => $config) {

            $value = AnonymizeService::get($config, $this);
            $fieldValue[$fieldName] = $value;
        }

        return $fieldValue;
    }
}
