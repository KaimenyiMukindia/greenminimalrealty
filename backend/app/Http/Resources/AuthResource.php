<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = ['user' => new UserResource($this['user'])];

        if (isset($this['token'])) {
            $data['access_token'] = $this['token'];
            $data['token_type'] = 'Bearer';
        }

        $data['message'] = $this['message'] ?? 'Authenticated.';

        return $data;
    }
}