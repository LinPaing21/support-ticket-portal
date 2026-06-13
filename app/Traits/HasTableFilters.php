<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTableFilters
{
    public function scopeGlobalSearch(Builder $query, string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            foreach ($this->filterable as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    public function scopeSorting(Builder $query, string $sort, string $direction): Builder
    {
        if (! in_array($sort, $this->sortable ?? []) || ! in_array($direction, ['asc', 'desc'])) {
            return $query;
        }

        return $query->orderBy($sort, $direction);
    }
}
