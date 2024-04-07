<?php

namespace Yormy\CiphersweetExtLaravel\Traits;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;

trait UsesCipherSweetExt
{
    use UsesCipherSweet;

    public function scopeWhereStartsWith(
        Builder $query,
        string $column,
        string $indexName,
        string|array $value,
    ): Collection {
        $builder = $this->scopeWhereBlind($query, $column, $indexName, $value);
        $allItems = $builder->get();

        $filteredItems = $allItems->filter(function ($item) use ($value, $column) {
            return str_contains($item[$column], $value) !== false;
        })->values();

        return $filteredItems;
    }
}
