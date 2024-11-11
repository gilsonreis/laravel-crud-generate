<?php

namespace App\UseCases\Product;

use App\Repositories\Product\ProductRepositoryInterface;

class UpdateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }
}