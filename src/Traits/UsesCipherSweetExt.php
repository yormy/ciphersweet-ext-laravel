<?php
namespace Yormy\CiphersweetExtLaravel\Traits;

use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;

trait UsesCipherSweetExt
{
    use UsesCipherSweet;

    public function scopeWhereStartsWith(
        Builder $query,
        string $column,
        string $indexName,
        string|array $value,
    ): Collection {
        $builder = $this->scopeWhereBlind($query, $column, $indexName, $value );
        $allItems = $builder->get();

        $filteredItems = $allItems->filter(function ($item) use ($value, $column) {
            return false !== str_contains($item[$column], $value);
        })->values();

        return $filteredItems;
    }
}
