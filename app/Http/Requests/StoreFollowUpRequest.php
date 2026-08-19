<?php

namespace App\Http\Requests;

use App\Models\FollowupStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
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
            'followup_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(FollowupStatus::all())],
        ];
    }

    public function messages(): array
    {
        return [
            'followup_date.required' => 'The follow-up date is required.',
            'followup_date.date' => 'The follow-up date must be a valid date.',
        ];
    }
}
