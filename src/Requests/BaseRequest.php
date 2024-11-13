<?php

namespace Gilsonreis\LaravelCrudGenerator\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

abstract class BaseRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new Response([
            'status' => 'fail',
            'code' => 422,
            'data' => [
                'message' => $validator->errors()->first()
            ]
        ], 422);
        throw new ValidationException($validator, $response);
    }

    abstract public function rules(): array;
}