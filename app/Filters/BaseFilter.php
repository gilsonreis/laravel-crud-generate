<?php

namespace App\Filters;

abstract class BaseFilter
{
    public function __construct(
        private ?array $columns = ['*'],
        private ?string $orderColumn = 'created_at',
        private ?string $orderDirection = 'asc',
    ) {
    }

    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function setColumns(?array $columns): BaseFilter
    {
        $this->columns = $columns;
        return $this;
    }

    public function getOrderColumn(): ?string
    {
        return $this->orderColumn;
    }

    public function setOrderColumn(?string $orderColumn): BaseFilter
    {
        $this->orderColumn = $orderColumn;
        return $this;
    }

    public function getOrderDirection(): ?string
    {
        return $this->orderDirection;
    }

    public function setOrderDirection(?string $orderDirection): BaseFilter
    {
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            throw new \DomainException('OrderDirection precisa ser "asc" ou "desc"', 422);
        }

        $this->orderDirection = $orderDirection;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'columns' => $this->getColumns(),
            'orderColumn' => $this->getOrderColumn(),
            'orderDirection' => $this->getOrderDirection()
        ];
    }
}