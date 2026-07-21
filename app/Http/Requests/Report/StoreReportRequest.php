<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $targetTable = $this->input('target_type') === 'user' ? 'users' : 'posts';

        return [
            'target_type' => ['required', Rule::in(['post', 'user'])],
            'target_id' => ['required', 'uuid', Rule::exists($targetTable, 'id')],
            'reason' => ['required', Rule::in(['spam', 'harassment', 'inappropriate', 'fake', 'privacy', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
