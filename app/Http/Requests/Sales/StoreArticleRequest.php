<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSales() || $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'client_id'         => ['nullable', 'integer', 'exists:clients,id'],
            'article_type_id'   => ['nullable', 'integer', 'exists:article_types,id'],
            'priority'          => ['required', Rule::in(['low', 'medium', 'high'])],
            'deadline'          => ['nullable', 'date', 'after_or_equal:today'],
            'word_count_target' => ['nullable', 'integer', 'min:50', 'max:50000'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'file'              => [
                'required',
                'file',
                'max:204800',
                'mimes:doc,docx,pdf,txt,jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mp3,wav,m4a',
            ],

            // Asset folder name (subfolder created inside the Assets parent)
            'assets_folder_name' => ['nullable', 'string', 'max:255'],

            // Optional list of attached assets — files and/or links
            'assets'          => ['nullable', 'array'],
            'assets.*.type'   => ['required_with:assets', Rule::in(['file', 'link'])],
            'assets.*.name'   => ['nullable', 'string', 'max:255'],
            'assets.*.file'   => ['nullable', 'file', 'max:204800', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mp3,wav,m4a,pdf,doc,docx,txt'],
            'assets.*.url'    => ['nullable', 'url', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'   => 'The file must be 200 MB or smaller.',
            'file.mimes' => 'Allowed: documents (.doc, .docx, .pdf, .txt), images (.jpg, .png, .gif, .webp), video (.mp4, .mov, .avi, .webm), or audio (.mp3, .wav, .m4a).',
        ];
    }
}
