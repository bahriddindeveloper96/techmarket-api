<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeGroup extends Model
{
    protected $fillable = [
        'name'
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attribute_groups')
            ->withPivot('attributes')
            ->withTimestamps();
    }
}
