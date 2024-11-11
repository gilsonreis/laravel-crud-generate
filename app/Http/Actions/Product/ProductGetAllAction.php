<?php

namespace App\Http\Actions\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UseCases\Product\GetAllProductUseCase;
use App\Traits\ApiResponser;
use App\Support\Pagination;
use App\Support\Filter;

class ProductGetAllAction extends Controller
{
    use ApiResponser;

    public function __construct(
        private readonly GetAllProductUseCase $useCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $columns = $request->get('columns') ? explode(',', $request->get('columns')) : ['*'];
            $orderColumn = $request->get('orderColumn', 'created_at');
            $orderDirection = $request->get('orderDirection', 'asc');
            $modelFilters = $request->get('Product', []);



            $filter = new Filter(
                columns: $columns,
                orderColumn: $orderColumn,
                orderDirection: $orderDirection,
                filters: $modelFilters
            );

            $pagination = new Pagination(
                page: $request->get('page', 1),
                perPage: $request->get('perPage', 10)
            );

            $result = $this->useCase->handle($pagination, $filter);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
