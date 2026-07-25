<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_id' => 'required|exists:incidents,id',
            'old_status' => 'required|string',
            'new_status' => 'required|string',
        ];
    }
}