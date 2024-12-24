<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $variantImages = [];
        foreach ($this->variants as $variant) {
            if (!empty($variant->images)) {
                foreach ($variant->images as $image) {
                    $variantImages[] = [
                        'image' => url($image),
                        
                    ];
                }
            }
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'price' => $this->price,
            'stock' => $this->stock,
            'category_id' => $this->category_id,
            'user_id' => $this->user_id,
            'active' => $this->active,
            'featured' => $this->featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'attributes' => $this->attributes,
            'name' => $this->name,
            'description' => $this->description,
            'average_rating' => $this->average_rating,
            'favorite_count' => $this->favorite_count,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'favorites' => $this->whenLoaded('favorites'),
            // 'images' => array_map(function($image) {
            //     return url($image);
            // }, $this->images ?? []),
            'images' => $variantImages,
        ];
    }
}
