<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config()->boolean('iconify-api.search.enabled');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'query' => 'required|string',
            'prefixes' => 'nullable|string',
            'category' => 'nullable|string',
            'page' => 'nullable|integer',
            'limit' => 'nullable|integer',
            'palette' => 'nullable|boolean',
            'style' => 'nullable|string|in:fill,stroke',
            'similar' => 'nullable|boolean',
            'tags' => 'nullable|string',
        ];
    }
}
