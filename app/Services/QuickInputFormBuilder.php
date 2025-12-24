<?php

namespace App\Services;

use App\Models\EmissionSourceFormField;
use Illuminate\Support\Facades\Validator;

class QuickInputFormBuilder
{
    /**
     * Build form fields array from emission source form fields
     *
     * @param int $emissionSourceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function buildForm($emissionSourceId)
    {
        return EmissionSourceFormField::where('emission_source_id', $emissionSourceId)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Validate form data based on field definitions
     *
     * @param array $data
     * @param \Illuminate\Database\Eloquent\Collection $formFields
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateForm($data, $formFields)
    {
        $rules = [];
        $messages = [];

        foreach ($formFields as $field) {
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Add type-specific validation
            switch ($field->field_type) {
                case 'number':
                case 'decimal':
                    $fieldRules[] = 'numeric';
                    if ($field->validation_rules) {
                        $validationRules = json_decode($field->validation_rules, true);
                        if (isset($validationRules['min'])) {
                            $fieldRules[] = 'min:' . $validationRules['min'];
                        }
                        if (isset($validationRules['max'])) {
                            $fieldRules[] = 'max:' . $validationRules['max'];
                        }
                    }
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
            }

            $rules[$field->field_name] = $fieldRules;

            if ($field->label) {
                $messages[$field->field_name . '.required'] = $field->label . ' is required.';
            }
        }

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Get field options for dropdown/select fields
     *
     * @param string $fieldName
     * @param int $sourceId
     * @return array
     */
    public function getFieldOptions($fieldName, $sourceId)
    {
        $field = EmissionSourceFormField::where('emission_source_id', $sourceId)
            ->where('field_name', $fieldName)
            ->first();

        if ($field && $field->options) {
            return json_decode($field->options, true);
        }

        return [];
    }

    /**
     * Get default values for form fields
     *
     * @param int $emissionSourceId
     * @return array
     */
    public function getDefaultValues($emissionSourceId)
    {
        $fields = $this->buildForm($emissionSourceId);
        $defaults = [];

        foreach ($fields as $field) {
            if ($field->default_value) {
                $defaults[$field->field_name] = $field->default_value;
            }
        }

        return $defaults;
    }

    /**
     * Format form field value based on field type
     *
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    public function formatFieldValue($value, $fieldType)
    {
        switch ($fieldType) {
            case 'number':
            case 'decimal':
                return is_numeric($value) ? (float) $value : null;
            case 'boolean':
            case 'checkbox':
                return (bool) $value;
            default:
                return $value;
        }
    }
}

