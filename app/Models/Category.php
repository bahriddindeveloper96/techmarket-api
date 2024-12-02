<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CategoryTranslation;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'image',
        'active'
    ];

    protected $hidden = ['translations'];

    protected $appends = ['name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function getNameAttribute()
    {
        $translation = $this->translations->where('locale', app()->getLocale())->first();
        return $translation ? $translation->name : null;
    }

    public function getDescriptionAttribute()
    {
        $translation = $this->translations->where('locale', app()->getLocale())->first();
        return $translation ? $translation->description : null;
    }
}
