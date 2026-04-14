<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'logo' =>$this->logo,
            'logo_url' =>asset('storage/' . $this->logo),
            'slug' => $this->slug,
            'is_active' => $this->is_active,

            // 🔥 optional relations
            'products_count' => $this->whenLoaded('products', fn () => $this->products->count()),
        ];
    }
}
