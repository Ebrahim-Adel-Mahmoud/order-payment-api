<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\DTOs\AuthTokenResult;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function register(array $data): AuthTokenResult
    {
        $user = $this->users->create($data);

        return $this->issueToken($user);
    }

    /** @param array<string, string|null> $credentials */
    public function login(array $credentials): ?AuthTokenResult
    {
        if (! $token = auth('api')->attempt($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = auth('api')->user();

        return $this->tokenResult($user, $token);
    }

    public function logout(): void
    {
        auth('api')->logout();
    }

    private function issueToken(User $user): AuthTokenResult
    {
        return $this->tokenResult($user, JWTAuth::fromUser($user));
    }

    private function tokenResult(User $user, string $token): AuthTokenResult
    {
        return new AuthTokenResult(
            user: $user,
            accessToken: $token,
            expiresIn: (int) config('jwt.ttl') * 60,
        );
    }
}
