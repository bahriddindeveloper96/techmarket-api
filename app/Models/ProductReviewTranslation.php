<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductReview;

class ProductReviewTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_review_id',
        'locale',
        'comment'
    ];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }
}
