<?php

namespace App\Library\Domain\Errors;

final class BookHasOpenLoans extends DomainError
{
    public const CODE = 'BOOK_HAS_OPEN_LOANS';
}
