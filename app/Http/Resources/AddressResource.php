<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'phone' => $this->phone,
            'street' => $this->street,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'is_default' => $this->is_default,
        ];
    }
}
