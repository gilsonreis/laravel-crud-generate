<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use App\UseCases\Product\CreateProductUseCase;
use App\Traits\ApiResponser;
use App\Http\Requests\ProductRequest;

class ProductCreateAction extends Controller
{
    use ApiResponser;

    public function __invoke(ProductRequest $request, CreateProductUseCase $useCase)
    {
        try {
            $result = $useCase->handle($request->validated());
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}