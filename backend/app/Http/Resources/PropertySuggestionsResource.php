<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertySuggestionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tags' => array_values($this['tags'] ?? []),
            'locations' => array_values($this['locations'] ?? []),
            'titles' => array_values($this['titles'] ?? []),
        ];
    }
}
