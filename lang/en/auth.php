<?php

return [
    'register_success' => 'User registered successfully',
    'validation' => [
        'name' => [
            'required' => 'The name field is required',
            'string' => 'The name must be a string',
            'max' => 'The name must not exceed :max characters',
        ],
        'email' => [
            'required' => 'The email field is required',
            'email' => 'Please enter a valid email address',
            'unique' => 'This email is already registered',
            'max' => 'The email must not exceed :max characters',
        ],
        'password' => [
            'required' => 'The password field is required',
            'min' => 'The password must be at least :min characters',
            'confirmed' => 'The password confirmation does not match',
        ],
        'phone' => [
            'required' => 'The phone number is required',
            'unique' => 'This phone number is already registered',
            'max' => 'The phone number must not exceed :max characters',
        ],
    ],
];
