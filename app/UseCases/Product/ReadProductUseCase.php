<?php

namespace App\UseCases\Product;

use App\Repositories\Product\ProductRepositoryInterface;

class ReadProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function handle(int $id)
    {
        return $this->repository->find($id);
    }
}