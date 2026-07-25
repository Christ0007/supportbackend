<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSoftwareSolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
        ];
    }
}