<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'icon',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'json',
    ];

    protected $with = ['translations'];

    public function translations(): HasMany
    {
        return $this->hasMany(PaymentMethodTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    public function getNameAttribute()
    {
        return $this->translation()->name ?? null;
    }

    public function getDescriptionAttribute()
    {
        return $this->translation()->description ?? null;
    }
}
