<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intervention_date' => 'sometimes|date',
            'duration' => 'sometimes|integer|min:1',
            'description' => 'sometimes|string',
        ];
    }
}