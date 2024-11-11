<?php

namespace App\UseCases\Product;

use App\Repositories\Product\ProductRepositoryInterface;
use App\Support\Pagination;
use App\Support\Filter;

class GetAllProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(Pagination $pagination, Filter $filter)
    {
        return $this->repository->getAll($pagination, $filter);
    }
}