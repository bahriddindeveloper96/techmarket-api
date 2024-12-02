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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('auth.validation.name.required'),
            'name.string' => __('auth.validation.name.string'),
            'name.max' => __('auth.validation.name.max'),
            
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
