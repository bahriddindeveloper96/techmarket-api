<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'translations' => ['required', 'array'],
            'translations.uz' => ['required', 'array'],
            'translations.uz.name' => ['required', 'string', 'max:255'],
            'translations.ru' => ['required', 'array'],
            'translations.ru.name' => ['required', 'string', 'max:255'],
            'translations.en' => ['required', 'array'],
            'translations.en.name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
        ];
    }

    public function messages(): array
    {
        return [
            'translations.required' => 'Translations are required',
            'translations.*.required' => 'Translation for this language is required',
            'translations.*.name.required' => 'Name is required for this language',
            'translations.*.name.string' => 'Name must be a string',
            'translations.*.name.max' => 'Name may not be greater than 255 characters',
            
            'email.required' => __('auth.validation.email.required'),
            'email.email' => __('auth.validation.email.email'),
            'email.unique' => __('auth.validation.email.unique'),
            'email.max' => __('auth.validation.email.max'),
            
            'password.required' => __('auth.validation.password.required'),
            'password.min' => __('auth.validation.password.min'),
            'password.confirmed' => __('auth.validation.password.confirmed'),
            
            'phone.required' => __('auth.validation.phone.required'),
            'phone.unique' => __('auth.validation.phone.unique'),
            'phone.max' => __('auth.validation.phone.max'),
        ];
    }
}
