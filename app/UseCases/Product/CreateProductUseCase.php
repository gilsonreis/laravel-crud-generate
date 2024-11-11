<?php

namespace App\UseCases\Product;

use App\Repositories\Product\ProductRepositoryInterface;

class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(array $data)
    {
        return $this->repository->create($data);
    }
}