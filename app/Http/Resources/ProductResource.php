<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'category' => $this->category,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'is_active' => $this->is_active,

            // 🖼️ image principale (première image)
            'image' => $this->whenLoaded('images', function () {
                $firstImage = $this->images->first();

                return $firstImage
                    ? asset('storage/' . $firstImage->path)
                    : null;
            }),

            // 🖼️ toutes les images
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->path),
                    'is_primary' => $img->pivot->is_primary ?? false,
                ]);
            }),
        ];
    }
}
