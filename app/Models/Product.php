<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Category;
use App\Models\ProductTranslation;
use App\Models\Attribute;
use App\Models\ProductVariant;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'price',
        'stock',
        'category_id',
        'images',
        'active',
        'featured'
    ];

    protected $hidden = ['translations'];

    protected $appends = ['name', 'description'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'images' => 'array',
        'active' => 'boolean',
        'featured' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
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

    public function getAttributesByGroup()
    {
        $attributes = $this->attributes()
            ->with('group')
            ->get()
            ->groupBy('group.name');

        return $attributes->map(function ($groupAttributes) {
            return [
                'attributes' => $groupAttributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->name,
                        'value' => $attribute->pivot->value,
                        'type' => $attribute->type,
                        'filterable' => $attribute->filterable
                    ];
                })
            ];
        });
    }

    // Variant yaratish
    public function createVariant(array $attributeValues, float $price, int $stock): ?ProductVariant
    {
        // Xususiyatlar kombinatsiyasi mavjudligini tekshirish
        if (ProductVariant::hasVariantWithAttributes($this->id, $attributeValues)) {
            return null;
        }

        // SKU generatsiya qilish
        $sku = ProductVariant::generateSKU($this->id, $attributeValues);

        // Variant yaratish
        return $this->variants()->create([
            'price' => $price,
            'stock' => $stock,
            'attribute_values' => $attributeValues,
            'sku' => $sku,
            'active' => true
        ]);
    }

    // Variantni topish
    public function findVariant(array $attributeValues): ?ProductVariant
    {
        return $this->variants()
            ->where('attribute_values', json_encode($attributeValues))
            ->where('active', true)
            ->first();
    }
}