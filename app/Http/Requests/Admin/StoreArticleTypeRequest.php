<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $typeId = $this->route('article_type')?->id;

        return [
            'name'            => ['required', 'string', 'max:100'],
            'slug'            => ['nullable', 'string', 'max:100', Rule::unique('article_types', 'slug')->ignore($typeId)],
            'drive_folder_id' => ['nullable', 'string', 'max:128'],
            'description'     => ['nullable', 'string', 'max:255'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }
}
