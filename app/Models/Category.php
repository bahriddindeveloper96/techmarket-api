<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'image',
        'images',
        'active',
        'parent_id',
        'user_id',
        'order',
        'featured'
    ];

    protected $casts = [
        'images' => 'array',
        'active' => 'boolean',
        'order' => 'integer',
        'featured' => 'boolean'
    ];

    protected $hidden = ['translations'];

    protected $appends = ['name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
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
