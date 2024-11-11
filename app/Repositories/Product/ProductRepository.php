<?php

namespace App\Repositories\Product;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\Pagination;
use App\Support\Filter;

class ProductRepository implements ProductRepositoryInterface
{
    public function getAll(Pagination $pagination, Filter $filter): LengthAwarePaginator|array
    {
        $query = Product::query();

        $query->applyFilters($filter->getFilters());

        $query->orderBy($filter->getOrderColumn(), $filter->getOrderDirection());

        if ($pagination->hasPaginate()) {
            return $query->paginate(
                perPage: $pagination->getPerPage(),
                columns: $filter->getColumns(),
                page: $pagination->getPage()
            )->toArray();
        }

        return $query->get($filter->getColumns())->toArray();
    }

    public function find(int $id): ?Product
    {
        return Product::query()->find($id)?->first();
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Product::query()->find($id)?->update($data) ?? false;
    }

    public function delete(int $id): bool
    {
        return Product::query()->find($id)?->delete() ?? false;
    }
}