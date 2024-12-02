<?php

return [
    'register_success' => 'Foydalanuvchi muvaffaqiyatli ro\'yxatdan o\'tdi',
    'validation' => [
        'name' => [
            'required' => 'Ism maydoni to\'ldirilishi shart',
            'string' => 'Ism matn bo\'lishi kerak',
            'max' => 'Ism :max belgidan oshmasligi kerak',
        ],
        'email' => [
            'required' => 'Email maydoni to\'ldirilishi shart',
            'email' => 'Iltimos, to\'g\'ri email manzilini kiriting',
            'unique' => 'Bu email allaqachon ro\'yxatdan o\'tgan',
            'max' => 'Email :max belgidan oshmasligi kerak',
        ],
        'password' => [
            'required' => 'Parol maydoni to\'ldirilishi shart',
            'min' => 'Parol kamida :min belgidan iborat bo\'lishi kerak',
            'confirmed' => 'Parol tasdiqlash mos kelmadi',
        ],
        'phone' => [
            'required' => 'Telefon raqami to\'ldirilishi shart',
            'unique' => 'Bu telefon raqami allaqachon ro\'yxatdan o\'tgan',
            'max' => 'Telefon raqami :max belgidan oshmasligi kerak',
        ],
    ],
];
