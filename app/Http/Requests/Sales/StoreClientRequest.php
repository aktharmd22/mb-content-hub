<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSales() || $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name'          => ['required', 'string', 'max:255'],
            'company'       => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes'         => ['nullable', 'string', 'max:2000'],
        ];
    }
}
