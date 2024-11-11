<?php

namespace App\UseCases\Product;

use App\Repositories\Product\ProductRepositoryInterface;
use App\Types\Pagination;
use App\Filters\ProductFilter;

class GetAllProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(Pagination $pagination, ProductFilter $filter)
    {
        return $this->repository->getAll($pagination, $filter);
    }
}