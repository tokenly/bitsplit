<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateField extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $types = config('settings.signup_field_types');
        return [
            'name' => 'required|unique:signup_fields,name',
            'type' => ['required', Rule::in($types)],
            'required' => 'required|boolean',
            'options' => 'required_if:type,Checkbox|min:1',
            'condition_field' => 'exists:signup_fields,id',
        ];
    }
}
