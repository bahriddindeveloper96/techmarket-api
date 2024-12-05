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
use App\Models\ProductReview;
use App\Models\Favorite;
use App\Models\CompareList;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'price',
        'stock',
        'category_id',
        'images',
        'active',
        'featured',
        'user_id'
    ];

    protected $hidden = ['translations'];

    protected $appends = ['name', 'description', 'average_rating', 'favorite_count'];

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function compareLists()
    {
        return $this->hasMany(CompareList::class);
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

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->where('is_approved', true)->avg('rating') ?? 0;
    }

    public function getFavoriteCountAttribute()
    {
        return $this->favorites()->count();
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
