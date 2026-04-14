<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'=>$this->name,
            'id' => $this->id,
            'url' => asset('storage/' . $this->path),
            'alt' => $this->alt,
            'mime_type' => $this->mime_type,
        ];
    }
}
