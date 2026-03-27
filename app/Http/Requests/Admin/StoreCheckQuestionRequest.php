<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text' => 'required|string|max:1000',
            'options' => 'required|array|min:2',
            'options.*.label' => 'required|string|max:255',
            'options.*.pass' => 'nullable',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['is_active'] = $this->boolean('is_active');
        $data['is_required'] = $this->boolean('is_required');
        $data['options'] = array_values(array_map(fn($o) => [
            'label' => $o['label'],
            'pass' => !empty($o['pass']),
        ], array_filter($data['options'], fn($o) => !empty($o['label']))));

        return $data;
    }
}
