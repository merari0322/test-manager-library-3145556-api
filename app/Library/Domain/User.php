<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\ValidationFailed;

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LIBRARIAN = 'librarian';

    private function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly string $role,
    ) {
    }

    public static function create(string $id, string $username, string $passwordHash, string $role): self
    {
        $username = self::normalize($username);
        if ($username === '') {
            throw new ValidationFailed('El nombre de usuario es obligatorio.', [
                ['field' => 'username', 'issue' => 'obligatorio'],
            ]);
        }
        if (! in_array($role, [self::ROLE_ADMIN, self::ROLE_LIBRARIAN], true)) {
            throw new ValidationFailed('El rol no es válido.', [
                ['field' => 'role', 'issue' => 'debe ser admin o librarian'],
            ]);
        }

        return new self($id, $username, $passwordHash, $role);
    }

    /** CA-02.2: lower case and trimmed. */
    public static function normalize(string $username): string
    {
        return mb_strtolower(trim($username));
    }
}
