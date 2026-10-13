<?php

namespace App\Library\Adapters\Outbound\Security;

use App\Library\Application\Ports\PasswordHasher;
use Illuminate\Support\Facades\Hash;

final class BcryptPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return Hash::driver('bcrypt')->make($plain, ['rounds' => 12]);
    }

    public function verify(string $plain, string $hash): bool
    {
        return Hash::driver('bcrypt')->check($plain, $hash);
    }
}
