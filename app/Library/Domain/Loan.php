<?php

namespace App\Library\Domain;

use App\Library\Domain\Errors\DuplicateBookInLoan;
use App\Library\Domain\Errors\ItemAlreadyReturned;
use App\Library\Domain\Errors\NotFound;
use App\Library\Domain\Errors\ValidationFailed;
use DateInterval;
use DateTimeImmutable;

/**
 * Aggregate root: 1 to 3 lines, no repeated book, due at 14 days, immutable except returns
 * (architecture.md section 3). The status is computed, never stored (article VII).
 */
final class Loan
{
    public const MAX_ITEMS = 3;
    public const LOAN_DAYS = 14;

    /** @param list<LoanItem> $items */
    private function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
        public array $items,
    ) {
    }

    public static function open(string $id, string $memberId, string $userId, DateTimeImmutable $loanedAt): self
    {
        return new self($id, $memberId, $userId, $loanedAt, []);
    }

    /** @param list<LoanItem> $items */
    public static function restore(string $id, string $memberId, string $userId, DateTimeImmutable $loanedAt, array $items): self
    {
        return new self($id, $memberId, $userId, $loanedAt, $items);
    }

    /** Withdrawing the copy and creating the line are one operation (architecture.md section 3). */
    public function addItem(Book $book, string $categoryName): void
    {
        foreach ($this->items as $item) {
            if ($item->bookId === $book->id) {
                throw new DuplicateBookInLoan('No se puede repetir el mismo libro en un préstamo.');
            }
        }
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new ValidationFailed('Un préstamo tiene de 1 a 3 libros.', [['field' => 'items', 'issue' => 'de 1 a 3']]);
        }

        $book->withdraw();
        $this->items[] = new LoanItem(Uuid::v4(), $book->id, $book->title, $book->isbn->value, $categoryName,
            $this->loanedAt->add(new DateInterval('P'.self::LOAN_DAYS.'D')), null);
    }

    public function assertNotEmpty(): void
    {
        if ($this->items === []) {
            throw new ValidationFailed('Un préstamo tiene de 1 a 3 libros.', [['field' => 'items', 'issue' => 'de 1 a 3']]);
        }
    }

    /** CA-07.1 and CA-07.2: one line at a time, never twice. */
    public function returnItem(string $itemId, DateTimeImmutable $now): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                if ($item->returnedAt !== null) {
                    throw new ItemAlreadyReturned('Esta línea ya fue devuelta.');
                }
                $item->returnedAt = $now;

                return $item;
            }
        }

        throw new NotFound('La línea de préstamo no existe.');
    }

    /** CA-08.1 and api-contract.md section 3.6. */
    public function status(DateTimeImmutable $now): string
    {
        $open = array_filter($this->items, fn (LoanItem $i) => $i->returnedAt === null);
        if ($open === []) {
            return 'closed';
        }
        foreach ($open as $item) {
            if ($item->dueAt < $now) {
                return 'overdue';
            }
        }

        return 'open';
    }
}
