<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|string|max:100|unique:cpt_types,slug|alpha_dash',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'request_mode' => 'required|in:normal,blocked,self_service',
            'mode_message' => 'nullable|string|max:5000',
            'content_areas' => 'nullable|array',
            'content_areas.*.name' => 'required|string|max:255',
            'content_areas.*.type' => 'required|in:text,textarea,select,checkbox,date,file,richtext,group',
            'content_areas.*.required' => 'nullable',
            'content_areas.*.help' => 'nullable|string|max:500',
            'content_areas.*.placeholder' => 'nullable|string|max:255',
            'content_areas.*.options' => 'nullable|array',
            'content_areas.*.options.*' => 'nullable|string|max:255',
            'content_areas.*.word_limit' => 'nullable|integer|min:1|max:10000',
            'content_areas.*.sub_fields' => 'nullable|array',
            'content_areas.*.sub_fields.*.name' => 'required|string|max:255',
            'content_areas.*.sub_fields.*.type' => 'required|in:text,textarea',
            'content_areas.*.repeatable' => 'nullable',
            'content_areas.*.allow_add' => 'nullable',
            'content_areas.*.reading_age' => 'nullable',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['is_active'] = $this->boolean('is_active');

        $contentAreas = collect($data['content_areas'] ?? [])
            ->filter(fn($area) => !empty($area['name']))
            ->map(function ($area) {
                $subFields = [];
                if (!empty($area['sub_fields']) && is_array($area['sub_fields'])) {
                    foreach ($area['sub_fields'] as $sf) {
                        if (!empty($sf['name']) && !empty($sf['type'])) {
                            $subFields[] = [
                                'name' => trim($sf['name']),
                                'type' => $sf['type'],
                            ];
                        }
                    }
                }

                return [
                    'name' => trim($area['name']),
                    'type' => $area['type'] ?? 'text',
                    'required' => !empty($area['required']),
                    'help' => trim($area['help'] ?? ''),
                    'placeholder' => trim($area['placeholder'] ?? ''),
                    'options' => array_values(array_filter(
                        $area['options'] ?? [],
                        fn($opt) => $opt !== null && trim($opt) !== ''
                    )),
                    'word_limit' => !empty($area['word_limit']) ? (int) $area['word_limit'] : null,
                    'sub_fields' => $subFields,
                    'repeatable' => !empty($area['repeatable']),
                    'allow_add' => !empty($area['allow_add']),
                    'reading_age' => !empty($area['reading_age']),
                ];
            })
            ->values()
            ->all();

        $data['form_config'] = !empty($contentAreas) ? ['content_areas' => $contentAreas] : null;
        unset($data['content_areas']);

        return $data;
    }
}
