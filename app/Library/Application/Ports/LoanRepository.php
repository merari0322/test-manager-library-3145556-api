<?php

namespace App\Library\Application\Ports;

use App\Library\Domain\Book;
use App\Library\Domain\DateRange;
use App\Library\Domain\Loan;
use App\Library\Domain\LoanItem;
use DateTimeImmutable;

interface LoanRepository
{
    public function get(string $id): ?Loan;

    /**
     * Saves the loan and withdraws one copy per book in one transaction.
     * Throws BookNotAvailable if another loan took the last copy first (RNF-2).
     *
     * @param list<Book> $books
     */
    public function add(Loan $loan, array $books): void;

    /** Sets returned_at and restocks the book once, in one transaction. Throws ItemAlreadyReturned on a race. */
    public function saveReturn(Loan $loan, LoanItem $item): void;

    /**
     * Q6: loans by range, computed status and member, newest first.
     *
     * @return array{items: list<Loan>, total: int}
     */
    public function search(?DateRange $range, ?string $status, ?string $memberId, DateTimeImmutable $now, int $page, int $size): array;

    /** Q8: unreturned lines due before now, most days late first. */
    public function overdue(DateTimeImmutable $now): array;
}
