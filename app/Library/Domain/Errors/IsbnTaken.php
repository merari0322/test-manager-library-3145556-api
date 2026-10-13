<?php

namespace App\Library\Domain\Errors;

final class IsbnTaken extends DomainError
{
    public const CODE = 'ISBN_TAKEN';
}
