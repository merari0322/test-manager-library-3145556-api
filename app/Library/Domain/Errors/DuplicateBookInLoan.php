<?php

namespace App\Library\Domain\Errors;

final class DuplicateBookInLoan extends DomainError
{
    public const CODE = 'DUPLICATE_BOOK_IN_LOAN';
}
