<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UseCases\Product\ShowProductUseCase;
use App\Traits\ApiResponser;

class ProductShowAction extends Controller
{
    use ApiResponser;

    public function __construct(
        private readonly ShowProductUseCase $useCase
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $result = $this->useCase->handle($id);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}