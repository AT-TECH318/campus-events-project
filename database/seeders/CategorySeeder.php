<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the demonstration category catalogue.
     */
    public function run(): void
    {
        foreach (Category::catalogue() as $slug => $category) {
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                ],
            );
        }
    }
}
