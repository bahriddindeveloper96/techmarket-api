<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'delivery_method_id',
        'payment_method_id',
        'delivery_name',
        'delivery_phone',
        'delivery_region',
        'delivery_district',
        'delivery_address',
        'delivery_comment',
        'delivery_cost',
        'desired_delivery_date',
        'payment_status',
        'total_amount',
        'total_discount',
        'payment_details',
        'status',
        'status_history'
    ];

    protected $casts = [
        'delivery_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'payment_details' => 'json',
        'status_history' => 'json',
        'desired_delivery_date' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = static::generateOrderNumber();
        });
    }

    protected static function generateOrderNumber(): string
    {
        $prefix = date('Ymd');
        $lastOrder = static::where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if (!$lastOrder) {
            return $prefix . '0001';
        }

        $lastNumber = intval(substr($lastOrder->order_number, -4));
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
