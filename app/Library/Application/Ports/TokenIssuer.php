<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\User;

interface TokenIssuer
{
    public const TTL_SECONDS = 28800;

    public function issue(User $user): string;

    /** @return array{sub: string, role: string}|null null when the token is invalid or expired */
    public function read(string $token): ?array;
}
