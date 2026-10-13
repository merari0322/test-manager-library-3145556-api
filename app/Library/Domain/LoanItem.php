<?php

namespace App\Library\Domain;

use DateTimeImmutable;

/** A book inside a loan, with frozen title, ISBN and category name (CA-06.6, article VIII). */
final class LoanItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt,
    ) {
    }
}
