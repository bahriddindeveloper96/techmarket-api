<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'price',
        'stock',
        'attribute_values',
        'sku',
        'active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'attribute_values' => 'array',
        'active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            if (empty($variant->sku)) {
                $variant->sku = $variant->generateSku();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Xususiyatlar kombinatsiyasi mavjudligini tekshirish
    public static function hasVariantWithAttributes(int $productId, array $attributeValues): bool
    {
        return self::where('product_id', $productId)
            ->where('attribute_values', json_encode($attributeValues))
            ->exists();
    }

    // Sklad qoldig'ini tekshirish
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    // Skladdan chiqarish
    public function decreaseStock(int $quantity = 1): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        $this->stock -= $quantity;
        $this->save();
        return true;
    }

    // Skladga qo'shish
    public function increaseStock(int $quantity = 1): void
    {
        $this->stock += $quantity;
        $this->save();
    }

    protected function generateSku()
    {
        $product = $this->product;
        $attributeValues = collect($this->attribute_values)->values()->join('-');
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        
        return strtoupper($product->slug . '-' . $attributeValues . '-' . $randomString);
    }
}
