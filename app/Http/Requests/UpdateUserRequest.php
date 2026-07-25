<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $this->user->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,technician,company',
            'is_active' => 'sometimes|boolean',
            'company_name' => 'required_if:role,company|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'software_solution_ids' => 'nullable|array',
            'software_solution_ids.*' => 'exists:software_solutions,id',
        ];
    }
}