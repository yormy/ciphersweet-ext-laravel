<?php

namespace Yormy\CiphersweetExtLaravel\Services;

use Faker\Factory;
use InvalidArgumentException;

class AnonymizeService
{
    public static function get(array $config, $model = null): string
    {
        $faker = Factory::create(config('anonymizer.faker.locale'));

        $fakerConfig = $config['faker'];

        $provider = $fakerConfig['provider'] ?? null;
        if (! $provider) {
            throw new InvalidArgumentException('The column name must specify how to anonymize the data');
        }
        $params = $fakerConfig['params'] ?? null;

        if ($model && $provider === 'database') {
            $field = $fakerConfig['params']['copyField'] ?? null;
            if (!$field) {
                throw new InvalidArgumentException('The field name must specify how to anonymize the data');
            }

            $prefix = $fakerConfig['params']['prefix'] ?? '';

            $anonymizedValue = $prefix. $model[$field];
        } else {
            $anonymizedValue = call_user_func([$faker, $provider], $params);
        }


        return $anonymizedValue;
    }
}
