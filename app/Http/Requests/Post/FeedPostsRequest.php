<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class FeedPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['sometimes', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'required_with:lat', 'numeric', 'between:-180,180'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ];
    }
}
