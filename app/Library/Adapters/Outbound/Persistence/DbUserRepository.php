<?php

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Errors\UsernameTaken;
use App\Library\Domain\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class DbUserRepository implements UserRepository
{
    public function findByUsername(string $username): ?User
    {
        $row = DB::table('user')->where('username', $username)->first();

        return $row === null ? null : User::create($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function add(User $user): void
    {
        try {
            DB::table('user')->insert([
                'id' => $user->id,
                'username' => $user->username,
                'password_hash' => $user->passwordHash,
                'role' => $user->role,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new UsernameTaken('El nombre de usuario ya existe.');
        }
    }
}
