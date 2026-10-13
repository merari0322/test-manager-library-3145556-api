<?php

namespace App\Library\Application\Services;

use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Errors\UsernameTaken;
use App\Library\Domain\Errors\ValidationFailed;
use App\Library\Domain\User;
use App\Library\Domain\Uuid;

/** HU-02: the admin creates librarians. */
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {
    }

    public function create(string $username, string $password, string $role): User
    {
        // CA-02.3: the admin role is never granted through the API.
        if ($role !== User::ROLE_LIBRARIAN) {
            throw new ValidationFailed('Solo se pueden crear usuarios con rol librarian.', [
                ['field' => 'role', 'issue' => 'solo admite librarian'],
            ]);
        }

        $user = User::create(Uuid::v4(), $username, $this->hasher->hash($password), $role);

        // CA-02.2: username is unique.
        if ($this->users->findByUsername($user->username) !== null) {
            throw new UsernameTaken('El nombre de usuario ya existe.');
        }

        $this->users->add($user);

        return $user;
    }
}
