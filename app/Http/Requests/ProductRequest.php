<?php

namespace App\Http\Requests;

class ProductRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required',
            'price' => 'sometimes|required|numeric',
            'description' => 'sometimes|required',
            'created_at' => 'sometimes|required|date',
            'category_id' => 'sometimes|required',
            'tags' => 'sometimes|required',
            'name_slug' => 'sometimes|required',

        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'price' => 'price',
            'description' => 'description',
            'created_at' => 'created_at',
            'category_id' => 'category_id',
            'tags' => 'tags',
            'name_slug' => 'name_slug',

        ];
    }
}