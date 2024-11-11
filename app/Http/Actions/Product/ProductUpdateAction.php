<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use App\UseCases\Product\UpdateProductUseCase;
use App\Traits\ApiResponser;
use App\Http\Requests\ProductRequest;

class ProductUpdateAction extends Controller
{
    use ApiResponser;

    public function __invoke(ProductRequest $request, UpdateProductUseCase $useCase, int $id)
    {
        try {
            $result = $useCase->handle($id, $request->validated());
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}