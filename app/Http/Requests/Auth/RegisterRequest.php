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
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'role' => ['sometimes', 'string', 'in:user,seller'],
            'bio' => ['sometimes', 'string', 'max:1000'],
            'address' => ['required_if:role,seller', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => 'First name is required',
            'firstname.string' => 'First name must be a string',
            'firstname.max' => 'First name may not be greater than 255 characters',
            
            'lastname.required' => 'Last name is required',
            'lastname.string' => 'Last name must be a string',
            'lastname.max' => 'Last name may not be greater than 255 characters',
            
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
            
            'address.required_if' => 'Address is required for sellers',
            'address.string' => 'Address must be a string',
            'address.max' => 'Address may not be greater than 255 characters',

            'bio.string' => 'Bio must be a string',
            'bio.max' => 'Bio may not be greater than 1000 characters',
        ];
    }
}
