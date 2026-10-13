<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\User;

interface UserRepository
{
    public function findByUsername(string $username): ?User;

    public function add(User $user): void;
}
