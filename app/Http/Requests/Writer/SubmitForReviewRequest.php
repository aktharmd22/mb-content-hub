<?php

namespace App\Http\Requests\Writer;

use Illuminate\Foundation\Http\FormRequest;

class SubmitForReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTechWriter() || $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'file'  => ['required', 'file', 'max:51200', 'mimes:doc,docx,pdf,txt'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'   => 'The file must be 50 MB or smaller.',
            'file.mimes' => 'Only .doc, .docx, .pdf, and .txt files are allowed.',
        ];
    }
}
