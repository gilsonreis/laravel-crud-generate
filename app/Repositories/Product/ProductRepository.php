<?php

namespace App\Repositories\Product;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Types\Pagination;
use App\Filters\ProductFilter;

class ProductRepository implements ProductRepositoryInterface
{
    public function getAll(Pagination $pagination, ProductFilter $filter): LengthAwarePaginator|array
    {
        $query = Product::query();


        if ($filter?->getName()) {
            $query->orWhere('name', 'like', '%' . $filter->getName() . '%');
        }
        if ($filter?->getDescription()) {
            $query->orWhere('description', 'like', '%' . $filter->getDescription() . '%');
        }
        if ($filter?->getTags()) {
            $query->orWhere('tags', 'like', '%' . $filter->getTags() . '%');
        }
        if ($filter?->getNameSlug()) {
            $query->orWhere('name_slug', 'like', '%' . $filter->getNameSlug() . '%');
        }

        $query->orderBy($filter->getOrderColumn(), $filter->getOrderDirection());

        if ($pagination->hasPaginate()) {
            return $query->paginate(
                perPage: $pagination->getPerPage(),
                columns: $filter->getColumns(),
                page: $pagination->getPage()
            );
        }

        return $query->get($filter->getColumns())?->toArray() ?? [];
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
