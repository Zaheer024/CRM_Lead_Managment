<?php

namespace App\Http\Requests;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-().]{7,20}$/'],
            'source' => ['required', Rule::in(LeadSource::all())],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(LeadStatus::all())],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'The customer name is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'phone.regex' => 'The phone number format is invalid.',
            'source.required' => 'The source field is required.',
            'source.in' => 'The selected source is not supported.',
            'status.in' => 'The selected status is not supported.',
        ];
    }
}
