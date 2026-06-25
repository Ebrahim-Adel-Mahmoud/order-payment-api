<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsApiUser(?User $user = null): self
    {
        $user ??= User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $this;
    }

    /** @return array<string, string> */
    protected function apiHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
