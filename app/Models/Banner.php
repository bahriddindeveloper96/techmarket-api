<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'url',
        'order',
        'active',
        'button_text'
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer'
    ];
}
