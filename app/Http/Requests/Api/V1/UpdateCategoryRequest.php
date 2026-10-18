<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->getKey();

        $nameRules = ['string', 'max:255', Rule::unique('categories', 'name')->ignore($categoryId)];

        if ($this->isMethod('PUT')) {
            array_unshift($nameRules, 'required');
        } else {
            array_unshift($nameRules, 'sometimes', 'required');
        }

        return [
            'name' => $nameRules,
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'slug' => ['prohibited'],
        ];
    }
}
