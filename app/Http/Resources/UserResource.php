<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Auth\DTOs\AuthTokenResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public static function authToken(AuthTokenResult $result): array
    {
        return [
            'user' => new self($result->user),
            'access_token' => $result->accessToken,
            'token_type' => 'bearer',
            'expires_in' => $result->expiresIn,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
