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

class DecryptRecordCommand extends Command
{
    protected $signature = 'db:decrypt-record {model} {decryptKey?}';

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

    protected function getDecryptionKey(): string
    {
        if ($decryptKey = $this->argument('decryptKey')) {
            return $decryptKey;
        }

        return config('ciphersweet.providers.string.key');
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
        $id = 2;
        $newClass = (new $modelClass());

        $records = DB::table($newClass->getTable())
            ->where('id',1000)
            ->orderBy(
                (new $modelClass())
                ->getKeyName()
            );

        if ($records->count() === 0 ) {
            $this->error(PHP_EOL. 'No records found');
        }

        $records->each(function (object $model) use ($modelClass, $newClass, &$updatedRows) {
                $model = (array)$model;

                $oldRow = new EncryptedRow(app(CipherSweetEngine::class), $newClass->getTable());
              //  $modelClass::configureCipherSweet($oldRow);

                $newRow = new EncryptedRow(
                    new CipherSweetEngine(new StringProvider($this->getDecryptionKey()), $oldRow->getBackend()),
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
                    try {
                        $ciphertext = $newRow->decryptRow($model);
                    } catch (InvalidCiphertextException $e) {
                        // possibly not encrypted, or not a valid key provided
                        $message = "Model {$modelClass} cannot be decrypted. \nEither the database is not encrypted, or no valid decryption key provided";
                        $this->error(PHP_EOL. $message);
                        die();
                    }

                    dump($ciphertext);

                }
            });

        $this->info("done");
    }
}
