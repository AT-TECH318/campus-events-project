<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CategoryIndexRequest;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(CategoryIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);

        $perPage = (int) ($request->validated('per_page') ?? 15);

        return CategoryResource::collection(
            Category::query()->orderBy('name')->paginate($perPage),
        );
    }

    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::query()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'slug' => $this->uniqueSlug($request->validated('name')),
        ]);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        $attributes = $request->validated();

        if (array_key_exists('name', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name'], $category->id);
        }

        $category->update($attributes);

        return new CategoryResource($category->fresh());
    }

    public function destroy(Category $category): JsonResponse|Response
    {
        $this->authorize('delete', $category);

        try {
            $category->delete();
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

            // 23000 is the integrity-constraint state used by MySQL and SQLite.
            // 23503 is PostgreSQL's foreign-key violation.
            if (in_array($sqlState, ['23000', '23503'], true)) {
                return response()->json([
                    'message' => 'This category cannot be deleted while events still use it.',
                ], 409);
            }

            throw $exception;
        }

        return response()->noContent();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base === '' ? 'category' : $base;
        $suffix = 2;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
