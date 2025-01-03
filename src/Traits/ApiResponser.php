<?php

namespace Gilsonreis\LaravelCrudGenerator\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\{JsonResponse, Response};

trait ApiResponser
{

    /**
     * Build Success Response
     * @param array|string $data
     * @param int $code
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     */
    public function successResponse(array|string $data, int $code = Response::HTTP_OK, array $headers = [], int $options = 0): JsonResponse
    {
        if (is_string($data)) {
            return response()->json([
                'status' => 'success',
                'code' => $code,
                'data' => [
                    'message' => $data
                ]
            ], $code, $headers, $options);
        }   
        if (is_array($data) && array_key_exists('data', $data)) {
            return response()->json([
                'status' => 'success',
                'code' => $code,
                'data' => [
                    'results' => $data['data'] ?? [],
                    'links' => [
                        'first' => $data['first_page_url'],
                        'last' => $data['last_page_url'],
                        'next' => $data['next_page_url'],
                        'prev' => $data['prev_page_url'],
                    ],
                    'meta' => [
                        'current_page' => $data['current_page'],
                        'last_page' => $data['last_page'],
                        'from' => $data['from'],
                        'path' => $data['path'],
                        'per_page' => $data['per_page'],
                        'to' => $data['to'],
                        'total' => $data['total'],
                        'links' => $data['links']
                    ]
                ]
            ], $code, $headers, $options);
        }

        return response()->json([
            'status' => 'success',
            'code' => $code,
            'data' =>  $data ?? [],
            
        ], $code, $headers, $options);
    }


    /**
     * Build Error Response
     * @param string $message
     * @param int $code
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     * @throws \Exception
     */
    public function errorResponse(string $message, int $code = Response::HTTP_INTERNAL_SERVER_ERROR, array $headers = [], int $options = 0): JsonResponse
    {
        $status = match ($code) {
            400, 401, 403, 409, 422 => 'fail',
            404, 500, 501, 502 => 'error',
            default => 'error'
        };

        return response()->json([
            'status' => $status,
            'code' => $code,
            'data' => [
                'message' => $message
            ]
        ], $code, $headers, $options);
    }
}
