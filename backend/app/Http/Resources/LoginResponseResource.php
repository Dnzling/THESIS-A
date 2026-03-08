<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'access_token' => $this->resource['token'],
            'token_type' => 'bearer',
            'abilities' => $this->resource['abilities'],
            'requires_verification' => $this->resource['requires_verification'] ?? false,
        ];
    }

    /**
     * Customize the response for the resource.
     */
    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->resource['status_code'] ?? 200);
    }
}