<?php

namespace Yormy\CiphersweetExtLaravel\Events;

class ModelsAnonymized
{
    public function __construct(
        public readonly string $model,
        public readonly int $count,
        public readonly float $durationInSeconds
    ) {

    }
}
