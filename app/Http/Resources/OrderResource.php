<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'external_transaction_id' => $this->external_transaction_id,

            // 🏪 shop
            'shop' => new ShopResource($this->whenLoaded('shop')),

            // 📍 addresses
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
            'shipping_address' => new AddressResource($this->whenLoaded('shippingAddress')),

            // 🧾 items
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
