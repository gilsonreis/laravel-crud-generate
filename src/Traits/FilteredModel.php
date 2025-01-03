<?php

namespace Gilsonreis\LaravelCrudGenerator\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait FilteredModel
{
    /**
     * Aplica filtros à consulta com base nos parâmetros fornecidos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (Str::startsWith($field, '_or')) {
                $query->where(function ($q) use ($value) {
                    foreach ($value as $orCondition) {
                        foreach ($orCondition as $orField => $orValue) {
                            $this->applyOperator($q, $orField, $orValue, 'orWhere');
                        }
                    }
                });
            } else {
                $this->applyOperator($query, $field, $value, 'where');
            }
        }

        return $query;
    }

    /**
     * Aplica o operador apropriado ao campo e valor fornecidos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @param string $method
     */
    protected function applyOperator($query, $field, $value, $method = 'where')
    {
        $operators = [
            '_like'      => ['operator' => 'LIKE', 'value' => '%' . $value . '%'],
            '_gt'        => ['operator' => '>', 'value' => $value],
            '_lt'        => ['operator' => '<', 'value' => $value],
            '_gte'       => ['operator' => '>=', 'value' => $value],
            '_lte'       => ['operator' => '<=', 'value' => $value],
            '_in'        => ['method' => $method . 'In', 'value' => explode(',', $value)],
            '_not_in'    => ['method' => $method . 'NotIn', 'value' => explode(',', $value)],
            '_null'      => ['operator' => '=', 'value' => null],
            '_not_null'  => ['operator' => '!=', 'value' => null],
        ];

        foreach ($operators as $suffix => $operation) {
            if (Str::endsWith($field, $suffix)) {
                $actualField = Str::before($field, $suffix);
                $methodToApply = $operation['method'] ?? $method;
                $operator = $operation['operator'] ?? null;
                if($operator != null){
                $query->$methodToApply($actualField, $operator, $operation['value']);
                }else{
                    $query->$methodToApply($actualField, $operation['value']);
                }
                return;
            }
        }

        if (Str::endsWith($field, '_between')) {
            $actualField = Str::before($field, '_between');
            $range = explode(',', $value);
            if (count($range) === 2) {
                $query->$method . 'Between'($actualField, [$range[0], $range[1]]);
            }
            return;
        }

        $query->$method($field, '=', $value);
    }
}
