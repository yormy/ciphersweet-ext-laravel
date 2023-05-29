<?php

namespace Yormy\CiphersweetExtLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;
use ParagonIE\CipherSweet\KeyRotation\RowRotator;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class DecryptDbCommand extends Command
{
    //protected $signature = 'db:decrypt {model} {newKey} ';
    protected $signature = 'db:decrypt {model}  {newKey} {sortDirection=asc}';

    protected $description = 'Encrypt the values of a model';

    public function handle(): int
    {
        if (! $this->ensureValidInput()) {
            return self::INVALID;
        }

        $modelClass = $this->argument('model');

        $this->decryptModelValues($modelClass);

        return self::SUCCESS;
    }

    protected function ensureValidInput(): bool
    {
        /** @var class-string<\Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted> $modelClass */
        $modelClass = $this->argument('model');

        if (! class_exists($modelClass)) {
            $this->error("Model {$modelClass} does not exist");

            return false;
        }

        $newClass = (new $modelClass());

        if (! $newClass instanceof CipherSweetEncrypted) {
            $this->error("Model {$modelClass} does not implement CipherSweetEncrypted");

            return false;
        }

        return true;
    }

    /**
     * @param class-string<\Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted> $modelClass
     *
     * @return void
     */
    protected function decryptModelValues(string $modelClass): void
    {
        $updatedRows = 0;

        $newClass = (new $modelClass());

        $this->getOutput()->progressStart(DB::table($newClass->getTable())->count());
        $sortDirection = $this->argument('sortDirection');

        DB::table($newClass->getTable())
            ->orderBy((new $modelClass())
                ->getKeyName(), $sortDirection)
            ->each(function (object $model) use ($modelClass, $newClass, &$updatedRows) {
                $model = (array)$model;

                $oldRow = new EncryptedRow(app(CipherSweetEngine::class), $newClass->getTable());
                $modelClass::configureCipherSweet($oldRow);

                $newRow = new EncryptedRow(
                    new CipherSweetEngine(new StringProvider($this->argument('newKey')), $oldRow->getBackend()),
                    $newClass->getTable(),
                );
                $modelClass::configureCipherSweet($newRow);

                $rotator = new RowRotator($oldRow, $newRow);



                //if ($rotator->needsReEncrypt($model)) { // how to determine if encrypted
                if (true) {
                    try {
                        [$indices] = $rotator->prepareForUpdate($model);
                    } catch (InvalidCiphertextException $e) {
                        [$indices] = $newRow->prepareRowForStorage($model);
                    }

                    // update database with unencrypted data
                    $ciphertext = $newRow->decryptRow($model);
                    DB::table($newClass->getTable())
                        ->where($newClass->getKeyName(), $model[$newClass->getKeyName()])
                        ->update(Arr::only($ciphertext, $newRow->listEncryptedFields()));

                    // delete old indexes
                    foreach ($indices as $name => $value) {
                        DB::table('blind_indexes')->where([
                            'indexable_type' => $newClass->getMorphClass(),
                            'indexable_id' => $model[$newClass->getKeyName()],
                            'name' => $name,
                        ], [
                            'value' => $value,
                        ])->delete();
                    }

                    $updatedRows++;
                }

                $this->getOutput()->progressAdvance();
            });

        $this->getOutput()->progressFinish();

        $this->info("Updated {$updatedRows} rows.");
        $this->info("You can now set your config key to the new key.");
    }
}
