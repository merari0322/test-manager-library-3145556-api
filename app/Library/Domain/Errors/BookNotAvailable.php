<?php

namespace App\Library\Domain\Errors;

final class BookNotAvailable extends DomainError
{
    public const CODE = 'BOOK_NOT_AVAILABLE';
}
