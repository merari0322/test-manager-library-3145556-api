<?php

namespace App\Library\Domain\Errors;

final class UsernameTaken extends DomainError
{
    public const CODE = 'USERNAME_TAKEN';
}
