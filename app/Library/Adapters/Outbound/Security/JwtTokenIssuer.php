<?php

namespace App\Library\Adapters\Outbound\Security;

use App\Library\Application\Ports\TokenIssuer;
use App\Library\Domain\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/** D-11: JWT HS256, 8 hours, only sub and role (no personal data). */
final class JwtTokenIssuer implements TokenIssuer
{
    public function __construct(private readonly string $secret)
    {
    }

    public function issue(User $user): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $user->id,
            'role' => $user->role,
            'exp' => $now + self::TTL_SECONDS,
        ], $this->secret, 'HS256');
    }

    public function read(string $token): ?array
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (Throwable) {
            return null;
        }

        return ['sub' => (string) $claims->sub, 'role' => (string) $claims->role];
    }
}
