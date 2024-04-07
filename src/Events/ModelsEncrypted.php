<?php

declare(strict_types=1);

namespace Yormy\CiphersweetExtLaravel\Events;

class ModelsEncrypted
{
    public function __construct(
        public readonly string $model,
        public readonly int $count,
        public readonly float $durationInSeconds
    ) {
    }
}
