<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UseCases\Product\DeleteProductUseCase;
use App\Traits\ApiResponser;

class ProductDeleteAction extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request, DeleteProductUseCase $useCase, int $id)
    {
        try {
            $result = $useCase->handle($id);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}