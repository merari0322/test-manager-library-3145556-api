<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Errors\InvalidCredentials;
use App\Library\Domain\User;

/** HU-01: log in. */
final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
    ) {
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, role: string} */
    public function login(string $username, string $password): array
    {
        $user = $this->users->findByUsername(User::normalize($username));

        // CA-01.2: same message whether the username or the password failed.
        if ($user === null || ! $this->hasher->verify($password, $user->passwordHash)) {
            throw new InvalidCredentials('Usuario o contraseña incorrectos.');
        }

        return [
            'access_token' => $this->tokens->issue($user),
            'token_type' => 'Bearer',
            'expires_in' => TokenIssuer::TTL_SECONDS,
            'role' => $user->role,
        ];
    }
}
