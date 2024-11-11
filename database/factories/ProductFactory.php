<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 0, 1000),
            'description' => $this->faker->paragraph,
            'category_id' => $this->faker->word,
            'tags' => $this->faker->words(mt_rand(2, 5)),
            'name_slug' => $this->faker->slug(3)
        ];
    }
}
