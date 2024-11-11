<?php

namespace App\Repositories\Product;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\Pagination;
use App\Support\Filter;

interface ProductRepositoryInterface
{
    public function getAll(Pagination $pagination, Filter $filter): LengthAwarePaginator|array;

    public function find(int $id);

    public function create(array $data);

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}