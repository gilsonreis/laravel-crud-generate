<?php

namespace App\Support;

class Filter
{
    public function __construct(
        private ?array $columns = ['*'],
        private ?string $orderColumn = 'created_at',
        private ?string $orderDirection = 'asc',
        private array $filters = []
    ) {}

    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function getOrderColumn(): ?string
    {
        return $this->orderColumn;
    }

    public function setOrderColumn(string $orderColumn): self
    {
        $this->orderColumn = $orderColumn;
        return $this;
    }

    public function getOrderDirection(): ?string
    {
        return $this->orderDirection;
    }

    public function setOrderDirection(string $orderDirection): self
    {
        if (!in_array($orderDirection, ['asc', 'desc'])) {
            throw new \DomainException('OrderDirection precisa ser "asc" ou "desc"');
        }
        $this->orderDirection = $orderDirection;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }
}
