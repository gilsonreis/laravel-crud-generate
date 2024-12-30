<?php

namespace Gilsonreis\LaravelCrudGenerator\Exception;

use Exception;
use Gilsonreis\LaravelCrudGenerator\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JwtException extends Exception
{
    use ApiResponser;
    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
     return    $this->errorResponse($this->getMessage(), 403);
    }
}
