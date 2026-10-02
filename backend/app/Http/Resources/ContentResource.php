<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource->toArray();

        if ($this->relationLoaded('items')) {
            $data['items'] = self::collection($this->items);
        }
        if ($this->relationLoaded('tags')) {
            $data['tags'] = self::collection($this->tags);
        }
        if ($this->relationLoaded('images')) {
            $data['images'] = self::collection($this->images);
        }
        if ($this->relationLoaded('children')) {
            $data['children'] = self::collection($this->children);
        }

        return $data;
    }
}