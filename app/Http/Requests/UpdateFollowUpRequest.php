<?php

namespace App\Http\Requests;

use App\Models\FollowupStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFollowUpRequest extends FormRequest
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
            'followup_date' => ['sometimes', 'required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(FollowupStatus::all())],
        ];
    }

    public function messages(): array
    {
        return [
            'followup_date.date' => 'The follow-up date must be a valid date.',
        ];
    }
}
