<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'sitemap_url' => 'nullable|url|max:512',
            'default_approvers' => 'nullable|array',
            'default_approvers.*.name' => 'required|string|max:255',
            'default_approvers.*.email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['is_active'] = $this->boolean('is_active');
        $data['default_approvers'] = array_values(array_filter(
            $data['default_approvers'] ?? [],
            fn($a) => !empty($a['name'])
        ));

        return $data;
    }
}
