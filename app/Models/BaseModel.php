<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    /**
     * Aplica filtros dinamicamente com base na query string, usando o nome do modelo como chave.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $queryParams
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplyFilters($query, ?array $queryParams = [])
    {
        if (isset($queryParams)) {
            foreach ($queryParams as $field => $value) {

                if (Str::endsWith($field, '_between')) {
                    // Filtro "between"
                    $column = Str::before($field, '_between');
                    $dates = explode(',', $value);
                    if (Schema::hasColumn($this->getTable(), $column) && count($dates) === 2) {
                        $query->whereBetween($column, [$dates[0], $dates[1]]);
                    }

                } elseif (Str::endsWith($field, '_in')) {
                    // Filtro "in"
                    $column = Str::before($field, '_in');
                    $values = explode(',', $value);
                    if (Schema::hasColumn($this->getTable(), $column)) {
                        $query->whereIn($column, $values);
                    }

                } elseif (Str::endsWith($field, '_not_in')) {
                    // Filtro "not in"
                    $column = Str::before($field, '_not_in');
                    $values = explode(',', $value);
                    if (Schema::hasColumn($this->getTable(), $column)) {
                        $query->whereNotIn($column, $values);
                    }

                } elseif (Str::endsWith($field, '_gt')) {
                    // Filtro "greater than"
                    $column = Str::before($field, '_gt');
                    if (Schema::hasColumn($this->getTable(), $column)) {
                        $query->where($column, '>', $value);
                    }

                } elseif (Str::endsWith($field, '_lt')) {
                    // Filtro "less than"
                    $column = Str::before($field, '_lt');
                    if (Schema::hasColumn($this->getTable(), $column)) {
                        $query->where($column, '<', $value);
                    }

                } elseif (Str::endsWith($field, '_exact')) {
                    // Filtro "exact match"
                    $column = Str::before($field, '_exact');
                    if (Schema::hasColumn($this->getTable(), $column)) {
                        $query->where($column, '=', $value);
                    }

                } elseif (Str::endsWith($field, '_not_null')) {
                    // Filtro "not null"
                    $column = Str::before($field, '_not_null');
                    if (Schema::hasColumn($this->getTable(), $column) && $value) {
                        $query->whereNotNull($column);
                    }

                } elseif (Str::endsWith($field, '_is_null')) {
                    // Filtro "is null"
                    $column = Str::before($field, '_is_null');
                    if (Schema::hasColumn($this->getTable(), $column) && $value) {
                        $query->whereNull($column);
                    }
                } elseif (Str::startsWith($field, 'or_')) {
                    // Remover o prefixo 'or_' e dividir os campos
                    $fields = explode('_', Str::after($field, 'or_'));

                    $query->where(function ($q) use ($fields, $value) {
                        foreach ($fields as $orField) {
                            $q->orWhere($orField, 'like', '%' . $value . '%');
                        }
                    });
                } else {
                    // Filtro padrão "like"
                    if (Schema::hasColumn($this->getTable(), $field) && !is_null($value)) {
                        $query->where($field, 'like', "%$value%");
                    }
                }
            }
        }

        return $query;
    }
}
