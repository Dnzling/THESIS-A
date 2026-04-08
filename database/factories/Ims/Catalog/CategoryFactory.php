<?php

namespace Database\Factories\Ims\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ims\Catalog\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(rand(1, 3), true);
        
        return [
            'name' => ucfirst($name),
            'description' => $this->faker->sentence(10),
            'image_path' => $this->faker->imageUrl(640, 480, 'furniture'),
            'is_active' => $this->faker->boolean(90),
        ];
    }
    
    public function topLevel()
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => null,
            ];
        });
    }
    
    public function withParent()
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Category::inRandomOrder()->first()->id,
            ];
        });
    }
}