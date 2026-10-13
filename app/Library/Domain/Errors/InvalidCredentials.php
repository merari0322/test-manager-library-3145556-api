<?php

namespace App\Library\Domain\Errors;

final class InvalidCredentials extends DomainError
{
    public const CODE = 'INVALID_CREDENTIALS';
}
