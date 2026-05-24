<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'min:10', 'max:255'],
            'model' => ['nullable', 'string', 'max:100'],
        ];
    }
}
