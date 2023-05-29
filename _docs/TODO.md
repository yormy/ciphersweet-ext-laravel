# 
enc entire db
dectript entire db

dec value

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
