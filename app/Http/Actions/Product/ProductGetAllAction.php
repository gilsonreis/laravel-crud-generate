<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\UseCases\Product\GetAllProductUseCase;
use App\Traits\ApiResponser;
use App\Types\Pagination;
use App\Filters\ProductFilter;

class ProductGetAllAction extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request, GetAllProductUseCase $useCase)
    {
        try {
            $pagination = new Pagination($request->query('page', 1), $request->query('perPage', 15));
            $filter = new ProductFilter(
                $request->query('name'),
                $request->query('description'),
                $request->query('tags'),
                $request->query('name_slug'),
            );

            $filter->setOrderColumn('id')
                ->setOrderDirection('desc');
            $filter->setColumns(['name', 'description']);

            $result = $useCase->handle($pagination, $filter);
            return $this->successResponse($result->toArray());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
