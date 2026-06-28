<?php

declare(strict_types=1);

namespace App\Services\Auth\DTOs;

use App\Models\User;

final readonly class AuthTokenResult
{
    public function __construct(
        public User $user,
        public string $accessToken,
        public int $expiresIn,
    ) {
    }
}
