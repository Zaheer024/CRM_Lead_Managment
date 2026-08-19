<?php

namespace App\Http\Requests;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
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
        return [
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-().]{7,20}$/'],
            'source' => ['sometimes', Rule::in(LeadSource::all())],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(LeadStatus::all())],
            'remarks' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'source.in' => 'The selected source is not supported.',
            'status.in' => 'The selected status is not supported.',
        ];
    }
}
