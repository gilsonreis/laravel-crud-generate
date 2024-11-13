<?php

namespace Gilsonreis\LaravelCrudGenerator\Support;

class Pagination
{
    public function __construct(private int $page = 1, private int $perPage = 15, private ?bool $paginate = true)
    {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): Pagination
    {
        $this->page = $page;
        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): Pagination
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function setPaginate(bool $setPaginate): Pagination
    {
        $this->paginate = $setPaginate;
        return $this;
    }

    public function hasPaginate(): bool
    {
        return $this->paginate;
    }

    public function toArray(): array
    {
        return [
            'page' => $this->getPage(),
            'per_page' => $this->getPerPage(),
            'paginate' => $this->hasPaginate(),
        ];
    }
}